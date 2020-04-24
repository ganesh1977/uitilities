<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

use Primera\AtcomResBundle\Entity\WebService as WS;
use Primera\AtcomResBundle\Entity\WebService\FDL;
use AppBundle\Entity\FlightLogPricedefinitionBatch;

use GuzzleHttp\Client;

class FlightController extends Controller
{
    const ERR_NOT_FOUND     = 1;
    const ERR_DEP           = 2;
    const ERR_ARR           = 4;
    const ERR_ALT           = 8;
    const ERR_BKD           = 16;
    const ERR_ALT_MM        = 32;
    const ERR_BKD_MM        = 64;
    const ERR_FLIGHT_NO     = 128;
    
    const ERR_LVL_0 = 0;//'success';
    const ERR_LVL_1 = 1;//'info';
    const ERR_LVL_2 = 2;//'warning';
    const ERR_LVL_3 = 3;//'danger';    
    
    /**
     * @Route("inventory/flight/sellrules/log", name="flight_sell_log")
     */
    public function flightPackagesLogBatchModalAction(Request $request){           
        
        $from_date='';        
        //Date checking in the textbox is empty or not
        $ff_date = $request->query->get('fdate');
        if($ff_date != '')
        {                    
            $fdate = new \DateTime($request->query->get('fdate'));        
            $fdate_list = (array)$fdate;
            $from_date = $fdate_list['date'];
        }
        
        $to_date = '';
        $tt_date = $request->query->get('tdate');
        if($tt_date != '')
        {
            $tdate = new \DateTime($request->query->get('tdate'));        
            $todate = (array)$tdate;
            $to_date = $todate['date'];
        }        
        $where = '';
        // Checking the date 
        if( ($ff_date!='') && ($tt_date!='') )
        {
            $where = " where cycleDt between date('".$from_date."') AND date('".$to_date."')";
        }           
        
        $sql = "SELECT "
                . "fl.id"
                . ",fl.prcVal"
                . ",fl.hide_empty"
                . ",fl.hide_off"
                . ",fl.always_show"
                . ",fl.m_headCd"
                . ",fl.depCd"
                . ",fl.arrCd"
                . ",fl.headCd"
                . ",fl.stDt"
                . ",fl.endDt"
                . ",fl.direction"
                . ",fl.CycleDt"
                . ",fl.rule"
                . ",ap.username"
                . ",fl.change_status"
                . ",fl.update_dt_tm "
                . "FROM `flight_log_pricedefinition_change` as fl "
                . "inner join app_users as ap on fl.batch_id = ap.id $where "
                . "order by fl.update_dt_tm desc"; 
        
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();            
        $user_stmt = $doctrine->getEntityManager() 
                      ->getConnection()
                        ->prepare($sql);
        $user_stmt->execute();    
        $data = $user_stmt->fetchAll();   
        
        return $this->render('flight/sellrules/flight_log_modal.html.twig', [
            'batchData' => $data,
            'from_date'=>$from_date,
            'to_date'=>$to_date            
        ]); 
    }
        
