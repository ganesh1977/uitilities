<?php
// Oracle BI 27.3 <-- kig her
namespace AppBundle\Controller;

use DateInterval;
use DatePeriod;
use DateTime;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\RequestException;

class BedController extends Controller
{
    const DATE_FORMAT = 'd-M-Y';

    /**
     * @Route("/inventory/report", name="inventory_report")
     */
    public function reportAction(Request $request)
    {
        $promCd = strtoupper($request->query->get('prom_cd'));
        $arrCd = strtoupper($request->query->get('arr_cd'));
        $stay = intval($request->query->get('stay', 7));
        $stDt = new Datetime($request->query->get('st_dt'));
        $endDt = new Datetime($request->query->get('end_dt'));

        $conn = $this->get('doctrine.dbal.atcore_connection');

        $sql = "SELECT
                    tr.route_cd, tis.cycle_dt,
                    tis.alt SECTOR_ALT, tis.bkd SECTOR_BKD,
                    pt1.pt_cd DEP_CD,
                    pt2.pt_cd ARR_CD,
                    ti.alt, ti.bkd,
                    tsr.rule, tsd.duration,
                    prom.cd PROM_CD, srch.cd SRCH_GP_CD,
                    tiysd.land_rstn,
                    tiysdl.land_tp,
                    listagg(tiysdl.tp_id, ',') within group(order by tiysdl.tp_id) stc_stk_ids
                FROM
                    ATCOMRES.AR_TRANSROUTE tr
                        INNER JOIN ATCOMRES.AR_POINT pt1
                            ON pt1.pt_id = tr.dep_pt_id
                        INNER JOIN ATCOMRES.AR_POINT pt2
                            ON pt2.pt_id = tr.arr_pt_id
                        INNER JOIN ATCOMRES.AR_TRANSINVROUTE tir
                            ON tir.trans_route_id = tr.trans_route_id
                            INNER JOIN ATCOMRES.AR_TRANSINVSECTOR tis
                                ON tis.trans_inv_sec_id = tir.dep_sec_id
                                INNER JOIN ATCOMRES.AR_TRANSINVYIELDSELL tiys
                                    ON tiys.dep_sec_id = tis.trans_inv_sec_id
                                    INNER JOIN ATCOMRES.AR_TRANSINVENTORY ti
                                        ON ti.box_id = tiys.box_id
                                        INNER JOIN atcomres.ar_transsellrule tsr
                                            ON tsr.trans_sell_rule_id = tiys.trans_sell_rule_id
                                            INNER JOIN atcomres.ar_transselldetail tsd
                                                ON tsd.trans_sell_rule_id = tsr.trans_sell_rule_id
                                                INNER JOIN atcomres.ar_promotion prom
                                                    ON prom.prom_id = tsd.prom_id
                                                    INNER JOIN atcomres.ar_usercodes srch
                                                        ON srch.user_cd_id = prom.srch_prom_gp_id
                                    LEFT JOIN ATCOMRES.AR_TRANSINVYIELDSELLDETAIL tiysd
                                        ON tiysd.trans_inv_yield_sell_id = tiys.trans_inv_yield_sell_id
                                        LEFT JOIN ATCOMRES.AR_TRANSINVYEILDSELLDTLLAND tiysdl
                                            ON tiysdl.trans_inv_yeild_sell_dtl_id = tiysd.trans_inv_yield_sell_dtl_id
                WHERE 
                    tis.cycle_dt BETWEEN :st_dt AND :end_dt
                        AND
                    pt2.pt_cd = :arr_cd
                        AND
                    tr.dir_mth = 'OUT'
                        AND
                    tir.sale_sts = 'ON'
                        AND
                    ti.alt > 0
                GROUP BY
                    tr.route_cd, tis.cycle_dt, tis.alt, tis.bkd, pt1.pt_cd, pt2.pt_cd,
                    tis.trans_inv_sec_id, ti.alt, ti.bkd, tsr.rule, tsd.duration, 
                    prom.cd, srch.cd, tiysd.land_rstn, tiysdl.land_tp
                ORDER BY
                    srch.cd, tis.cycle_dt, tr.route_cd, prom.cd, tsr.rule";

        $stmt = $conn->prepare($sql);
        $stmt->bindValue('st_dt', $stDt, 'date');
        $stmt->bindValue('end_dt', $endDt, 'date');
        $stmt->bindValue('arr_cd', $arrCd);
        $stmt->execute();
        $results = $stmt->fetchAll();

        $flights = [];
        $flightDates = [];

        foreach ($results as $result) {
            $srchGpCd = $result['SRCH_GP_CD'];
            $cycleDt = $result['CYCLE_DT'];
            $routeCd = $result['ROUTE_CD'];
            $promCd = $result['PROM_CD'];
            $rule = $result['RULE'];
            $landRstn = $result['LAND_RSTN'];

            if (!isset($flights[$srchGpCd])) {
                $flights[$srchGpCd] = [];
            }

            if (!isset($flights[$srchGpCd][$cycleDt])) {
                $flights[$srchGpCd][$cycleDt] = [];
            }

            if (!isset($flights[$srchGpCd][$cycleDt][$routeCd])) {
                $flights[$srchGpCd][$cycleDt][$routeCd] = [
                    'sector_alt' => intval($result['SECTOR_ALT']),
                    'sector_bkd' => intval($result['SECTOR_BKD']),
                    'sell_rule_alt' => 0,
                    'sell_rule_bkd' => 0,
                    'dep_cd' => $result['DEP_CD'],
                    'arr_cd' => $result['ARR_CD'],
                    'promotions' => [],
                ];
            }
            $flights[$srchGpCd][$cycleDt][$routeCd]['sell_rule_alt'] += $result['ALT'];
            $flights[$srchGpCd][$cycleDt][$routeCd]['sell_rule_bkd'] += $result['BKD'];

            if (!isset($flights[$srchGpCd][$cycleDt][$routeCd]['promotions'][$promCd])) {
                $flights[$srchGpCd][$cycleDt][$routeCd]['promotions'][$promCd] = [
                    'sell_rule_alt' => 0,
                    'sell_rule_bkd' => 0,
                    'sell_rules' => [],
                ];
            }
            $flights[$srchGpCd][$cycleDt][$routeCd]['promotions'][$promCd]['sell_rule_alt'] += $result['ALT'];
            $flights[$srchGpCd][$cycleDt][$routeCd]['promotions'][$promCd]['sell_rule_bkd'] += $result['BKD'];

            if (!isset($flights[$srchGpCd][$cycleDt][$routeCd]['promotions'][$promCd]['sell_rules'][$rule])) {
                $flights[$srchGpCd][$cycleDt][$routeCd]['promotions'][$promCd]['sell_rules'][$rule] = [
                    'alt' => intval($result['ALT']),
                    'bkd' => intval($result['BKD']),
                    'duration' => intval($result['DURATION']),
                    'restrictions' => [],
                ];
            }

            if ($landRstn != 'ALL') {
                if (!isset($flights[$srchGpCd][$cycleDt][$routeCd]['promotions'][$promCd][$rule]['restrictions'][$landRstn])) {
                    $flights[$srchGpCd][$cycleDt][$routeCd]['promotions'][$promCd][$rule]['restrictions'][$landRstn] = [];
                }
                if (!is_null($result['LAND_TP']) && !is_null($result['STC_STK_IDS'])) {
                    $flights[$srchGpCd][$cycleDt][$routeCd]['promotions'][$promCd][$rule]['restrictions'][$landRstn][$result['LAND_TP']] = explode(',', $result['STC_STK_IDS']);
                }
            }


            // Flight Dates
            if (!in_array($cycleDt, $flightDates)) {
                $flightDates[] = $cycleDt;
            }
        }

        sort($flightDates);

        $sql = "SELECT
                    stc.stc_stk_id ACCOM_ID, stc.cd ACCOM_CD, stc.name ACCOM,
                    sellu.rm_id SELL_RM_ID, rm.cd RM_CD, rm.name RM,
                    sellu.inv_unit_id
                FROM
                    ATCOMRES.AR_STATICSTOCK stc
                        INNER JOIN ATCOMRES.AR_SELLSTATIC sell
                            ON sell.stc_stk_id = stc.stc_stk_id
                            INNER JOIN ATCOMRES.AR_PROMOTION prom
                                ON prom.prom_id = sell.prom_id
                                AND prom.cd = 'CCI'
                            INNER JOIN ATCOMRES.AR_SELLUNIT sellu
                                ON sellu.sell_stc_id = sell.sell_stc_id
                                INNER JOIN ATCOMRES.AR_ROOM rm
                                    ON rm.rm_id = sellu.rm_id
                        
                        INNER JOIN ATCOMRES.AR_STATICTRANSPORT stt
                          ON stt.stc_stk_id = stc.stc_stk_id
                            INNER JOIN ATCOMRES.AR_POINT pt
                              ON pt.pt_id = stt.pt_id
                WHERE
                    pt.pt_cd = :arr_cd
                ORDER BY
                    stc.name";

        $stmt = $conn->prepare($sql);
        $stmt->bindValue('arr_cd', $arrCd);
        $stmt->execute();
        $results = $stmt->fetchAll();

        $sql = "SELECT
                    sellu.inv_unit_id,
                    usr.cd SHR_CD,
                    inv.inv_dt, inv.alt_rel_dt,
                    inv.min_stay, inv.max_stay,
                    inv.sub_alloc_fg, usr.cd,
                    CASE
                        WHEN inv.alt_sts = 'CL' THEN 'CL'
                        ELSE NVL(invsub.alt_sts, inv.alt_sts)
                    END AS ALT_STS,
                    CASE
                        WHEN inv.st_day_sts = 'CL' THEN 'CL'
                        ELSE NVL(invsub.st_day_sts, inv.st_day_sts)
                    END AS ST_DAY_STS,
                    CASE
                        WHEN inv.dep_day_sts = 'CL' THEN 'CL'
                        ELSE NVL(invsub.dep_day_sts, inv.dep_day_sts)
                    END AS DEP_DAY_STS,
                    NVL(invsub.alt_alloc, inv.alt_alloc) AS ALT_ALLOC,
                    CASE NVL(invsub.alt_alloc,99999)
                        WHEN 99999 THEN inv.alt_opt+inv.alt_bkd
                        ELSE NVL(invsub.alt_occ1_ob+invsub.alt_occ2_ob+invsub.alt_occ3_ob+invsub.alt_occ4_ob+invsub.alt_occ5_ob+invsub.alt_occ6_ob,0)
                    END AS ALT_OB,
                    NVL(invsub.alt_exc_sd_alloc, inv.alt_exc_sd_alloc) AS ALT_EXC_SD_ALLOC,
                    NVL(invsub.alt_exc_sd_ob, inv.alt_exc_sd_ob) AS ALT_EXC_SD_OB,
                    (inv.alt_alloc - inv.alt_opt - inv.alt_bkd) AS ALT_MAIN_REMAIN
                FROM
                    ATCOMRES.AR_STATICSTOCK stc
                        INNER JOIN ATCOMRES.AR_SELLSTATIC sell
                            ON sell.stc_stk_id = stc.stc_stk_id
                            INNER JOIN ATCOMRES.AR_PROMOTION prom
                                ON prom.prom_id = sell.prom_id
                                AND prom.cd = 'CCI'
                            INNER JOIN ATCOMRES.AR_SELLUNIT sellu
                                ON sellu.sell_stc_id = sell.sell_stc_id
                                INNER JOIN ATCOMRES.AR_INVENTORY inv
                                    ON inv.stk_sub_id = sellu.inv_unit_id
                                    LEFT JOIN ATCOMRES.AR_INVENTORYSUB invsub
                                        ON invsub.stk_sub_id = inv.stk_sub_id
                                        AND invsub.inv_dt = inv.inv_dt
                                        LEFT JOIN ATCOMRES.AR_USERCODES usr
                                            ON usr.user_cd_id = invsub.shr_id
                        
                        INNER JOIN ATCOMRES.AR_STATICTRANSPORT stt
                          ON stt.stc_stk_id = stc.stc_stk_id
                            INNER JOIN ATCOMRES.AR_POINT pt
                              ON pt.pt_id = stt.pt_id
                WHERE
                    inv.inv_dt BETWEEN :st_dt AND :end_dt
                        AND
                    pt.pt_cd = :arr_cd
                ORDER BY
                    inv.inv_dt";


        $stmt = $conn->prepare($sql);
        $stmt->bindValue('st_dt', $stDt, 'date');

        $endDt->modify('+ ' . $stay . ' days');
        $stmt->bindValue('end_dt', $endDt, 'date');
        $endDt->modify('- ' . $stay . ' days');

        $stmt->bindValue('arr_cd', $arrCd);
        $stmt->execute();
        $results = $stmt->fetchAll();

        return $this->render('inventory/report.html.twig', [
            'flight_dates' => $flightDates,
            'flights' => $flights,
        ]);

    }




