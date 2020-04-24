<?php

namespace AppBundle\Controller;

use AppBundle\Service\Atcore;
use DateInterval;
use DatePeriod;
use DateTime;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use GuzzleHttp\Client;

class StatusController extends Controller
{
    const DATE_FORMAT = 'Y-m-d';

    /**
     * @Route("/status", name="status")
     */
    public function statusIndexAction(Request $request)
    {
        $status = [];

        if ($request->query->has('prom_cd') && $request->query->has('accom_cd') &&
            $request->query->has('rm_cd') && $request->query->has('st_dt') &&
            $request->query->has('stay')) {

            $promCd     = strtoupper($request->query->get('prom_cd'));
            $depCd      = strtoupper($request->query->get('dep_cd'));
            $accomCd    = strtoupper($request->query->get('accom_cd'));
            $rmCd       = strtoupper($request->query->get('rm_cd'));
            $stDt       = new \Datetime($request->query->get('st_dt'));
            $stay       = intval($request->query->get('stay'));
            $endDt      = new \Datetime($request->query->get('st_dt'));
            $endDt->modify('+' . ($stay - 1) . ' days');
            $nAdu       = intval($request->query->get('n_adu'));
            $nChd       = intval($request->query->get('n_chd'));
            $fltNb      = strtoupper($request->query->get('flt_nb'));

            if (!$rmCd) {
                return $this->redirectToRoute('status_search', $request->query->all());
            }

            $conn = $this->get('doctrine.dbal.atcore_connection');

            $atcore = $this->get('app.atcore');

            $atcore->promCd     = $promCd;
            $atcore->stDt       = $stDt;
            $atcore->endDt      = $endDt;
            $atcore->dir        = 'OUT';
            $atcore->stay       = $stay;
            $atcore->accomCd    = $accomCd;
            $atcore->rmCd       = $rmCd;

            $endDt->modify('+7 days');
            $atcore->endDt = $endDt;

            /* ACCOMMODATION */
            $results = $atcore->getAccommodationInventoryResults();

            $endDt->modify('-7 days');
            $atcore->endDt = $endDt;

            $inventoryLoaded    = count($results) ? true: false;
            $units              = null;
            $unitsMatrix        = [];
            $unitsAvailable     = null;

            $inventoryTable     = [];

            $minStay = 0;
            $maxStay = 999;

            $releaseEarliest    = null;
            $releaseDates       = [];
            $released           = false;

            $stDaySts   = true;
            $altSts     = true;

            $accommodationName  = $unitName = null;
            $rmGpCd             = null;

            $masterAccomCd  = null;
            $noSharer       = false;

            $accomId = null;

            $sellStcId  = null;
            $sellUnitId = null;

            $day = 0;

            foreach ($results as $result) {

                if (is_null($masterAccomCd) && !is_null($result['MASTER_ACCOM_CD'])) {
                    $masterAccomCd = $result['MASTER_ACCOM_CD'];
                }

                if (is_null($accommodationName)) {
                    $accommodationName = $result['ACCOM'];
                }

                if (is_null($unitName)) {
                    $unitName = $result['RM'];
                }

                if (is_null($accomId)) {
                    $accomId = $result['ACCOM_ID'];
                }

                if (is_null($sellStcId)) {
                    $sellStcId = $result['SELL_STC_ID'];
                }

                if (is_null($sellUnitId)) {
                    $sellUnitId = $result['SELL_UNIT_ID'];
                }

                if (is_null($rmGpCd)) {
                    $rmGpId = $result['RM_GP_CD'];
                }

                // No sharer found! Break!
                if (is_null($result['STK_SUB_ID']) && is_null($masterAccomCd)) {
                    $noSharer = true;
                    break;
                }

                $alloc      = $result['ALT_ALLOC'];
                $bkd        = $result['ALT_OB'];
                $excAlloc   = $result['ALT_EXC_SD_ALLOC'];
                $excBkd     = $result['ALT_EXC_SD_OB'];
                $mainRemain = $result['ALT_MAIN_REMAIN'];

                $invDt = new \Datetime($result['INV_DT']);

                if ($stDt == $invDt && $result['ST_DAY_STS'] == 'CL') {
                    $stDaySts = false;
                }

                if ($result['ALT_STS'] == 'CL') {
                    $altSts = false;
                }

                $multiplyOfSeven = (!($stay % 7) && $day > 0 && !($day % 7)) ? true : false;

                $day++;

                if ($stDt == $invDt || $multiplyOfSeven) {
                    $unitsOnThisDay =  $alloc - $bkd;
                } else {
                    $unitsOnThisDay =  $alloc - $bkd - $excAlloc + $excBkd;
                }
                
                if ($unitsOnThisDay > $mainRemain) {
                    $unitsOnThisDay = $mainRemain;
                    $mainIsLower = true;
                } else {
                    $mainIsLower = false;
                }

                $inventoryTable[$invDt->format('Y-m-d')] = [
                                'alloc' => $alloc,
                                'bkd' => $bkd,
                                'exc_alloc' => $excAlloc,
                                'exc_bkd' => $excBkd,
                                'units' => $unitsOnThisDay,
                                'main_is_lower' => $mainIsLower,
                                'alt_sts' => $result['ALT_STS'],
                                'bookable_units' => null
                            ];
          
                if (is_null($units)) {
                    $units = $unitsOnThisDay;
                } else {
                    $units = min($units,$unitsOnThisDay);
                }

                if ($invDt <= $endDt) {
                    $unitsMatrix[$invDt->format('Y-m-d')] = $unitsOnThisDay;
                }

                $minStay = max($minStay, $result['MIN_STAY']);
                $maxStay = min($maxStay, $result['MAX_STAY']);

                $releaseTime = strtotime($result['ALT_REL_DT']);

                if (is_null($releaseEarliest)) {
                    $releaseEarliest = $releaseTime;
                } else {
                    $releaseEarliest = min($releaseEarliest, $releaseTime);
                }
                if ($releaseTime < time()) {
                    $released = true;
                    $releaseDates[$result['INV_DT']] = $result['ALT_REL_DT'];
                }
            }


            $loopInterval = DateInterval::createFromDateString('1 day');

            $oldInventoryTable = $inventoryTable;

            $inventoryTable = [];

            $invDateMatrix = [];
            $missingDatesData = [];
            // Loop through every column / date
            foreach ($oldInventoryTable as $invDt => $_bookingData) {

                $_today = new DateTime($invDt);
                $invDateMatrix[$invDt] = [];

                if ($_today > $endDt) {
                    break;
                }

                $_end = clone $_today;
                $_end->modify('+7 day');
                $_period = new DatePeriod($_today, $loopInterval, $_end);

                $noExclusives   = true;
                $excSum         = 0;
                $allocMatrix    = [];

                // For each day look 7 days ahead. 
                foreach ($_period as $_date) {

                    $_dtFt = $_date->format(self::DATE_FORMAT);

                    $invDateMatrix[$invDt][] = $_dtFt;

                    if (!(isset($oldInventoryTable[$_dtFt]) &&
                        is_array($oldInventoryTable[$_dtFt]) &&
                        isset($oldInventoryTable[$_dtFt]['alloc'])))
                    {
                        $missingDatesData[$invDt] = $_dtFt;
                        continue 1;
                    }

                    $_allotment     = (int) $oldInventoryTable[$_dtFt]['alloc'];
                    $allocMatrix[]  = $_allotment;

                    if (!is_null($oldInventoryTable[$_dtFt]['exc_alloc'])) {
                        $noExclusives = false;
                    }
                    
                    if ($_date == $_today) continue 1;
                        
                    $excSum -= $oldInventoryTable[$_dtFt]['exc_alloc'];
                }

                // Create flags to see if the alloc data increases and or decreases.
                // This is done because when alloc data increases the calculation is different.
                list($increaseFlag, $decreaseFlag) = $this->allocTableAnalysis($allocMatrix);

                $allotment = min($allocMatrix);
                if ($increaseFlag && !$decreaseFlag) {
                    $allotment = max($allocMatrix);
                }

                $excSum -= $_bookingData['exc_bkd'];

                $excSum += $allotment;

                /**
                 * Save today's data in InventoryTable.
                 * If there's no exclusivity at all simply use the lowest value
                 * from $unitsMatrix which is an array of theoretical units.
                 * If not, use either null if there's no exclusivity on today,
                 * or use the exc sum.
                 */                
                
                $todayBookableUnits = null;
                if (!is_null($_bookingData['exc_alloc'])) {     
                    //$todayBookableUnits = $excSum;
                    $todayBookableUnits = $_bookingData['alloc']-$_bookingData['bkd']-$_bookingData['exc_alloc']+$_bookingData['exc_bkd'];                                        
                } else if ($noExclusives === true) {                   
                     //$todayBookableUnits = min($unitsMatrix);
                     $todayBookableUnits = $_bookingData['alloc']-$_bookingData['bkd'];                                         
                }

               
                $inventoryTable[$invDt] = $oldInventoryTable[$invDt];
                $inventoryTable[$invDt]['bookable_units'] = $todayBookableUnits;                                
                
               /**
                 * Set the "global" units available.
                 * When it's the selected start date and there's exclusivity,
                 * force the bookable units to be the bookable units for this date.
                 * If not, use the lowest value of either the existing value or
                 * today's bookable units.
                 */
                if ($_today == $stDt && $noExclusives === false) {
                    $unitsAvailable = $todayBookableUnits;
                } else {
                    if (!is_null($todayBookableUnits) && $noExclusives) {
                        $unitsAvailable = is_null($unitsAvailable) ? $todayBookableUnits : min($unitsAvailable, $todayBookableUnits);
                    }
                }
            }

            


            /* STOP SALES */
            $results = $atcore->getStopsalesByAccomResults();

            $stopsale = count($results) ? true : false;
            $stopsales = [];
            foreach ($results as $result) {
                $stopsales[] = [
                    'st_dt' => $result['ST_DT'],
                    'end_dt' => $result['END_DT'],
                    'text' => $result['TEXT'],
                    'rm_cd' => $result['RM_CD'],
                    'prom_cd' => $result['PROM_CD'],
                ];
            }
 
            /* MEMORY CACHE */
            $atcore->endDt  = clone $stDt;
            $atcore->endDt->modify('+1 day');
            $atcore->depCd  = $depCd;
            $atcore->nAdu   = $nAdu;
            $atcore->nChd   = $nChd;

            $memoryCacheLink = $atcore->getMemoryCacheLink();

            $client = new Client([
                'timeout'  => 1.0,
            ]);
            $response = $client->request('GET', $memoryCacheLink, [
                'verify' => false,
            ]);
            $memoryCacheXml = $response->getBody()->getContents();

            $dom = new \DOMDocument();
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;
            $dom->loadXML($memoryCacheXml);

            $memoryCacheXmlFormatted = $dom->saveXML();

            $simpleXml = simplexml_load_string($memoryCacheXml);            

            $memoryCacheFound = (int)$simpleXml->Result[0]['Count'];

            /* SEARCHINCBUILDUNIT */
            $searchincbuildunits = [];
            if ($sellStcId && $sellUnitId) {
                $sql = "SELECT
                            *
                        FROM
                            atcomres.ar_searchincbuildunit sibu
                        WHERE
                            sibu.sell_stc_id = :sell_stc_id
                                AND
                            sibu.sell_unit_id = :sell_unit_id";

                $stmt = $conn->prepare($sql);
                $stmt->bindValue('sell_stc_id', $sellStcId);
                $stmt->bindValue('sell_unit_id', $sellUnitId);
                $stmt->execute();
                $searchincbuildunits = $stmt->fetchAll();
            }


            /* SEARCHINCBUILDUNIT_HIS */
            $searchincbuildunitshis = [];
            if ($sellStcId && $sellUnitId) {
                $sql = "SELECT
                            *
                        FROM
                            atcomres.ar_searchincbuildunit_his sibuh
                        WHERE
                            sibuh.sell_stc_id = :sell_stc_id
                                AND
                            sibuh.sell_unit_id = :sell_unit_id
                        ORDER BY
                            sibuh.last_dt DESC
                        FETCH FIRST 10 ROWS ONLY";

                $stmt = $conn->prepare($sql);
                $stmt->bindValue('sell_stc_id', $sellStcId);
                $stmt->bindValue('sell_unit_id', $sellUnitId);
                $stmt->execute();
                $searchincbuildunitshis = $stmt->fetchAll();
            }


            /* SEARCHINCLUSIVEUNIT */
            $searchinclusiveunits = [];
            if ($sellStcId && $sellUnitId) {
                $sql = "SELECT
                            *
                        FROM
                            atcomres.ar_searchinclusiveunit siu
                        WHERE
                            siu.sell_stc_id = :sell_stc_id
                                AND
                            siu.sell_unit_id = :sell_unit_id
                                AND
                            siu.dt = :st_dt
                                AND
                            siu.stay = :stay";

                $stmt = $conn->prepare($sql);
                $stmt->bindValue('sell_stc_id', $sellStcId);
                $stmt->bindValue('sell_unit_id', $sellUnitId);
                $stmt->bindValue('st_dt', $stDt, 'date');
                $stmt->bindValue('stay', $stay);
                $stmt->execute();
                $searchinclusiveunits = $stmt->fetchAll();
            }

            /* FLIGHT */
            $flights = [
                'remain' => 0,
                'inv_remain' => 0,
                'sts' => 'OFF',
                'routes' => [],
            ];

            $results = $atcore->getCachedFlightsForAccom();
            foreach ($results as $result) {
                $excluded = false;
                $included = true;
                $routeRemain    = min($result['OUT_REMAIN'], $result['IN_REMAIN']);
                $routeSts       = ($result['OUT_STS'] == 'ON' && $result['IN_STS'] == 'ON') ? 'ON' : 'OFF';

                $invOutRemain   = $result['OUT_ALT'] - $result['OUT_OB'];
                $invInRemain    = $result['IN_ALT'] - $result['IN_OB'];
                $invRemain      = min($invOutRemain, $invInRemain);

                if ($routeSts == 'ON') {
                    $flights['remain'] += $routeRemain;
                    $flights['inv_remain'] += $invRemain;
                    $flights['sts'] = 'ON';
                }


                if ($routeRemain) {
                    /* Sell Rule Restriction */
                    $sql = "SELECT
                                tiysd.land_rstn,
                                tiysdl.land_tp,
                                listagg(tiysdl.tp_id, ',') within group(order by tiysdl.tp_id) tp_ids
                            FROM
                                ATCOMRES.AR_TRANSINVYIELDSELL tiys
                                    INNER JOIN ATCOMRES.AR_TRANSINVYIELDSELLDETAIL tiysd
                                        ON tiysd.trans_inv_yield_sell_id = tiys.trans_inv_yield_sell_id
                                        INNER JOIN ATCOMRES.AR_TRANSINVYEILDSELLDTLLAND tiysdl
                                            ON tiysdl.trans_inv_yeild_sell_dtl_id = tiysd.trans_inv_yield_sell_dtl_id
                            WHERE
                                tiys.trans_sell_rule_id = :tsr_id
                                    AND
                                tiys.box_id = :box_id
                            GROUP BY
                                tiysd.land_rstn, tiysdl.land_tp";

                    $stmt = $conn->prepare($sql);
                    $stmt->bindValue('tsr_id', $result['OUT_TRANS_SELL_RULE_ID']);
                    $stmt->bindValue('box_id', $result['OUT_CUR_SRCH_BOX_ID']);
                    $stmt->execute();
                    $rstns = $stmt->fetchAll();

                    foreach ($rstns as $rstn) {
                        $tpIds = explode(',', $rstn['TP_IDS']);
                        if ($rstn['LAND_TP'] == 'ACC') {
                            if ($rstn['LAND_RSTN'] == 'EXCL' && in_array($accomId, $tpIds)) {
                                $excluded = true;
                            }
                            if ($rstn['LAND_RSTN'] == 'INCL' && !in_array($accomId, $tpIds)) {
                                $included = false;
                            }
                        }
                    }
                }


                $flights['routes'][] = [
                    'remain' => $routeRemain,
                    'inv_remain' => $invRemain,
                    'sts' => $routeSts,
                    'included' => $included,
                    'excluded' => $excluded,
                    'out' => [
                        'remain' => $result['OUT_REMAIN'],
                        'sts' => $result['OUT_STS'],
                        'flt_nb' => $result['OUT_ROUTE_NUM'],
                        'trans_cd' => $result['OUT_TRANS_HEAD_CD'],
                        'inv_remain' => $invOutRemain,
                    ],
                    'in' => [
                        'remain' => $result['IN_REMAIN'],
                        'sts' => $result['IN_STS'],
                        'flt_nb' => $result['IN_ROUTE_NUM'],
                        'trans_cd' => $result['IN_TRANS_HEAD_CD'],
                        'inv_remain' => $invInRemain,
                    ],
                ];
            }          
            /* STATUS array */
            $status = [
                'missingDatesData' => $missingDatesData,
                'invDateMatrix' => $invDateMatrix,
                'accommodation' => [
                    'master_accom_cd' => $masterAccomCd,
                    'no_sharer' => $noSharer,
                    'names' => [
                        'accommodation' => $accommodationName,
                        'unit' => $unitName,
                    ],
                    'inventory' => [
                        'loaded' => $inventoryLoaded,
                        'units' => $units,
                        'bookable_units' => $unitsAvailable,
                        'table' => $inventoryTable,
                        'st_day_sts' => $stDaySts,
                        'alt_sts' => $altSts,
                    ],
                    'stopsales' => [
                        'stopsale' => $stopsale,
                        'stopsales' => $stopsales,
                    ],
                    'release' => [
                        'released' => $released,
                        'dates' => $releaseDates,
                        'earliest' => date('c', $releaseEarliest),
                    ],
                    'stay' => [
                        'valid' => ($stay >= $minStay && $stay <= $maxStay) ? true : false,
                        'min_stay' => $minStay,
                        'max_stay' => $maxStay,
                    ],
                    'searchincbuildunits' => $searchincbuildunits,
                    'searchincbuildunitshis' => $searchincbuildunitshis,
                    'searchinclusiveunits' => $searchinclusiveunits,
                ],
                'flights' => $flights,
                'memory_cache' => [
                    'link' => $memoryCacheLink,
                    'xml' => $memoryCacheXmlFormatted,
                    'found' => $memoryCacheFound,
                ],
                'buildjobs' => [
                    'rmGpId' => @$rmGpId
                ]
            ];

        }


        return $this->render('status/index.html.twig', [
            'search' => $request->query->all(),
            'status' => $status,
        ]);
    }