    /**
     * @Route("/inventory/flight", name="inventory_flight")
     */
    public function flightsAction(Request $request)
    {        
        date_default_timezone_set('UTC');
        
        $request = Request::createFromGlobals();
        if ($request->query->has('from')) {
            $dateFrom = date('Y-m-d', strtotime($request->query->get('from')));
        } else {
            $dateFrom = date('Y-m-d');
        }
        if ($request->query->has('to')) {
            $dateTo = date('Y-m-d', strtotime($request->query->get('to')));
        } else {
            $dateTo = date('Y-m-d', time()+60*60*24*30);
        }
        if ($request->query->has('airport')) {
            $airport = preg_replace('/[^A-Z]+/', '', strtoupper($request->query->get('airport')));
        } else {
            $airport = null;
        }
        
        
        $row = 1;
        $sep = ',';
        $file = $this->container->getParameter('tour_operator_allotment_csv_path');
        $debug = 0;

        if ($request->query->has('debug')) {
            $debug = 1;
        }

        if (($handle = fopen($file, 'r')) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, $sep)) !== FALSE) {
                if ($debug) {
                    print '<pre>';
                    print_r($data);
                    die();
                }

                if ($row++ == 1) {
                    continue;
                }
                
                $depUtc         = $data[0];
                $depLoc         = $data[1];
                $arrUtc         = $data[2];
                $arrLoc         = $data[3];
                $flightNo       = $data[4];
                $depPt          = $data[5];
                $arrPt          = $data[6];
                $sharer         = $data[7];
                $allotment      = $data[8];
                $reserved       = $data[9];

                $depUtcTs = strtotime($depUtc);
                $depLocTs = strtotime($depLoc);
                $arrUtcTs = strtotime($arrUtc);
                $arrLocTs = strtotime($arrLoc);
                
                $dateUTC = date('Y-m-d', $depUtcTs);
                $route = $depPt . '-' . $arrPt;
                if (!isset($dates[$dateUTC])) {
                    $dates[$dateUTC] = [];
                }
                if (!isset($dates[$dateUTC][$route])) {
                    $dates[$dateUTC][$route] = [];
                }
                if (!isset($dates[$dateUTC][$route][$flightNo])) {
                    $dates[$dateUTC][$route][$flightNo] = [
                        'BT' => ['allotment' => 0, 'reserved' => 0],
                        'SR' => ['allotment' => 0, 'reserved' => 0],
                        'LM' => ['allotment' => 0, 'reserved' => 0],
                        'SO' => ['allotment' => 0, 'reserved' => 0],
                        'HF' => ['allotment' => 0, 'reserved' => 0],
                        'ST' => ['allotment' => 0, 'reserved' => 0],
                        'allotment' => 0,
                        'reserved' => 0,
                        'dep_utc' => $depUtc,
                        'dep_loc' => $depLoc,
                        'arr_utc' => $arrUtc,
                        'arr_loc' => $arrLoc
                    ];
                }
                
                $dates[$dateUTC][$route][$flightNo]['allotment'] += $allotment;
                $dates[$dateUTC][$route][$flightNo]['reserved'] += $reserved;              

                $dates[$dateUTC][$route][$flightNo][$sharer]['allotment'] += $allotment;
                $dates[$dateUTC][$route][$flightNo][$sharer]['reserved'] += $reserved;              
            }
            fclose($handle);
        }
        
        $conn = $this->get('doctrine.dbal.atcore_connection');
        
        $sql = "SELECT
                    TO_CHAR(tis.utc_dep_dt_tm, 'YYYY-MM-DD\"T\"HH24:MI:SS\"Z\"') UTC_Departure_Dt,
                    pt1.pt_cd Departure_Pt, pt2.pt_cd Arrival_Pt,
                    tis.route_num Flight_No,
                    tis.alt AS sector_alt, tis.bkd AS sector_bkd,
                    ti.alt, ti.bkd, tsr.rule
                FROM
                    ATCOMRES.AR_TRANSHEAD th
                        INNER JOIN ATCOMRES.AR_TRANSROUTE tr
                        ON tr.trans_head_id = th.trans_head_id
                            INNER JOIN ATCOMRES.AR_TRANSINVROUTE tir
                            ON tir.trans_route_id = tr.trans_route_id
                                INNER JOIN ATCOMRES.AR_TRANSINVSECTOR tis
                                ON tis.trans_inv_sec_id = tir.dep_sec_id
                                INNER JOIN ATCOMRES.AR_TRANSINVSHARERYIELD tisy
                                ON tisy.yield_inv_id = tir.yield_inv_id
                                    INNER JOIN ATCOMRES.AR_TRANSINVYIELDSELL tiys
                                    ON tiys.trans_inv_sharer_yield_id = tisy.trans_inv_sharer_yield_id
                                        INNER JOIN ATCOMRES.AR_TRANSINVENTORY ti
                                        ON ti.box_id = tiys.box_id
                                        INNER JOIN ATCOMRES.AR_TRANSSELLRULE tsr
                                        ON tsr.trans_sell_rule_id = tiys.trans_sell_rule_id
                            INNER JOIN ATCOMRES.AR_POINT pt1
                            ON pt1.pt_id = tr.dep_pt_id
                            INNER JOIN ATCOMRES.AR_POINT pt2
                            ON pt2.pt_id = tr.arr_pt_id
                WHERE 
                    (
                        tis.carrier_id IN (2261273,2594705)
                            OR
                        (
                            tis.carrier_id IS NULL
                                AND
                            tir.carrier_id IN (2261273,2594705)
                        )
                    )
                        AND
                    tis.cycle_dt >= TO_DATE('$dateFrom', 'YYYY-MM-DD')
                        AND
                    tis.cycle_dt <= TO_DATE('$dateTo', 'YYYY-MM-DD')";

        if ($airport) {
            $sql .= sprintf(" AND (pt1.pt_cd = '%1\$s' OR pt2.pt_cd = '%1\$s')",
                            $airport);
        }

        $results = $conn->fetchAll($sql);
        
        foreach ($results as $flightSharer) {
            $dateUTC = date('Y-m-d', strtotime($flightSharer['UTC_DEPARTURE_DT']));
            $route = $flightSharer['DEPARTURE_PT'] . '-' . $flightSharer['ARRIVAL_PT'];
            $flightNo = $flightSharer['FLIGHT_NO'] ? $flightSharer['FLIGHT_NO'] : 'MISS';
            $rule = $flightSharer['RULE'];
            $alt = $flightSharer['ALT'];
            $bkd = $flightSharer['BKD'];
            $sectorAlt = $flightSharer['SECTOR_ALT'];
            $sectorBkd = $flightSharer['SECTOR_BKD'];

            if (!isset($sharers[$dateUTC])) {
                $sharers[$dateUTC] = [];
            }
            if (!isset($sharers[$dateUTC][$route])) {
                $sharers[$dateUTC][$route] = [];
            }
            if (!isset($sharers[$dateUTC][$route][$flightNo])) {
                $sharers[$dateUTC][$route][$flightNo] = [
                    'BT' => ['alt' => 0, 'bkd' => 0],
                    'SR' => ['alt' => 0, 'bkd' => 0],
                    'LM' => ['alt' => 0, 'bkd' => 0],
                    'SO' => ['alt' => 0, 'bkd' => 0],
                    'HF' => ['alt' => 0, 'bkd' => 0],
                    'ST' => ['alt' => 0, 'bkd' => 0],
                    'UK' => ['alt' => 0, 'bkd' => 0],
                    'rules' => [],
                    'alt' => 0,
                    'bkd' => 0,
                    'sector_alt' => $sectorAlt,
                    'sector_bkd' => $sectorBkd
                ];
            }

            if ($alt || $bkd) {
                if (!isset($sharers[$dateUTC][$route][$flightNo]['rules'][$rule])) {
                    $sharers[$dateUTC][$route][$flightNo]['rules'][$rule] = ['alt' => 0, 'bkd' => 0];
                }
                            
                $sharers[$dateUTC][$route][$flightNo][substr($rule, 0, 2)]['alt'] += $alt;
                $sharers[$dateUTC][$route][$flightNo][substr($rule, 0, 2)]['bkd'] += $bkd;

                $sharers[$dateUTC][$route][$flightNo]['rules'][$rule]['alt'] += $alt;
                $sharers[$dateUTC][$route][$flightNo]['rules'][$rule]['bkd'] += $bkd;

                $sharers[$dateUTC][$route][$flightNo]['alt'] += $alt;
                $sharers[$dateUTC][$route][$flightNo]['bkd'] += $bkd;
            }
        }
               
        $sql = "SELECT
                    tr.route_cd, tis.cycle_dt,
                    TO_CHAR(tis.utc_dep_dt_tm, 'YYYY-MM-DD\"T\"HH24:MI:SS\"Z\"') UTC_Departure_Dt,
                    TO_CHAR(tis.dep_dt_tm, 'YYYY-MM-DD\"T\"HH24:MI:SS') Departure_Dt,
                    TO_CHAR(tis.utc_arr_dt_tm, 'YYYY-MM-DD\"T\"HH24:MI:SS\"Z\"') UTC_Arrival_Dt,
                    TO_CHAR(tis.arr_dt_tm, 'YYYY-MM-DD\"T\"HH24:MI:SS') Arrival_Dt,
                    pt1.pt_cd Departure_Pt, pt2.pt_cd Arrival_Pt,
                    tr.dir_mth Direction, tis.route_num Flight_No,
                    tis.alt, tis.bkd, tis.opt, tir.sale_sts, th.cd, tis.carrier_id
                FROM
                    ATCOMRES.AR_TRANSHEAD th
                        INNER JOIN ATCOMRES.AR_TRANSROUTE tr
                        ON tr.trans_head_id = th.trans_head_id
                            INNER JOIN ATCOMRES.AR_TRANSINVROUTE tir
                            ON tir.trans_route_id = tr.trans_route_id
                                INNER JOIN ATCOMRES.AR_TRANSINVSECTOR tis
                                ON tis.trans_inv_sec_id = tir.dep_sec_id
                            INNER JOIN ATCOMRES.AR_POINT pt1
                            ON pt1.pt_id = tr.dep_pt_id
                            INNER JOIN ATCOMRES.AR_POINT pt2
                            ON pt2.pt_id = tr.arr_pt_id
                WHERE 
                    (
                        tis.carrier_id IN (2261273,2594705)
                            OR
                        (
                            tis.carrier_id IS NULL
                                AND
                            tir.carrier_id IN (2261273,2594705)
                        )
                    )
                        AND
                    tis.cycle_dt >= TO_DATE('$dateFrom', 'YYYY-MM-DD')
                        AND
                    tis.cycle_dt <= TO_DATE('$dateTo', 'YYYY-MM-DD')";

        if ($airport) {
            $sql .= sprintf(" AND (pt1.pt_cd = '%1\$s' OR pt2.pt_cd = '%1\$s')",
                            $airport);
        }

        $flights = $conn->fetchAll($sql);

        $cleanedFlights = [];       
        foreach ($flights as $flight) {
            if ($flight['SALE_STS'] == 'OFF' && $flight['ALT'] == 0) {
                continue;
            }
            
            $error = 0;
            $syncMsgs = [];
            $aAltInfo = [];
            $aBkdInfo = [];
            $qAltInfo = [];
            $qBkdInfo = [];

            $dateUTC = date('Y-m-d', strtotime($flight['UTC_DEPARTURE_DT']));
            $route = $flight['DEPARTURE_PT'] . '-' . $flight['ARRIVAL_PT'];
            $flightNo = $flight['FLIGHT_NO'] ? $flight['FLIGHT_NO'] : 'MISS';
            if ($flightNo == 'MISS') {
                $error += self::ERR_FLIGHT_NO;
            }
            
            $quintessence = [
                'allotment' => 0,
                'reserved' => 0,
                'dep_utc' => '',
                'dep_loc' => '',
                'arr_utc' => '',
                'arr_loc' => ''
            ];
            
            if (isset($dates[$dateUTC][$route][$flightNo])) {
                $quintessence = $dates[$dateUTC][$route][$flightNo];
                
                if (substr($quintessence['dep_loc'], 0, 16) != substr($flight['DEPARTURE_DT'], 0, 16)) {
                    $error += self::ERR_DEP;
                }
                if (substr($quintessence['arr_loc'], 0, 16) != substr($flight['ARRIVAL_DT'], 0, 16)) {
                    $error += self::ERR_ARR;
                }


                // Allotment and reserved matching?
                if (isset($sharers[$dateUTC][$route][$flightNo])) {
                    if ($quintessence['allotment'] != $sharers[$dateUTC][$route][$flightNo]['sector_alt']) {
                        $error += self::ERR_ALT;
                    }
                    if ($quintessence['reserved'] != $sharers[$dateUTC][$route][$flightNo]['sector_bkd']) {
                        $error += self::ERR_BKD;
                    }
                } else {
                    $error += self::ERR_ALT;
                    $error += self::ERR_BKD;
                }


                // Compare sharers and show rules!
                $sharerIds = ['BT', 'SR', 'LM', 'SO', 'HF'];
                foreach ($sharerIds as $sharer) {                    
                    
                    // Quintessence allotment and reserved
                    $qAlt = $dates[$dateUTC][$route][$flightNo][$sharer]['allotment'];
                    $qBkd = $dates[$dateUTC][$route][$flightNo][$sharer]['reserved'];
                    
                    // AtcomRes allotment & booked
                    if (isset($sharers[$dateUTC][$route][$flightNo])) {
                        $aAlt = $sharers[$dateUTC][$route][$flightNo][$sharer]['alt'];
                        $aBkd = $sharers[$dateUTC][$route][$flightNo][$sharer]['bkd'];
                    
                        if ($sharer == 'BT') {
                            $aAlt += $sharers[$dateUTC][$route][$flightNo]['ST']['alt'];
                            $aBkd += $sharers[$dateUTC][$route][$flightNo]['ST']['bkd'];
                        }
                    } else {
                        $aAlt = 0;
                        $aBkd = 0;
                    }
                    
                    if ($qAlt != $aAlt && !($error & self::ERR_ALT) && !($error & self::ERR_ALT_MM)) {
                        $error += self::ERR_ALT_MM;
                    }
                    if ($qBkd != $aBkd && !($error & self::ERR_BKD) && !($error & self::ERR_BKD_MM)) {
                        $error += self::ERR_BKD_MM;
                    }
                    
                    if ($dates[$dateUTC][$route][$flightNo][$sharer]['allotment'] || 
                        $dates[$dateUTC][$route][$flightNo][$sharer]['reserved']) {
                        $qAltInfo[] = $sharer . ': ' . $dates[$dateUTC][$route][$flightNo][$sharer]['allotment'];
                        $qBkdInfo[] = $sharer . ': ' . $dates[$dateUTC][$route][$flightNo][$sharer]['reserved'];
                    }
                }
                
                if (isset($sharers[$dateUTC][$route][$flightNo])) {
                    foreach ($sharers[$dateUTC][$route][$flightNo]['rules'] as $rule => $data) {
                        $aAltInfo[] = $rule . ': ' . $data['alt'];
                        $aBkdInfo[] = $rule . ': ' . $data['bkd'];
                    }
                }
                
                
            } else {
                $error += self::ERR_NOT_FOUND;
            }
            

            $sync = self::ERR_LVL_0;
            if ($error & self::ERR_NOT_FOUND) {
                $sync = self::ERR_LVL_3;
                $syncMsgs[] = 'No matching flight on the same date and route!';
            } else {
                if ($error & self::ERR_FLIGHT_NO) {
                    $sync = ($sync < self::ERR_LVL_2) ? self::ERR_LVL_2 : $sync;
                    $syncMsgs[] = 'Flight number is missing!';
                }
                if ($error & self::ERR_DEP) {
                    $sync = self::ERR_LVL_3;
                    $syncMsgs[] = 'Departure local time not matching!';
                }
                if ($error & self::ERR_ARR) {
                    $sync = self::ERR_LVL_3;
                    $syncMsgs[] = 'Arrival local time not matching!';
                }
                if ($error & self::ERR_ALT) {
                    $sync = self::ERR_LVL_3;
                    $syncMsgs[] = 'Allotment not matching!';
                }
                if ($error & self::ERR_ALT_MM) {
                    $sync = ($sync < self::ERR_LVL_2) ? self::ERR_LVL_2 : $sync;
                    $syncMsgs[] = 'Mismatch in allotment sharer!';
                }
                if ($error & self::ERR_BKD) {
                    $sync = ($sync < self::ERR_LVL_1) ? self::ERR_LVL_1 : $sync;
                    $syncMsgs[] = 'Reserved/booked not matching!';
                }
                if ($error & self::ERR_BKD_MM) {
                    $sync = ($sync < self::ERR_LVL_1) ? self::ERR_LVL_1 : $sync;
                    $syncMsgs[] = 'Mismatch in booking sharer!';
                }
            }

            switch ($sync)
            {
                case self::ERR_LVL_1:
                    $syncVal = 'info';
                    break;
                case self::ERR_LVL_2:
                    $syncVal = 'warning';
                    break;
                case self::ERR_LVL_3:
                    $syncVal = 'danger';
                    break;
                default:
                    $syncVal = 'success';
            }
            
            $flight['QUINTESSENCE'] = [
                'ALT' => $quintessence['allotment'],
                'BKD' => $quintessence['reserved'],
                'UTC_DEPARTURE_DT' => $quintessence['dep_utc'],
                'DEPARTURE_DT' => substr($quintessence['dep_loc'], 0, 19),
                'UTC_ARRIVAL_DT' => $quintessence['arr_utc'],
                'ARRIVAL_DT' => substr($quintessence['arr_loc'], 0, 19),
                'SYNC' => $syncVal,
                'SYNC_MSGS' => $syncMsgs,
                'ALT_SHARERS' => '<h6>@comRes</h6><p>' . implode($aAltInfo, '<br>') . '</p><h6>Quintessence</h6><p>' . implode($qAltInfo, '<br>') . '</p>',
                'BKD_SHARERS' => '<h6>@comRes</h6><p>' . implode($aBkdInfo, '<br>') . '</p><h6>Quintessence</h6><p>' . implode($qBkdInfo, '<br>') . '</p>',
            ];
            $flight['ERROR'] = $error;
                        
            $cleanedFlights[] = $flight;
            
            // to compare if flights from Quintessence is not present in the data from AtcomRes

        }
        
        $missing = [];
        if ($request->query->has('showMissing')) {

            foreach ($dates as $dateUTC => $routes) {
                if (strtotime($dateUTC) < strtotime($dateFrom) || strtotime($dateUTC) > strtotime($dateTo)) {
                    continue;
                }
                foreach ($routes as $route => $flights) {
                    foreach ($flights as $flightNo => $flight) {
                        if (!isset($sharers[$dateUTC][$route][$flightNo]) && !isset($sharers[$dateUTC][$route]['MISS'])) {
                            $sharerInfo = [];
                            foreach ($sharerIds as $sharer) {
                                if ($flight[$sharer]['allotment']) {
                                    $sharerInfo[] = $sharer . ' = ' . $flight[$sharer]['allotment'];
                                }
                            }
                            $missing[] = 'Missing ' . $flightNo . ' (' . $route . ') on ' . $dateUTC . ' (' . implode($sharerInfo, ' / ') . ')';
                        }
                    }
                }
            }
        }

        return $this->render('flight/index.html.twig', [
            'flights' => $cleanedFlights,
            'missing' => $missing,
            'from' => $dateFrom,
            'to' => $dateTo,
            'airport' => $airport
        ]);
    }




    /**
     * @Route("/inventory/flight/sellrules", name="inventory_flight_sellrules")
     */
    public function flightSellrulesAction(Request $request)
    {           
        $curDate=null;
        $saleSts=null;
        if(!empty($request->query->get('curDate')))
        {
            $curDate = new \Datetime($request->query->get('curDate'));
        }        
        
        $depCd = strtoupper($request->query->get('dep_cd', null));
        $arrCd = strtoupper($request->query->get('arr_cd', null));
       
        $headCd = strtoupper($request->query->get('head_cd', null));
       
       
        $stDt = new \Datetime($request->query->get('st_dt'));
        $endDt = new \Datetime($request->query->get('end_dt'));
        $prcLvl = $request->query->get('prc_lvl', null);
        $hideEmpty = $request->query->get('hide_empty', null);
        $hideOff = $request->query->get('hide_off', null);
        $alwaysShow = $request->query->get('always_show', null);
        
        $sellRules = [
            'out' => [],
            'ret' => [],
        ];

        $flights = [];

        if ((($depCd && $arrCd) || $headCd) && $stDt && $endDt) {
            
            $sectorRep = $this->getDoctrine()->getRepository('PrimeraAtcomResBundle:DB\TransInvSector', 'atcore');
            
            if ($headCd) {
                $sectors = $sectorRep->loadByHeadCdAndDatesSaleSts($headCd, $stDt, $endDt,$curDate,$saleSts);
                //$sectors = $sectorRep->loadByHeadCdAndDates($headCd, $stDt, $endDt,$curDate);
            } else {
                $sectors = $sectorRep->loadByOnDAndDatesSaleSts($depCd, $arrCd, $stDt, $endDt,$curDate,$saleSts);
            }
            
            foreach ($sectors as $sector) {
                $cycleDt = $sector->getCycleDt()->format('Y-m-d');
                $headCd = $sector->getTransHead()->getCd();
                $saleSts = $sector->getSaleSts();
                $dir = strtolower($sector->getInvRouteSectors()[0]->getTransInvRoute()->getTransRoute()->getDirMth());
                
                foreach ($sector->getYieldSells() as $yieldSell) {
                    $sellRule = $yieldSell->getSellRule();
                    $inventory = $yieldSell->getInventory();
                    $rule = $sellRule->getRule();
                    $sellDetails = $sellRule->getSellDetails();
                    $duration = $sellDetails[0]->getDuration();
                    $sts = $yieldSell->getSts();
                    
                    $prcLvlMatch = true;
                    if ($prcLvl) {
                        $prcLvlMatch = false;
                        foreach ($yieldSell->getRates() as $rate) {
                            if ($rate->getPrcLvl() == $prcLvl && $rate->getSts() != 'HIS') {
                                $prcLvlMatch = true;
                            }
                        }
                    }
            
                    if ($hideOff && $sts == 'OFF') {
                        continue;
                    }
                    
                    if (!array_key_exists($rule, $sellRules[$dir])) {
                        $sellRules[$dir][$rule] = [
                            'duration' => $duration,
                            'rule' => $rule,
                            'active' => false,
                        ];
                    }
            
                    if (!isset($flights[$cycleDt])) {
                        $flights[$cycleDt] = [];
                    }
            
                    if (!isset($flights[$cycleDt][$headCd])) {
                        $flights[$cycleDt][$headCd] = [
                            'active_rules' => 0,
                        ];
                    }
            
                    if (!isset($flights[$cycleDt][$headCd][$dir])) {
                        $flights[$cycleDt][$headCd][$dir] = [
                            'sec_id' => $sector->getTransInvSecId(),
                            'rules' => [],
                            'active_rules' => 0,
                            'alt' => $sector->getAlt(),
                            'bkd' => $sector->getOB(),
                            'sale_sts' => $saleSts,
                        ];
                    }

                    if ($inventory->getAlt()) {
                        $flights[$cycleDt][$headCd]['active_rules']++;
                        $flights[$cycleDt][$headCd][$dir]['active_rules']++;
                        $sellRules[$dir][$rule]['active'] = true;
                    }
            
                    $inbdRstn = '';
                    $inbdRestrictions = [];
                    $sellDetails = $yieldSell->getSellDetails();
                    if (is_array($sellDetails)) {
                        $inbdRstn = $sellDetails[0]->getInbdRstn();
                        $inbdRestrictions = $sellDetails[0]->getInboundRestrictions();
                    }
                    
                    $flights[$cycleDt][$headCd][$dir]['rules'][$rule] = [
                        'active' => $inventory->getAlt() ? true : false,
                        'duration' => $duration,
                        'alt' => $inventory->getAlt(),
                        'bkd' => $inventory->getOB(),
                        'inbd_rstn' => $inbdRstn,
                        'rstn' => [],
                        'sts' => $sts,
                        'prc_lvl_match' => $prcLvlMatch,
                    ];
                    
                    foreach ($inbdRestrictions as $rstn) {
                        $flights[$cycleDt][$headCd][$dir]['rules'][$rule]['rstn'][] = $rstn->getTransRoute()->getTransHead()->getCd();
                    }
                }            
            }
        
            uasort($sellRules['out'], array($this, 'sortSellRules'));
            uasort($sellRules['ret'], array($this, 'sortSellRules'));

            $alwaysActive = [
                'BTF0',
                'SRF0',
                'LMF0',
                'SOF0',
                'HFF0',
                'STF0',
                'UKF0'
            ];
            
            if ($hideEmpty) {
                foreach ($sellRules as $dir => $rules) {
                    foreach ($rules as $ruleCd => $rule) {
                        if (!$rule['active']) {
                            if ($alwaysShow && in_array($ruleCd, $alwaysActive)) {
                                continue;
                            }
                            unset($sellRules[$dir][$ruleCd]);
                        }
                    }
                }
            }
        }

        return $this->render('flight/sellrules.html.twig', [
            'search'        => $request->query->all(),
            'flights'       => $flights,
            'sell_rules'    => $sellRules,
            //'sale_sts'      => $saleSts
        ]);
    }

    /**
     * @Route("/inventory/flight/sellrules/save", name="inventory_flight_sellrules_save")
     */
    public function flightSellrulesSaveAction(Request $mainRequest)
    {
        
        // Check user rights ROLE_FLIGHT_SELLRULE
        if ($this->get('security.authorization_checker')->isGranted('ROLE_FLIGHT')) {           
            $jsonString = $mainRequest->request->get('json', null);            
            $doctrine = $this->getDoctrine();
            $em = $doctrine->getManager();            
            $user = $this->get('security.token_storage')->getToken()->getUser();            
            $userId = $user->getId();            
            $dateList = $user->getLastLogin(); 
            $date = (Array)$dateList;
            
            $loggedInDate = "'".$date['date']."'";                        
            if (is_null($jsonString)) {
                return $this->redirectToRoute('inventory_flight_sellrules', $mainRequest->query->all());
            }
            
            if ($jsonString) { 
               
                
                $key    =   '';
                $json   =   json_decode($jsonString);

                $sectorRep = $this->getDoctrine()->getRepository('PrimeraAtcomResBundle:DB\TransInvSector', 'atcore');
            
                $stDt = new \Datetime($json->stDt);
                $endDt = new \Datetime($json->endDt);
                $curDate = null;
                if ($json->headCd) {
                    $sectors = $sectorRep->loadByHeadCdAndDates($json->headCd, $stDt, $endDt,$curDate);
                } else {
                    $sectors = $sectorRep->loadByOnDAndDates($json->depCd, $json->arrCd, $stDt, $endDt,$curDate);
                }

                $totalSeats = [];
                foreach ($sectors as $sector) {
                    $key = strtoupper($sector->getTransHead()->getCd() . ' ' . $sector->getCycleDt()->format('Y-m-d'));                    
                    $totalSeats[$key] = $sector->getAlt();
                }
                
                $requests = [];
                $seq = 1;
                $sequences = [];                
                foreach ($json->adjustments as $adjustment) {
                    $key = strtoupper($adjustment->headCd . $adjustment->direction . $adjustment->cycleDt);                     
                    $keys = strtoupper($adjustment->headCd . ' ' . $adjustment->cycleDt);                     
                    if (!isset($requests[$key])) {
                        $requests[$key] = [
                            'seq' => $seq,
                            'transport_cd' => $adjustment->headCd,
                            'dep_air_cd' => $this->getPtCdFromHeadCdAndDir('dep', $adjustment->headCd, $adjustment->direction),
                            'arr_air_cd' => $this->getPtCdFromHeadCdAndDir('arr', $adjustment->headCd, $adjustment->direction),
                            'cycle_dates' => [],
                        ];
                        $sequences[$seq] = $key;
                        $seq++;
                    }
                    
                    if (!isset($requests[$key]['cycle_dates'][$adjustment->cycleDt])) {
                        $dir_repalce = str_replace(' ',$adjustment->direction,$key);
                        $requests[$key]['cycle_dates'][$adjustment->cycleDt] = [
                            'cycle_dt' => $adjustment->cycleDt,
                            'tot_seats' => $totalSeats[$keys],
                            'sell_rules' => [],
                        ];
                    }
                    
                    $requests[$key]['cycle_dates'][$adjustment->cycleDt]['sell_rules'][$adjustment->rule] = $adjustment->change;
                }

                // Now loop through all requests and create xml
                $requestGroup = new FDL\RequestGroup();
                $requestGroup->Control_Group = new FDL\ControlGroup();

                $control = new FDL\Control();
                $control->Msg_Sub_Tp = 'AMENDADJUSTSELLRULE';
    
                foreach ($requests as $key => $req) {
                    foreach ($req['cycle_dates'] as $date) {
                        $request = new FDL\Request();
                        $request->Control = clone $control;
                        $request->Control->Group_Seq = $req['seq'];
        
                        $request->Trans_Header = new FDL\TransHeader();
                        $request->Trans_Header->Transport_Cd = $req['transport_cd'];
                        $request->Trans_Header->Dep_Air_Cd = $req['dep_air_cd'];
                        $request->Trans_Header->Arr_Air_Cd = $req['arr_air_cd'];
                        $request->Trans_Header->Cycle_Dates = new FDL\CycleDates();
        
                        $transportCycleDate = new FDL\TransportCycleDate();
                        $transportCycleDate->Cycle_Dt = $date['cycle_dt'];
                        $transportCycleDate->Tot_Seats = $date['tot_seats'];
                        $transportCycleDate->Sell_Rules = new FDL\SellRules();
        
                        foreach ($date['sell_rules'] as $rule => $change) {
                            $sellRule = new FDL\SellRule();
                            $sellRule->Cd = $rule;
                            $sellRule->Adjust_Seats = $change;
                            $transportCycleDate->Sell_Rules->Sell_Rule[] = $sellRule;
                        }
        
                        $request->Trans_Header->Cycle_Dates->Transport_Cycle_Date[] = $transportCycleDate;

                        // Add all (here just one) requests to the request group
                        $requestGroup->Request[] = $request;
                    }
        
                }
                
                $request = new WS\Request();
                $request->CONTROL = new WS\Control();
                $request->CONTROL->MSG_TP = 'FDL';
                $request->Request_Group = $requestGroup;
                
                $serializer = $this->get('jms_serializer');
                $xml = $serializer->serialize($request, 'xml');
                
                $xml = substr($xml, strpos($xml, '?'.'>') + 2);
        
                $uri = $this->getGateway();
        
                $client = new Client(['http_errors' => false]);
                $response = $client->request('POST', $uri, [
                    'form_params' => [
                            'XML' => $xml,
                    ],
                ]);
                
                $responseString = $response->getBody();
                $responses = $this->get('jms_serializer')->deserialize($responseString, 'Primera\AtcomResBundle\Entity\WebService\Response', 'xml');                
                //echo $responses->Response_Group['Response']['0']['Control']['Error']['Err_Text']; 
                //print_r($responses->Response_Group->Response['0']->Control); exit;
                if(empty($responses->Response_Group->Response['0']->Control->Error->Err_Text))
                {
                    $doneChanges = (Array)json_decode($jsonString);    
                $depCd              =   "'".strip_tags($doneChanges['depCd'])."'";
                $arrCd              =   "'".strip_tags($doneChanges['arrCd'])."'";
                $m_headCd           =   "'".strip_tags($doneChanges['headCd'])."'";
                $stDt               =   "'".$doneChanges['stDt']."'"; 
                $endDt              =   "'".$doneChanges['endDt']."'";
                $adjmentCount       =   count($doneChanges['adjustments']);
                $Modified_values    =   '';
                
                if( (isset($doneChanges['adjustments'])) && ($adjmentCount!=0)){
                    
                    //Log insertion Functionality 
                    foreach($doneChanges['adjustments'] as $changedValue){
                        $headCd         =   "'".strip_tags($changedValue->headCd)."'";
                        $direction      =   "'".strip_tags($changedValue->direction)."'";
                        $cycleDt        =   "'".strip_tags($changedValue->cycleDt)."'";
                        $rule           =   "'".strip_tags($changedValue->rule)."'";
                        $change         =   $changedValue->change;                        
                        
                        if(!empty($changedValue->prcVal))
                        {
                            $prcVal         =   "'".$changedValue->prcVal."'";                                                
                        } else {  $prcVal = 'null'; }
                        
                        $hide_empty     =   "'".$changedValue->hide_empty."'";
                        $hide_off       =   "'".$changedValue->hide_off."'";
                        $always_show    =   "'".$changedValue->always_show."'";

                        $Modified_values = $doctrine->getEntityManager()
                                           ->getConnection()
                                           ->prepare("INSERT into flight_log_pricedefinition_change 
                                                      (batch_id,depCd, arrCd,headCd,m_headCd,stDt,endDt,direction,cycleDt,rule,change_status,update_dt_tm,hide_empty,hide_off,always_show,prcVal)
                                                       values($userId,$depCd,$arrCd,$headCd,$m_headCd,$stDt,$endDt,$direction,$cycleDt,$rule,$change,$loggedInDate,$hide_empty,$hide_off,$always_show,$prcVal)");                                
                        $Modified_values->execute();
                    }
                }
                else{
                    die('Not Data is not Modified!');
                    return $this->render('flight/sellrules_saved.html.twig');   }
                }
                
                return $this->render('flight/sellrules_saved.html.twig', [
                    'search' => $mainRequest->query->all(),
                    'requests' => $requests,
                    'sequences' => $sequences,
                    'responses' => $responses,
                ]);
            }
            
            die('jsonString missing!');

        } else {
            return $this->render('flight/sellrules_save_not_allowed.html.twig', [
                'search' => $mainRequest->query->all(),
            ]);
        }

    }


    /**
     * @Route("/inventory/flight/seats/ajax", name="inventory_flight_seats_ajax")
     */
    public function flightSeatsAjaxAction(Request $request)
    {
        $secId = strtoupper($request->query->get('sec_id', null));
        
        $sellRules = [];
        
        $sectorRep = $this->getDoctrine()->getRepository('PrimeraAtcomResBundle:DB\TransInvSector', 'atcore');
        $sector = $sectorRep->loadSellRulesBySecId($secId);
        $seats = $sector->getAlt();

        foreach ($sector->getYieldSells() as $yieldSell) {
            $sellRule = $yieldSell->getSellRule();
            $inventory = $yieldSell->getInventory();
            
            $sellRules[$sellRule->getRule()] = $inventory->getAlt();
        }


        return $this->render('flight/sellrules/seats.html.twig', [
            'sec_id' => $secId,
            'seats' => $seats,
            'sell_rules' => $sellRules,
        ]);

    }

    /**
     * @Route("/inventory/flight/seats/save", name="inventory_flight_seats_save")
     */
    public function flightSeatsSaveAction(Request $mainRequest)
    {
        // Check user rights ROLE_FLIGHT_SELLRULE
        if ($this->get('security.authorization_checker')->isGranted('ROLE_FLIGHT_ADMIN')) {
        
            $secId = $mainRequest->request->get('sec_id', null);
            $adjustSeats = $mainRequest->request->get('adjust_seats', null);
            $sellRules = $mainRequest->request->get('sell_rules', null);

            $sectorRep = $this->getDoctrine()->getRepository('PrimeraAtcomResBundle:DB\TransInvSector', 'atcore');
            $sector = $sectorRep->loadSellRulesBySecId($secId);
        
            $headCd = $sector->getTransHead()->getCd();
            $direction = $sector->getDirMth();

            // Now loop through all requests and create xml
            $requestGroup = new FDL\RequestGroup();
            $requestGroup->Control_Group = new FDL\ControlGroup();

            $request = new FDL\Request();
            $request->Control = new FDL\Control();
            $request->Control->Msg_Sub_Tp = 'AMENDADJUSTSELLRULE';

            $request->Trans_Header = new FDL\TransHeader();
            $request->Trans_Header->Transport_Cd = $sector->getTransHead()->getCd();
            $request->Trans_Header->Dep_Air_Cd = $this->getPtCdFromHeadCdAndDir('dep', $headCd, $direction);
            $request->Trans_Header->Arr_Air_Cd = $this->getPtCdFromHeadCdAndDir('arr', $headCd, $direction);
            $request->Trans_Header->Cycle_Dates = new FDL\CycleDates();

            $transportCycleDate = new FDL\TransportCycleDate();
            $transportCycleDate->Cycle_Dt = $sector->getCycleDt()->format('Y-m-d');
            $transportCycleDate->Tot_Seats = $sector->getAlt();
            $transportCycleDate->Adjust_Seats = $adjustSeats;
            $transportCycleDate->Sell_Rules = new FDL\SellRules();

            foreach ($sellRules as $rule => $change) {
                if ($change) {
                    $sellRule = new FDL\SellRule();
                    $sellRule->Cd = $rule;
                    $sellRule->Adjust_Seats = $change;
                    $transportCycleDate->Sell_Rules->Sell_Rule[] = $sellRule;
                }
            }

            $request->Trans_Header->Cycle_Dates->Transport_Cycle_Date[] = $transportCycleDate;

            $requestGroup->Request[] = $request;
        
            $request = new WS\Request();
            $request->CONTROL = new WS\Control();
            $request->CONTROL->MSG_TP = 'FDL';
            $request->Request_Group = $requestGroup;
        
            $serializer = $this->get('jms_serializer');
            $xml = $serializer->serialize($request, 'xml');

            $xml = substr($xml, strpos($xml, '?'.'>') + 2);

            $uri = $this->getGateway();

            $client = new Client(['http_errors' => false]);
            $response = $client->request('POST', $uri, [
                'form_params' => [
                        'XML' => $xml,
                ],
            ]);
        
            $responseString = $response->getBody();
            $responses = $this->get('jms_serializer')->deserialize($responseString, 'Primera\AtcomResBundle\Entity\WebService\Response', 'xml');

            return $this->render('flight/sellrules_seats_saved.html.twig', [
                'search' => $mainRequest->query->all(),
                'responses' => $responses,
            ]);
        }

    }

    
    function getPtCdFromHeadCdAndDir($ptTp, $headCd, $dir)
    {
        $pt1 = substr($headCd, 0, 3);
        $pt2 = substr($headCd, 3, 3);
        
        $dir = strtoupper($dir);
        
        if ($dir == 'OUT' && $ptTp == 'dep') {
            return $pt2;
        } elseif ($dir == 'OUT' && $ptTp == 'arr') {
            return $pt1;
        } elseif ($dir == 'RET' && $ptTp == 'dep') {
            return $pt1;
        } elseif ($dir == 'RET' && $ptTp == 'arr') {
            return $pt2;
        }
    }
        
    function sortSellRules($a, $b) {
        $aStr = ($a['duration'] ? sprintf('%02d', $a['duration']) : 99) . $a['rule'];
        $bStr = ($b['duration'] ? sprintf('%02d', $b['duration']) : 99) . $b['rule'];
        if ($aStr == $bStr) {
            return 0;
        }
        return ($aStr < $bStr) ? -1 : 1;
    }
    
    function getGateway() {
        $kernel = $this->get('kernel');
        if ($kernel->getEnvironment() != 'prod') {
            $server = '192.168.218.4';
            $environment = 'PRMUAT';
        } else {
            $server = '192.168.218.30';
            $environment = 'PRMPROD';
        }
        return 'http://' . $server . '/' . $environment . '/XMLwebservice/AniteXMLGateway.aspx';
    }
}