    /**
     * @Route("/inventory/bedcontrol", name="inventory_bedcontrol")
     */
    public function bedControlAction(Request $request)
    {
        $ts = microtime(true); // debug
        debug($ts, __LINE__); // debug

        $promCd = $request->query->get('prom_cd', null);
        $arrCd = $request->query->get('arr_cd', null);
        $stay = $request->query->get('stay', null);
        $stDt = new Datetime($request->query->get('st_dt', null));
        $endDt = new Datetime($request->query->get('end_dt', null));
        $hideShared = $request->query->get('hide_shared', null);

        $nowDt = new Datetime();
        $accommodations = [];
        $flightDates = [];

        // User has searched
        if ($arrCd) {

            $interval = $stDt->diff($endDt);
            $daysBetween = $interval->format('%a');
            if ($daysBetween > 61) {
                die('Sorry, you are requesting more data than allowed. Maximum two months.');
            }
            debug($ts, __LINE__); // debug

            // Flights
            $sectorRep = $this->getDoctrine()->getRepository('PrimeraAtcomResBundle:DB\TransInvSector', 'atcore');
            $sectors = $sectorRep->loadByDatesArrCdAndDirection($stDt, $endDt, $arrCd, 'OUT', 'ON');

            debug($ts, __LINE__); // debug

            foreach ($sectors as $sector) {
                $cycleDt = $sector->getCycleDt()->format('Y-m-d');
                $headCd = $sector->getTransHead()->getCd();
                foreach ($sector->getYieldSells() as $yieldSell) {
                    $sellRule = $yieldSell->getSellRule();
                    $sellDetails = $sellRule->getSellDetails();
                    $duration = $sellDetails[0]->getDuration();

                    // Only add if 0 day duration or duration = stay
//                    if ($duration == 0 || $duration == $stay) {
                    $sellRuleShrCd = substr($sellRule->getRule(), 0, 2);

                    // Only if promotion isn't set or promotion = sharer
                    if (!$promCd || strtoupper($promCd) == strtoupper($sellRuleShrCd)) {
                        $inventory = $yieldSell->getInventory();

                        if (($inventory->getAlt() - $inventory->getOB()) > 0) {
                            if (!isset($flightDates[$cycleDt])) {
                                $flightDates[$cycleDt] = [
                                    'sharers' => [],
                                    'transports' => [],
                                    'sell_rules' => [],
                                ];
                            }

                            if (!isset($flightDates[$cycleDt]['sharers'][$sellRuleShrCd])) {
                                $flightDates[$cycleDt]['sharers'][$sellRuleShrCd] = [
                                    'alt' => 0,
                                    'ob' => 0,
                                ];
                            }

                            if (!isset($flightDates[$cycleDt]['transports'][$headCd])) {
                                $flightDates[$cycleDt]['transports'][$headCd] = [
                                    'alt' => 0,
                                    'ob' => 0,
                                ];
                            }

                            if (!isset($flightDates[$cycleDt]['sell_rules'][$sellRule->getRule()])) {
                                $flightDates[$cycleDt]['sell_rules'][$sellRule->getRule()] = [
                                    'alt' => 0,
                                    'ob' => 0,
                                ];
                            }

                            $flightDates[$cycleDt]['sharers'][$sellRuleShrCd]['alt'] += $inventory->getAlt();
                            $flightDates[$cycleDt]['sharers'][$sellRuleShrCd]['ob'] += $inventory->getOB();

                            $flightDates[$cycleDt]['transports'][$headCd]['alt'] += $inventory->getAlt();
                            $flightDates[$cycleDt]['transports'][$headCd]['ob'] += $inventory->getOB();

                            $flightDates[$cycleDt]['sell_rules'][$sellRule->getRule()]['alt'] += $inventory->getAlt();
                            $flightDates[$cycleDt]['sell_rules'][$sellRule->getRule()]['ob'] += $inventory->getOB();

                        }
                    }
//                    }
                }

            } // end foreach ($sectors)

            debug($ts, __LINE__); // debug

            // End date must be extended with stay
            $endDt->modify('+' . $stay . ' days');

            // Stopsales
            $stopsaleRep = $this->getDoctrine()->getRepository('PrimeraAtcomResBundle:DB\StaticStockStopSale', 'atcore');
            $stopsales = $stopsaleRep->loadByArrCdAndDates($arrCd, $stDt, $endDt);

            debug($ts, __LINE__); // debug

            // Sell statics
            $sellRep = $this->getDoctrine()->getRepository('PrimeraAtcomResBundle:DB\SellStatic', 'atcore');
            $sellStatics = $sellRep->loadStatusReportByPromCdArrCdStayAndDates($promCd, $arrCd, $stay, $stDt, $endDt);

            debug($ts, __LINE__); // debug

            foreach ($sellStatics as $sellStatic) {
                $stc = $sellStatic->getStaticStock();
                foreach ($sellStatic->getSellUnits() as $sellUnit) {
                    $room = $sellUnit->getRoomOrOverrideRoom();

                    foreach ($flightDates as $stDtKey => $flightInfo) {
                        $flightStDt = new Datetime($stDtKey);
                        $flightEndDt = clone $flightStDt;
                        $flightEndDt->modify('+' . $stay . ' days');

                        $sharers = [];

                        foreach ($sellUnit->getInventory() as $inventory) {

                            // Date is within range?
                            if ($inventory->getInvDt() >= $flightStDt && $inventory->getInvDt() < $flightEndDt) {

                                foreach ($inventory->getInventorySubs() as $inventorySub) {
                                    $sharer = $inventorySub->getSharer();
                                    $shrCd = $sharer->getCd();

                                    // Only check if flight is matching.
                                    if (array_key_exists($shrCd, $flightInfo['sharers'])) {

                                        if (!isset($sharers[$shrCd])) {
                                            $sharers[$shrCd] = [
                                                'exc_rem' => 0,
                                                'rem' => null,
                                                'release' => null,
                                                'stopsale' => null,
                                            ];
                                        }

                                        $invCache = $inventorySub->getBundleCaches()[0];

                                        if ($inventory->getInvDt() == $flightStDt && isset($inventorySub->getSubStays()[0])) {
                                            $excSd = $inventorySub->getSubStays()[0];
                                            $sharers[$shrCd]['exc_rem'] = $invCache->getBundleAltAlloc() - $invCache->getBundleAltOB();
                                        }

                                        $rem = $invCache->getUnbundleAltRem();
                                        if (is_null($sharers[$shrCd]['rem'])) {
                                            $sharers[$shrCd]['rem'] = $rem;
                                        } else {
                                            $sharers[$shrCd]['rem'] = min($sharers[$shrCd]['rem'], $rem);
                                        }

                                        // Release
                                        if ($inventory->getAltRelDt() <= $nowDt) {
                                            $sharers[$shrCd]['release'] = true;
                                        }

                                        // Stopsales
                                        // Loop through all stopsales for this period
                                        foreach ($stopsales as $stopsale) {
                                            // Has stopsale already been found?
                                            if (!$sharers[$shrCd]['stopsale']) {
                                                // Promotion is null or promotion is same as sharer?
                                                if (is_null($stopsale->getPromId()) || $stopsale->getPromotion()->getCd() == $shrCd) {
                                                    // Accommodation is matching?
                                                    if ($stopsale->getTpId() == $stc->getStcStkId()) {
                                                        // Is stopsale relevant for this inventory date?
                                                        if ($inventory->getInvDt() >= $stopsale->getStDt() && $inventory->getInvDt() <= $stopsale->getEndDt()) {
                                                            // Is this for specific rooms or accommodation generic?
                                                            $stopsaleRooms = $stopsale->getStopSaleRooms();
                                                            if ($stopsaleRooms->isEmpty()) {
                                                                $sharers[$shrCd]['stopsale'] = true;
                                                            } else {
                                                                foreach ($stopsaleRooms as $stopsaleRoom) {
                                                                    if ($stopsaleRoom->getRmId() == $sellUnit->getRmId()) {
                                                                        $sharers[$shrCd]['stopsale'] = true;
                                                                        break;
                                                                    }
                                                                } // end foreach ($stopsaleRooms)
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        } // end foreach ($stopsales)
                                    }
                                } // end foreach ($inventory->getInventorySubs())
                            }
                        } // end foreach ($sellUnit->getInventory())

                        foreach ($sharers as $shrCd => $sharer) {
                            $rem = $sharer['exc_rem'] + $sharer['rem'];
                            if ($rem) {

                                if (!isset($accommodations[$stc->getCd()])) {
                                    $accommodations[$stc->getCd()] = [
                                        'stc_stk_id' => $stc->getStcStkId(),
                                        'name' => $stc->getName(),
                                        'rooms' => [],
                                    ];
                                }
                                if (!isset($accommodations[$stc->getCd()]['rooms'][$room->getCd()])) {
                                    $accommodations[$stc->getCd()]['rooms'][$room->getCd()] = [
                                        'rm_id' => $sellUnit->getRoom()->getRmId(),
                                        'name' => $room->getName(),
                                        'dates' => [],
                                    ];
                                }
                                if (!isset($accommodations[$stc->getCd()]['rooms'][$room->getCd()]['dates'][$flightStDt->format('Y-m-d')])) {
                                    $accommodations[$stc->getCd()]['rooms'][$room->getCd()]['dates'][$flightStDt->format('Y-m-d')] = [
                                        'sharers' => [],
                                    ];
                                }
                                if (!isset($accommodations[$stc->getCd()]['rooms'][$room->getCd()]['dates'][$flightStDt->format('Y-m-d')]['sharers'][$shrCd])) {
                                    $accommodations[$stc->getCd()]['rooms'][$room->getCd()]['dates'][$flightStDt->format('Y-m-d')]['sharers'][$shrCd] = [
                                        'remain' => $rem,
                                        'stopsale' => $sharer['stopsale'] ? true : false,
                                        'release' => $sharer['release'] ? true : false,
                                    ];
                                }
                            }
                        } // end foreach (sharers)
                    } // end foreach ($flightDates)

                }

            } // end foreach ($sellStatics)

            debug($ts, __LINE__); // debug

//            die();
        }

        return $this->render('inventory/bedcontrol.html.twig', [
            'search' => $request->query->all(),
            'accommodations' => $accommodations,
            'flight_dates' => $flightDates,
            'mc_exception' => [],
        ]);

    }


    /**
     * @Route("/inventory/accommodation", name="inventory_accommodation")
     */
    public function accommodationAction(Request $request)
    {
        $flights = [];
        $flightSellRules = [];
        $accommodations = [];
        $allDaysAccommodation = [];
        $stopsalesNotes = [];
        $mcException = null;
        $unitsMatrix = [];
        $Days_info = '';

        if ($request->query->has('prom_cd') && $request->query->has('arr_cd') &&
            $request->query->has('stay') && $request->query->has('st_dt') &&
            $request->query->has('end_dt')) {

            $promCd = strtoupper($request->query->get('prom_cd'));
            $arrCd = strtoupper($request->query->get('arr_cd'));
            $stay = intval($request->query->get('stay'));
            $stDt = new Datetime($request->query->get('st_dt'));
            $endDt = new Datetime($request->query->get('end_dt'));
            $hideShared = $request->query->get('hide_shared');
            $hide_empty = $request->query->get('hide_empty');

            $Days_info    =   $request->query->get('DAY');
            
            $interval = $stDt->diff($endDt);
            $daysBetween = $interval->format('%a');

            if ($daysBetween > 61) {
                die('Sorry, you are requesting more data than allowed. Maximum two months.');
            }

            $conn = $this->get('doctrine.dbal.atcore_connection');

            $atcore = $this->get('app.atcore');

            $atcore->stDt = $stDt;
            $atcore->endDt = $endDt;
            $atcore->dir = 'OUT';
            $atcore->arrCd = $arrCd;
            $atcore->stay = $stay;
            $atcore->promCd = $promCd;


            /* MEMORY CACHE */
            $atcore->nAdu = 2;

            $memoryCacheAccomsOnDates = [];

            if ($daysBetween > 31) {

                $mcException = [
                    'request' => 'Check is only done up 31 days',
                    'response' => 'Requested ' . $daysBetween . ' days.',
                ];

            } else {

                $memoryCacheLink = $atcore->getMemoryCacheLink();

                $timeout = 6.0;
                $client = new Client([
                    'timeout'  => $timeout,
                ]);

                try {
                    $response = $client->request('GET', $memoryCacheLink, [
                        'verify' => false,
                        'read_timeout' => $timeout,
                        'connect_timeout' => $timeout,
                    ]);

                    $memoryCacheXml = $response->getBody()->getContents();

                    $crawler = new Crawler($memoryCacheXml);
                    $offers = $crawler->filterXPath('/Results/Offers/Offer');

                    $offers = $crawler->filterXPath('//Offers/Offer')->each(function (Crawler $offer, $i) {
                        $depDate = new Datetime($offer->filterXPath('//Transport/Route[@Id=1]')->attr('DepDate'));
                        return [
                            'cycle_dt' => $depDate->format('d-M-Y'),
                            'accom_cd' => $offer->filterXPath('//Accom')->attr('Code'),
                            'dep_cd' => $offer->filterXPath('//Transport/Route[@Id=1]')->attr('DepPt'),
                        ];
                    });

                    foreach ($offers as $offer) {
                        $memoryCacheAccomsOnDates[$offer['cycle_dt']][$offer['accom_cd']][$offer['dep_cd']] = true;
                    }

                } catch (RequestException $e) {
                    $mcException = [
                        'request' => Psr7\str($e->getRequest()),
                        'response' => 'Timeout',
                    ];

                    if ($e->hasResponse()) {
                        $mcException['response'] = Psr7\str($e->getResponse());
                    }
                }
            }

            /*
             * Increase the timespan to fetch flight data n days further.
             *
             * E.g. 7 days stay and 7 days date period.
             * ======= ======= =======  21 days total
             * |-----|                  Initial search and timespan to render.
             *         |-----|          Extra flight data to calculate Bookable units
             *                 |-----|  Inventory data need n days after last flight
             */
             
            $atcore->endDt->modify('+ ' . $atcore->stay . ' days');

            // Flights
            $flightsBySharer = $atcore->getFlightsAndSellRulesBySharer($promCd,$Days_info); 
            
            $d = explode(",",$promCd);
            $cnt = 0;

            foreach($d as $key=>$value)
            {                
                if(!array_key_exists($value, $flightsBySharer))
                {
                    $cnt++;
                }
            }
            $fBySharer = array_keys($flightsBySharer); 
            //print_r($fBySharer); exit;
            //echo "COUNT EXISTS:".$cnt; exit;
            if($cnt == 0)
            {
                if (!array_key_exists($promCd, $flightsBySharer)) {
                    return $this->render('inventory/accommodation.html.twig', [
                        'search' => $request->query->all(),
                        'flights' => [],
                        'flight_sell_rules' => [],
                        'accommodations' => [],
                        'stopsales_notes' => [],
                        'mc_exception' => [],
                        'exception' => [
                            'message' => 'Unable to find data for Prom CD "'. $promCd .'" in result.'
                        ],
                    ]);
                }
            }
            $accommodations1 = [];
			
            foreach($fBySharer as $key=>$value)
            {
                        $flights = $flightsBySharer[$value];
                        $flights1[$value] = $flights;

                        /*
                         * Increases timespan to fetch inventory data for n + 2 * 7 days.
                         */
                        $atcore->endDt->modify('+ '. $atcore->stay .' days');
                        $atcore->promCd = $value;

                        // Accommodations
                        $inventory = $atcore->getAccommodationInventoryResults();
                        $inventoryByDate = [];
                        foreach ($inventory as $inv) {
                            if ($hideShared && !is_null($inv['MASTER_ACCOM_CD'])) {
                                continue;
                            }
                            $invDt = new Datetime($inv['INV_DT']);
                            $inventoryByDate[$invDt->format('d-M-Y')][] = $inv;
                        }


                        // Stopsales
                        $periodInterval = new \DateInterval( "P1D" ); // 1-day, though can be more sophisticated rule
                        $stopsalesByAirport = $atcore->getStopsalesByAirportResults();
                        $stopsales = [];
                        foreach ($stopsalesByAirport as $result) {
                            $ssAccomCd = $result['ACCOM_CD'];
                            $ssRmCd = $result['RM_CD'] ? $result['RM_CD'] : 'ALL';

                            if (!isset($stopsales[$ssAccomCd])) {
                                $stopsales[$ssAccomCd] = [];
                            }

                            if (!isset($stopsales[$ssAccomCd][$ssRmCd])) {
                                $stopsales[$ssAccomCd][$ssRmCd] = [];
                            }

                            $ssStDt = new Datetime($result['ST_DT']);
                            $ssEndDt = new Datetime($result['END_DT']);

                            $stopsalesNotes[$result['STC_STK_STOPSALE_ID']] = [
                                'st_dt' => $ssStDt->format('d-M-Y'),
                                'end_dt' => $ssEndDt->format('d-M-Y'),
                                //'text' => $result['TEXT'],
                            ];

                            $ssEndDt = $ssEndDt->modify( '+1 day' );
                            $period = new \DatePeriod($ssStDt, $periodInterval, $ssEndDt);
                            foreach ($period as $ssDate) {
                                if (!isset($stopsales[$ssAccomCd][$ssRmCd][$ssDate->format('d-M-Y')])) {
                                    $stopsales[$ssAccomCd][$ssRmCd][$ssDate->format('d-M-Y')] = [];
                                }

                                $stopsales[$ssAccomCd][$ssRmCd][$ssDate->format('d-M-Y')][] = $result['STC_STK_STOPSALE_ID'];
                            }
                        }

                        $accommodations = [];

                        foreach ($flights as $date => $routes) {
                            $fltStDt = new Datetime($date);
                            $invDt = clone $fltStDt;

                            for ($day = 0; $day < $stay; $day++) {

                                if (!isset($inventoryByDate[$invDt->format('d-M-Y')])) {
                                    return $this->render('inventory/accommodation.html.twig', [
                                        'search' => $request->query->all(),
                                        'flights' => [],
                                        'flight_sell_rules' => [],
                                        'accommodations' => [],
                                        'stopsales_notes' => [],
                                        'mc_exception' => [],
                                        'exception' => [
                                            'message' => 'Unable to find data for date "'. $invDt->format('d-M-Y') .'" in result.'
                                        ],
                                    ]);
                                }
                                foreach ($inventoryByDate[$invDt->format('d-M-Y')] as $key => $inv) {

                                    $accomCd = $inv['ACCOM_CD'];
                                    $rmCd = $inv['RM_CD'];

                                    $alloc = $inv['ALT_ALLOC'];
                                    $bkd = $inv['ALT_OB'];
                                    $excAlloc = $inv['ALT_EXC_SD_ALLOC'];
                                    $excBkd = $inv['ALT_EXC_SD_OB'];
                                    $mainRemain = $inv['ALT_MAIN_REMAIN'];

                                    $multiplyOfSeven = (!($stay % 7) && $day > 0 && !($day % 7)) ? true : false;

                                    if ($fltStDt == $invDt || $multiplyOfSeven) {
                                        $unitsOnThisDay =  $alloc - $bkd;
                                    } else {
                                        $unitsOnThisDay =  $alloc - $bkd - $excAlloc + $excBkd;
                                    }

                                    if ($fltStDt == $invDt and $inv['ST_DAY_STS'] != 'OP') {
                                        $unitsOnThisDay = 0;
                                    }

                                    $unitsOnThisDay = $unitsOnThisDay > $mainRemain ? $mainRemain : $unitsOnThisDay;

                                    /**
                                     * Create a matrix of theoretical units.
                                     * It's group in ACCOM -> ROOM -> FLIGHT DATE.
                                     * Key is the date of the theoretical units and the value
                                     * are the units.
                                     */
                                    if ($invDt <= $endDt) {
                                        $_invDateString = $invDt->format(self::DATE_FORMAT);
                                        $_flightDateString = $fltStDt->format(self::DATE_FORMAT);
                                        if (!isset($unitsMatrix[$accomCd])) {
                                            $unitsMatrix[$accomCd] = [];
                                        }
                                        if (!isset($unitsMatrix[$accomCd][$rmCd])) {
                                            $unitsMatrix[$accomCd][$rmCd] = [];
                                        }
                                        if (!isset($unitsMatrix[$accomCd][$rmCd][$_flightDateString])) {
                                            $unitsMatrix[$accomCd][$rmCd][$_flightDateString] = [];
                                        }
                                        $unitsMatrix[$accomCd][$rmCd][$_flightDateString][$_invDateString] = $unitsOnThisDay;
                                    }

                                    if (!isset($accommodations[$accomCd])) {
                                        $accommodations[$accomCd] = [
                                            'name' => $inv['ACCOM'],
                                            'hide' => is_remove($inv['ACCOM_CD']),
                                            'rooms' => [],
                                        ];

                                        $allDaysAccommodation[$accomCd] = [
                                            'name' => $inv['ACCOM'],
                                            'rooms' => []
                                        ];
                                    }

                                    if (!isset($accommodations[$accomCd]['rooms'][$rmCd])) {
                                        $accommodations[$accomCd]['rooms'][$rmCd] = [
                                            'name' => $inv['RM'],
                                            'guarantee' => is_guarantee($inv['RM_CD']),
                                            'flt_dates' => [],
                                            'hide' => true,
                                        ];

                                        $allDaysAccommodation[$accomCd]['rooms'][$rmCd] = [
                                            'name' => $inv['RM'],
                                            'dates' => []
                                        ];
                                    }

                                    if (!isset($accommodations[$accomCd]['rooms'][$rmCd]['flt_dates'][$date])) {
                                        $accommodations[$accomCd]['rooms'][$rmCd]['flt_dates'][$date] = [
                                            'units' => $unitsOnThisDay,
                                            'min_stay' => $inv['MIN_STAY'],
                                            'max_stay' => $inv['MAX_STAY'],
                                            'released' => false,
                                            'release_dates' => [],
                                            'earliest_release' => null,
                                            'stopsale' => false,
                                            'stopsale_notes' => [],
                                            'in_memory' => isset($memoryCacheAccomsOnDates[$date][$accomCd]) || !is_null($mcException),
                                            'sharer' => true,
                                            'bkd_data' => [
                                                'alloc' => $alloc,
                                                'bkd' => $bkd,
                                                'exc_alloc' => $excAlloc,
                                                'exc_bkd' => $excBkd
                                            ]
                                        ];
                                    }

                                    if (!isset($allDaysAccommodation[$accomCd]['rooms'][$rmCd]['flt_dates'][$invDt->format(self::DATE_FORMAT)])) {
                                        $allDaysAccommodation[$accomCd]['rooms'][$rmCd]['dates'][$invDt->format(self::DATE_FORMAT)] = [
                                            'units' => $unitsOnThisDay,
                                            'alloc' => $alloc,
                                            'bkd' => $bkd,
                                            'exc_alloc' => $excAlloc,
                                            'exc_bkd' => $excBkd
                                        ];
                                    }

                                    if (is_null($inv['STK_SUB_ID'])) {
                                        $accommodations[$accomCd]['rooms'][$rmCd]['flt_dates'][$date]['sharer'] = false;
                                    }

                                    $accommodations[$accomCd]['rooms'][$rmCd]['flt_dates'][$date]['units'] = min($accommodations[$accomCd]['rooms'][$rmCd]['flt_dates'][$date]['units'], $unitsOnThisDay);
                                    $accommodations[$accomCd]['rooms'][$rmCd]['flt_dates'][$date]['min_stay'] = max($accommodations[$accomCd]['rooms'][$rmCd]['flt_dates'][$date]['min_stay'], $inv['MIN_STAY']);
                                    $accommodations[$accomCd]['rooms'][$rmCd]['flt_dates'][$date]['max_stay'] = min($accommodations[$accomCd]['rooms'][$rmCd]['flt_dates'][$date]['max_stay'], $inv['MAX_STAY']);

                                    // release
                                    $releaseTime = strtotime($inv['ALT_REL_DT']);
                                    if (is_null($accommodations[$accomCd]['rooms'][$rmCd]['flt_dates'][$date]['earliest_release'])) {
                                        $accommodations[$accomCd]['rooms'][$rmCd]['flt_dates'][$date]['earliest_release'] = $releaseTime;
                                    } else {
                                        $accommodations[$accomCd]['rooms'][$rmCd]['flt_dates'][$date]['earliest_release'] = min($accommodations[$accomCd]['rooms'][$rmCd]['flt_dates'][$date]['earliest_release'], $releaseTime);
                                    }
                                    if ($releaseTime < time()) {
                                        $accommodations[$accomCd]['rooms'][$rmCd]['flt_dates'][$date]['released'] = true;
                                        $accommodations[$accomCd]['rooms'][$rmCd]['flt_dates'][$date]['release_dates'][$invDt->format('d-M-Y')] = $inv['ALT_REL_DT'];
                                    }

                                    // stopsales
                                    if (isset($stopsales[$accomCd][$rmCd][$invDt->format('d-M-Y')])) { // room stop sale
                                        $accommodations[$accomCd]['rooms'][$rmCd]['flt_dates'][$date]['stopsale'] = true;
                                        foreach ($stopsales[$accomCd][$rmCd][$invDt->format('d-M-Y')] as $ssNoteId) {
                                            if (!isset($accommodations[$accomCd]['rooms'][$rmCd]['flt_dates'][$date]['stopsale_notes'][$ssNoteId])) {
                                                $accommodations[$accomCd]['rooms'][$rmCd]['flt_dates'][$date]['stopsale_notes'][$ssNoteId] = true;
                                            }
                                        }
                                    }
                                    if (isset($stopsales[$accomCd]['ALL'][$invDt->format('d-M-Y')])) { // hotel stop sale
                                        $accommodations[$accomCd]['rooms'][$rmCd]['flt_dates'][$date]['stopsale'] = true;
                                        foreach ($stopsales[$accomCd]['ALL'][$invDt->format('d-M-Y')] as $ssNoteId) {
                                            if (!isset($accommodations[$accomCd]['rooms'][$rmCd]['flt_dates'][$date]['stopsale_notes'][$ssNoteId])) {
                                                $accommodations[$accomCd]['rooms'][$rmCd]['flt_dates'][$date]['stopsale_notes'][$ssNoteId] = true;
                                            }
                                        }
                                    }

                                }

                                // next day in stay
                                $invDt->modify('+1 day');
                            }

                            $fltDt = $fltStDt->format('d-M-Y');
                            if (!isset($flightSellRules[$fltDt])) {
                                $flightSellRules[$fltDt] = [];
                            }
                            foreach ($routes as $routeCd => $route) {
                                foreach ($route['rules'] as $ruleCd => $rule) {

                                    if ($rule['alt'] > 0) {

                                        if (!isset($flightSellRules[$fltDt][$ruleCd])) {
                                            $flightSellRules[$fltDt][$ruleCd] = [
                                                'alt' => 0,
                                                'bkd' => 0,
                                            ];
                                        }

                                        $flightSellRules[$fltDt][$ruleCd]['alt'] += $rule['alt'];
                                        $flightSellRules[$fltDt][$ruleCd]['bkd'] += $rule['bkd'];

                                    }
                                }
                            }
                        }       
                        $flightSellRules1[$value] = $flightSellRules;
                    /*
                     * Clone arrays and clean the original arrays.
                     */

                    $oldAccommodations = $accommodations;
                    $oldFlights = $flights;

                    unset($accommodations);
                    unset($flights);

                    $accommodations = [];
                    $flights = [];

                    $loopInterval = DateInterval::createFromDateString('1 day');

                    foreach ($oldAccommodations as $accomCd => $accommodation) {
                        foreach ($accommodation['rooms'] as $rmCd => $room) {
                            foreach ($room['flt_dates'] as $fltIdx => $fltDate) {

                                $today = new DateTime($fltIdx);

                                $_endDt = new Datetime($request->query->get('end_dt'));
                                if ($today > $_endDt) {
                                    break 1;
                                }

                                if (!isset($flights[$fltIdx])) {
                                    $flights[$fltIdx] = $oldFlights[$fltIdx];
                                }

                                if (!isset($accommodations[$accomCd])) {
                                    $accommodations[$accomCd] = $accommodation;
                                }

                                $end = (clone $today)->modify('+7 days');
                                $period = new DatePeriod($today, $loopInterval, $end);

                                $_dates = $allDaysAccommodation[$accomCd]['rooms'][$rmCd]['dates'];

                                $excSumMatrix = [];

                                // Each day must calculate their bookable units.
                                foreach ($period as $_fltDate) {
                                    $noExclusives = true;
                                    $excSum = 0;
                                    $allocMatrix = [];
                                    $_fltDt = $_fltDate->format(self::DATE_FORMAT);

                                    $_today = clone $_fltDate;
                                    $_end = (clone $_today)->modify('+7 days');
                                    $_period = new DatePeriod($_today, $loopInterval, $_end);

                                    // Each day must loop n days ahead to calculate bookable units.
                                    foreach ($_period as $_date) {
                                        $dt = $_date->format(self::DATE_FORMAT);

                                        // Skip days without data.
                                        if (!isset($_dates[$dt])) continue 1;

                                        $_allotment = (int)$_dates[$dt]['alloc'];
                                        $allocMatrix[] = $_allotment;

                                        if (!is_null($_dates[$dt]['exc_alloc'])) {
                                            $noExclusives = false;
                                        }

                                        if ($_today == $_date) continue 1;
                                        $excSum -= $_dates[$dt]['exc_alloc'];
                                    }

                                    // Create flags to see if the alloc data increases and or decreases.
                                    // This is done because when alloc data increases the calculation is different.
                                    list($increaseFlag, $decreaseFlag) = $this->allocTableAnalysis($allocMatrix);

                                    $allotment = @min($allocMatrix);
                                    if ($increaseFlag && !$decreaseFlag) {
                                        $allotment = @max($allocMatrix);
                                    }

                                    $excSum -= @$allDaysAccommodation[$accomCd]['rooms'][$rmCd]['dates'][$_fltDt]['exc_bkd'];

                                    $excSum += $allotment;
                                    $excSumMatrix[$_fltDt] = @is_null($allDaysAccommodation[$accomCd]['rooms'][$rmCd]['dates'][$_fltDt]['exc_alloc']) ? null : $excSum;
                                }

                                // Choose the lowest value just like the allotment table on the status page.
                                $sum = @min($unitsMatrix[$accomCd][$rmCd][$fltIdx]);
                                $excSum = $excSumMatrix[$fltIdx];

                                $accommodations[$accomCd]['rooms'][$rmCd]['flt_dates'][$fltIdx]['bookable_units'] = $noExclusives ? $sum : $excSum;

                                // Unhide rooms that have units and aren't on stopsale or released and have sharer
                                if ($fltDate['units'] > 0 && !$fltDate['stopsale'] && !$fltDate['released'] && $fltDate['sharer']) {
                                    $accommodations[$accomCd]['rooms'][$rmCd]['hide'] = false;
                                    if ($accommodations[$accomCd]['hide'] === true) {
                                        $accommodations[$accomCd]['hide'] = false;
                                    }
                                }
                            }
                        }
                    }
                    $accommodations1[$value] = $accommodations;
                    $flights2[$value] = $flights;
                }
            }
        /**
         * EXCEL version! 
         */
        if ($request->query->has('excel')) {
            // ask the service for a Excel5
            $phpExcelObject = $this->get('phpexcel')->createPHPExcelObject();

            $xls_col = 'A';
            $xls_row = 1;
            $phpExcelObject->setActiveSheetIndex(0)->setCellValue($xls_col++ . $xls_row, 'Accom Cd');
            $phpExcelObject->setActiveSheetIndex(0)->setCellValue($xls_col++ . $xls_row, 'Accom');
            $phpExcelObject->setActiveSheetIndex(0)->setCellValue($xls_col++ . $xls_row, 'Room Cd');
            $phpExcelObject->setActiveSheetIndex(0)->setCellValue($xls_col++ . $xls_row, 'Room');
            foreach ($flights as $date => $routes) {
                $seats = 0;
                foreach ($routes as $route) {
                    $seats += $route['alt'] - $route['bkd'];
                }
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue($xls_col++ . $xls_row, $date . ' (' . $seats . ')');
            }
            $phpExcelObject->getActiveSheet()->getStyle('A' . $xls_row . ':' . $xls_col . $xls_row)->getFont()->setBold(true);          
                           
            foreach($accommodations1 as $key=>$value) {
                foreach ($accommodations1[$key] as $accomCd => $accom) {                 
                    foreach ($accom['rooms'] as $rmCd => $rm) {
                        if(!$rm['hide']){
                                $xls_col = 'A';
                                $xls_row++;
                                $phpExcelObject->setActiveSheetIndex(0)->setCellValue($xls_col++ . $xls_row, $accomCd);
                                $phpExcelObject->setActiveSheetIndex(0)->setCellValue($xls_col++ . $xls_row, $accom['name']);
                                $phpExcelObject->setActiveSheetIndex(0)->setCellValue($xls_col++ . $xls_row, $rmCd);
                                $phpExcelObject->setActiveSheetIndex(0)->setCellValue($xls_col++ . $xls_row, $rm['name']);
                                foreach ($flights as $date => $routes) {
                                    if (isset($rm['flt_dates'][$date]['units'])) {
                                        $units = $rm['flt_dates'][$date]['units'];
                                        if ($rm['flt_dates'][$date]['released'] or $rm['flt_dates'][$date]['stopsale']) {
                                            $units = 0;
                                        }
                                    } else {
                                        $units = 0;
                                    }
                                    $phpExcelObject->setActiveSheetIndex(0)->setCellValue($xls_col++ . $xls_row, $units);
                                }
                            }
                        }                
                   }
                }
                $xls_row++;
                $phpExcelObject->getActiveSheet()->mergeCells('A' . $xls_row. ':D' . $xls_row);
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('A' . $xls_row, 'Total rooms');
                $xls_col = 'E';
                foreach ($flights as $date => $routes) {
                    $phpExcelObject->setActiveSheetIndex(0)->setCellValue($xls_col . $xls_row, '=SUM(' . $xls_col . '2:'. $xls_col . ($xls_row-1) . ')');
                    $xls_col++;
                }
                $phpExcelObject->getActiveSheet()->getStyle('A' . $xls_row . ':' . $xls_col . $xls_row)->getFont()->setBold(true);

                $occupancyRules = [2.1,2.25,2.5,2.75,3.0];
                foreach ($occupancyRules as $occ) {
                    $xls_row++;
                    $phpExcelObject->getActiveSheet()->mergeCells('A' . $xls_row. ':D' . $xls_row);
                    $phpExcelObject->setActiveSheetIndex(0)->setCellValue('A' . $xls_row, 'Rooms needed (Occ ' . $occ . ')');
                    $xls_col = 'E';
                    foreach ($flights as $date => $routes) {
                        $seats = 0;
                        foreach ($routes as $route) {
                            $seats += $route['alt'] - $route['bkd'];
                        }
                        $phpExcelObject->setActiveSheetIndex(0)->setCellValue($xls_col++ . $xls_row, '=CEILING(' . $seats . '/'. $occ . ',1)');
                    }
                    $phpExcelObject->getActiveSheet()->getStyle('A' . $xls_row . ':' . $xls_col . $xls_row)->getFont()->setItalic(true);
                }

                $max_col = 4 + count($flights);
                for ($xls_col = 'A', $i = 0; $i < $max_col; $xls_col++, $i++) {
                    $phpExcelObject->getActiveSheet()->getColumnDimension($xls_col)->setAutoSize(true);
                }


                $phpExcelObject->getActiveSheet()->setTitle($promCd);


                // Set active sheet index to the first sheet, so Excel opens this as the first sheet
                $phpExcelObject->setActiveSheetIndex(0);

                // create the writer
                $writer = $this->get('phpexcel')->createWriter($phpExcelObject, 'Excel2007');
                // create the response
                $response = $this->get('phpexcel')->createStreamedResponse($writer);
                // adding headers
                $dispositionHeader = $response->headers->makeDisposition(
                    ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                    'BedControl_' . $promCd. '_' . $arrCd . '_' . $stDt->format('Ymd') . '-' . $endDt->format('Ymd') . '_' . $stay . '_' . date('YmdHis') . '_.xlsx'
                );
                $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
                $response->headers->set('Pragma', 'public');
                $response->headers->set('Cache-Control', 'maxage=1');
                $response->headers->set('Content-Disposition', $dispositionHeader);

                return $response;
            
            /**
             * HTML version
            */
          
        } else {
            // if accomodations is not available
            if(!isset($accommodations1))
            {
                $accommodations1 = [];
            } 
            
            //if flights are not available
            if(!isset($flights2))
            {
                $flights2 = [];
            }
            
            if(!isset($flightSellRules1))
            {
                $flightSellRules1 = [];
            }      
            
            //All flights with dates and sell rule and routs
            $ar = array();
            foreach($flights2 as $brand_key=>$brand_val)
            {
                foreach($brand_val as $key=>$val)
                {
                        $ar[] =  $key;
                }
            }   
            foreach($ar as $fdate=>$fvalue)
            {
                foreach($flights2 as $key=>$val)
                {                    
                    if(!array_key_exists($fvalue, $val))                    
                    {   
                        $flights2[$key][$fvalue] = array();
                        ksort($flights2[$key]);            
                    }
                }
            }
            
            $ar = array_unique($ar);            

            $flight_sell_det = array();
            foreach($flights2 as $brand_key=>$brand_val)
            {   
                foreach($brand_val as $date_key=>$route_val)
                {
                    $c = 1;                    
                    if(in_array($date_key,$ar))
                    {                        
                        $seats      = '0';
                        $new        = array();
                        $str        = '';
                        $rountinfo  = ''; 
                        foreach($route_val as $kkey=>$vval)
                        {                            
                            $seats = $seats+$vval['alt']-$vval['bkd'];
                            $rountinfos = substr($kkey,0,8).":".$vval['alt']."/".$vval['bkd']."/".$seats;    
                            $rountinfo.=$rountinfos."<br>";
                            $s = 0;  
                            
                            foreach($vval['rules'] as $sellKey=>$sellValue)
                            {
                                if($sellValue['alt']!=0 && $sellValue['bkd']!=0)
                                {   
                                    $s =  $sellValue['alt'] - $sellValue['bkd'];
                                    $sellDataVal = $sellValue['alt']."/".$sellValue['bkd']."/".$s;
                                    $str.=$sellKey.":".$sellDataVal."<br>";
                                    $new[] = array($sellKey=>$sellDataVal); 
                                }
                                elseif($sellValue['alt']==0 && $sellValue['bkd']!=0)
                                {
                                    $s =  $sellValue['alt'] - $sellValue['bkd'];
                                    $sellDataVal = $sellValue['alt']."/".$sellValue['bkd']."/".$s;
                                    $str.=$sellKey.":".$sellDataVal."<br>";
                                    $new[] = array($sellKey=>$sellDataVal); 
                                }elseif($sellValue['alt']!=0 && $sellValue['bkd']==0)
                                {
                                    $s =  $sellValue['alt'] - $sellValue['bkd'];
                                    $sellDataVal = $sellValue['alt']."/".$sellValue['bkd']."/".$s;
                                    $str.=$sellKey.":".$sellDataVal."<br>";
                                    $new[] = array($sellKey=>$sellDataVal); 
                                }
                            }                                                         
                        }    
                        
                        if(!array_key_exists ($date_key,$flight_sell_det))                            
                        {
                            if(count($new)==0)
                            {
                                $new = array();
                            }
                            $flight_sell_det[$date_key] = array("sell"=>rtrim($str,'<br>'),"BRAND"=>$brand_key,"seats"=>$seats,"Route"=>"all/bkd/avl","routeCode"=>rtrim($rountinfo,"<br>"));
                        }
                        else
                        {    
                             $flight_sell_det[$date_key] = array($flight_sell_det[$date_key],array("sell"=>rtrim($str,'<br>'),"seats"=>$seats,"BRAND"=>$brand_key,"Route"=>"all/bkd/avl","routeCode"=>rtrim($rountinfo,"<br>")));                                
                        }  
                        $c++;                                   
                    }                     
                }
            }                   
            
            //Selected Days
            if(isset($Days_info) && !empty($Days_info))
            {
                foreach($Days_info as $key=>$val)
                {
                    if($val!='')
                    {
                        $Days_info[$val]=$val;
                    }
                    else
                    {
                        $Days_info['all']='all';
                    }
                }
            } 
            else
            {
                $Days_info= array();
            }            
            
            return $this->render('inventory/accommodation.html.twig', [
                'search' => $request->query->all(),
                'flights' => $flights,
                'flight_sell_rules' => $flightSellRules,
                'accommodations' => $accommodations1,
                'stopsales_notes' => $stopsalesNotes,
                'mc_exception' => $mcException,
                'flightinfo' =>$flight_sell_det,
                'flights2' => $flights2,
                'day'=>$Days_info
            ]);
        }
    }
    
    private function allocTableAnalysis($allocMatrix)
    {
        $increaseFlag = false;
        $decreaseFlag = false;
        foreach ($allocMatrix as $_idx => $__alloc) {
            $_next = isset($allocMatrix[$_idx+1]) ? $allocMatrix[$_idx+1] : null;
            if (is_null($_next)) {
                continue;
            }
            if ($__alloc < $_next) {
                $increaseFlag = true;
            }
            if ($__alloc > $_next) {
                $decreaseFlag = true;
            }
        }

        return [$increaseFlag, $decreaseFlag];
    }




    private function cmpName($a, $b)
    {
        if ($a['name'] == $b['name']) {
            return 0;
        }
        return ($a['name'] < $b['name']) ? -1 : 1;
    }

}

function is_guarantee($rm_cd) {
    return in_array(substr(strtolower($rm_cd), 0, 4), ['dbg0', '1apg', '2apg', 'fag0', 'stg0', 'twg0', 'sug0', 'bgg0', 'tpg0']);
}

function is_remove($ht_cd) {
    return in_array(strtoupper($ht_cd), [
        'ACE035','ACE038','AGP043','AGP057','ALC015','AYT078','AYT079','BCN012','BCN056','BJV023',
        'BOJ014','BOJ015','BTS005','BTS006','BUD019','BUD020','CDL012','CDL014','CTA021','CTE039',
        'CTE041','CYP010','DBV042','DBV045','DLM013','DLM019','DXB029','DXB030','EDI004','EDI005',
        'FAO036','FAO037','FCO009','FCO010','FNC036','FNC038','FUE031','FUE038','HRG020','HRG021',
        'JER010','JER011','JTR021','JTR022','KLX009','KLX015','LEI031','LEI034','LID008','LIS014',
        'LIS015','LJU010','LJU011','LPA055','LPA062','LYR003','MAH017','MAH025','MLA009','MLA010',
        'MON010','NAP044','OPO009','OPO010','PDL029','PDL033','PMI072','PMI076','PRG016','PRG017',
        'PUJ018','PUJ021','PUY036','PUY037','RHO042','RHO043','RKT008','RKT010','SMI013','SPC017',
        'SPC020','STT009','SUF022','SUF024','SVQ005','SVQ006','TFS065','TFS075','VAR019','VAR020',
        'VLC010','VLC011','VRN005','VRN006','ZTH009','ZTH015',
        'BCN087','BCN088','BCN089','CAT013','DXB034','FNC052','FNC054','FNC056','FNC061','FNC063',
        'FNC065','LEI026','LEI052','LIS033','PDL038','RKT012','RKT014',
        'BLL005']);
}

function debug($time_start, $line) {
    $time_end = microtime(true);
    $time = $time_end - $time_start;
//    print "Used " . $time . " seconds to get to line " . $line ."<br>\n";
}