    /**
     * @Route("/status/search", name="status_search")
     */
    public function statusSearchAction(Request $request)
    {
        $promCd     = strtoupper($request->query->get('prom_cd'));
        $depCd      = strtoupper($request->query->get('dep_cd'));
        $accomCd    = strtoupper($request->query->get('accom_cd'));
        $rmCd       = strtoupper($request->query->get('rm_cd'));
        $stDt       = new \Datetime($request->query->get('st_dt'));
        $stay       = intval($request->query->get('stay'));
        $nAdu       = intval($request->query->get('n_adu'));
        $nChd       = intval($request->query->get('n_chd'));
        $fltNb      = strtoupper($request->query->get('flt_nb'));

        $conn = $this->get('doctrine.dbal.atcore_connection');

        $sql = "SELECT
                    stc.stc_stk_id
                FROM
                    ATCOMRES.AR_STATICSTOCK stc
                WHERE
                    stc.cd = :accom_cd";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue('accom_cd', $accomCd);
        $stmt->execute();
        $result = $stmt->fetch();
        if ($result) {
            $sql = "SELECT
                        rm.cd RM_CD,
                        COALESCE(ovrrm.name, rm.name) RM
                    FROM
                        ATCOMRES.AR_STATICSTOCK stc
                            INNER JOIN ATCOMRES.AR_SELLSTATIC sell
                                ON sell.stc_stk_id = stc.stc_stk_id
                                INNER JOIN ATCOMRES.AR_PROMOTION pro
                                    ON pro.prom_id = sell.prom_id
                                INNER JOIN ATCOMRES.AR_SELLUNIT sellu
                                    ON sellu.sell_stc_id = sell.sell_stc_id
                                    INNER JOIN ATCOMRES.AR_ROOM rm
                                        ON rm.rm_id = sellu.rm_id
                                    LEFT JOIN ATCOMRES.AR_ROOM ovrrm
                                        ON ovrrm.rm_id = sellu.or_rm_id
                    WHERE
                        pro.cost_prc_mth = 'CO'
                            AND
                        (:accom_cd is null OR stc.cd = :accom_cd)";

            $stmt = $conn->prepare($sql);
            $stmt->bindValue('accom_cd', $accomCd);
            $stmt->execute();
            $results = $stmt->fetchAll();

            return $this->render('status/search_room.html.twig', [
                'search' => $request->query->all(),
                'results' => $results,
            ]);

        } else {

            $results = [];

            if (strlen($accomCd) > 2) {

                $sql = "SELECT
                            stc.cd ACCOM_CD, stc.name ACCOM
                        FROM
                            ATCOMRES.AR_STATICSTOCK stc
                        WHERE
                            UPPER(stc.name) LIKE UPPER(:accom_cd)
                                OR
                            UPPER(stc.cd) LIKE UPPER(:accom_cd)";
                $stmt = $conn->prepare($sql);
                $stmt->bindValue('accom_cd', '%' . $accomCd . '%');
                $stmt->execute();
                $results = $stmt->fetchAll();

            }

            return $this->render('status/search_accom.html.twig', [
                'search' => $request->query->all(),
                'results' => $results,
            ]);

        }
    }
    
    /**
     * @Route("/status/options", name="status_options")
     */
    public function statusOptionsAction(Request $request)
    {
        $conn = $this->get('doctrine.dbal.atcore_connection');

        $sql = "SELECT
                    res.res_id, res.origin_dt, res.first_st_dt, res.last_end_dt
                FROM
                    ATCOMRES.AR_RESERVATION res
                WHERE
                    res.bkg_sts = 'OPT'";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll();

        return $this->render('status/option.html.twig', [
            'results' => $results,
        ]);
    }


    /**
     * @Route("/status/reservations", name="status_reservations")
     */
    public function statusReservationsAction(Request $request)
    {
        $promCd     = strtoupper($request->request->get('prom_cd'));
        $accomCd    = strtoupper($request->request->get('accom_cd'));
        $rmCd       = strtoupper($request->request->get('rm_cd'));
        $stDt       = new \Datetime($request->request->get('st_dt'));
        if ($request->request->has('end_dt')) {
            $endDt = new \Datetime($request->request->get('end_dt'));
        } else {
            $stay   = $request->request->get('stay');
            $endDt  = clone $stDt;
            $endDt->modify('+' . $stay . ' days');
        }

        $conn = $this->get('doctrine.dbal.atcore_connection');

        $sql = "SELECT
                    res.res_id, res.origin_dt,
                    ser.st_dt, ser.end_dt
                FROM
                    ATCOMRES.AR_RESERVATION res
                        INNER JOIN ATCOMRES.AR_RESSERVICE ser
                            ON ser.res_id = res.res_id
                            AND ser.ser_tp = 'ACC'
                            AND ser.ser_sts = 'CON'
                            INNER JOIN ATCOMRES.AR_SELLSTATIC sell
                                ON sell.sell_stc_id = ser.ser_id
                                    INNER JOIN ATCOMRES.AR_STATICSTOCK stc
                                        ON stc.stc_stk_id = sell.stc_stk_id
                            INNER JOIN ATCOMRES.AR_RESSUBSERVICE subser
                                ON subser.res_ser_id = ser.res_ser_id
                                    INNER JOIN ATCOMRES.AR_SELLUNIT sellu
                                        ON sellu.sell_unit_id = subser.inv_id
                                        INNER JOIN ATCOMRES.AR_ROOM rm
                                            ON rm.rm_id = sellu.rm_id

                        INNER JOIN ATCOMRES.AR_PROMOTION prom
                            ON prom.prom_id = res.prom_id
                WHERE
                    res.bkg_sts IN ('OPT', 'BKG')
                        AND
                    prom.cd = :prom_cd
                        AND
                    stc.cd = :accom_cd
                        AND
                    rm.cd = :rm_cd
                        AND
                    ser.st_dt < :end_dt
                        AND
                    ser.end_dt > :st_dt
                ORDER BY
                    ser.st_dt, res.res_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue('prom_cd', $promCd);
        $stmt->bindValue('accom_cd', $accomCd);
        $stmt->bindValue('rm_cd', $rmCd);
        $stmt->bindValue('st_dt', $stDt, 'date');
        $stmt->bindValue('end_dt', $endDt, 'date');
        $stmt->execute();
        $results = $stmt->fetchAll();

        if ($results) {
            $firstStDt = $lastEndDt = null;

            foreach ($results as $result) {
                $resStDt = new \Datetime($result['ST_DT']);
                $resEndDt = new \Datetime($result['END_DT']);

                if (is_null($firstStDt) || $firstStDt > $resStDt) {
                    $firstStDt = $resStDt;
                }
                if (is_null($lastEndDt) || $lastEndDt < $resEndDt) {
                    $lastEndDt = $resEndDt;
                }
            }

            $lastEndDt->modify('-1 day');
            $daysBetween = $lastEndDt->diff($firstStDt);

            return $this->render('status/reservations.html.twig', [
                'results' => $results,
                'first_st_dt' => $firstStDt,
                'last_end_dt' => $lastEndDt,
                'days_between' => $daysBetween->format('%a'),
                'st_dt' => $stDt,
                'end_dt' => $endDt,
            ]);
        } else {
            return $this->render('status/reservations-noresults.html.twig', [
            ]);
        }
    }

    /**
     * @Route("/status/buildjobs", name="status_buildjobs")
     */
    public function status_buildjobs(Request $request)
    {
        $promCd     = strtoupper($request->request->get('prom_cd'));
        $accomCd    = strtoupper($request->request->get('accom_cd'));
        $rmCd       = strtoupper($request->request->get('rm_cd'));
        $depCd      = strtoupper($request->request->get('dep_cd'));
        $stDt       = new \Datetime($request->request->get('st_dt'));
        $rmGpId     = strtoupper($request->request->get('rmGpId'));
        $stay       = $request->request->get('stay');

        if ($request->request->has('end_dt')) {
            $endDt = new \Datetime($request->request->get('end_dt'));
        } else {
            $endDt = clone $stDt;
            $endDt->modify('+' . $stay . ' days');
        }

        /** @var Atcore $atcore */
        $atcore = $this->get('app.atcore');
        $conn = $this->get('doctrine.dbal.atcore_connection');

        $atcore->promCd     = $promCd;
        $atcore->accomCd    = $accomCd;
        $atcore->rmCd       = $rmCd;
        $atcore->depCd      = $depCd;
        $atcore->stDt       = $stDt;
        $atcore->endDt      = $endDt;
        $atcore->stay       = $stay;


        /* ACCOMMODATION PRICE */
        // Default values
        $accomPrice = [
            'found' => false,
            'mod_dt_tm' => null,
            'details' => [],
        ];
        $accomPrcDetIds = [];

        if (!empty($rmGpId)) {
            list($accomPrice, $accomPrcDetIds) = $atcore->getAccommodationPrice($rmGpId);
        }

        /* PRICE DEFINITION */
        $priceDefinitions = [
            'found' => false,
            'mod_dt_tm' => null,
            'details' => [],
        ];

        if (count($accomPrcDetIds)) {

            $results = $atcore->getPriceDefinition($accomPrcDetIds); 

            foreach ($results as $result) {
                if (!$priceDefinitions['found']) {
                    $priceDefinitions['found'] = true;
                }

                if (is_null($priceDefinitions['mod_dt_tm']) || $priceDefinitions['mod_dt_tm'] < $result['MOD_DT_TM']) {
                    $priceDefinitions['mod_dt_tm'] = $result['MOD_DT_TM'];
                }

                $priceDefinitions['details'][] = $result;
            }
        }

        return $this->render('status/buildjobs.html.twig', [
            'accom_price' => $accomPrice,
            'price_definition' => $priceDefinitions,
        ]);
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
}