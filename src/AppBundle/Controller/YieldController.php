<?php

namespace AppBundle\Controller;

use DateInterval;
use DatePeriod;
use DateTime;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use AppBundle\Entity\Packages;
use AppBundle\Entity\YieldSupRequest;
use AppBundle\Entity\YieldSup;
use AppBundle\Entity\PriceDefinition\Log\Batch;
use AppBundle\Entity\PriceDefinition\Log\Change;

class YieldController extends Controller {

    private $dateFrom;
    private $dateTo;
    private $prom = '';
    private $airport = '';
    private $offsale = 0;
    private $allowedUsers = [];    

    /**
     * @Route("/yield/flightsales", name="yield_flightsales")
     */
    public function yieldFlightSalesAction(Request $request) {
        date_default_timezone_set('UTC');

        $this->cycleStDt = new \Datetime($request->query->get('st_dt', null));
        $this->cycleEndDt = new \Datetime($request->query->get('end_dt', null));
        $this->promCd = strtoupper($request->query->get('prom_cd', null));
        if ($this->promCd) {
            $this->promCds = explode(',', $this->promCd);
        }
        //Added on 05/09/2018 for New Flight Sales feature
        if (!empty($request->query->get('arr_cd', null))) {
            $this->arrCd = strtoupper($request->query->get('arr_cd', null));
        } else {
            $this->arrCd = '';
        }

        $this->offSale = $request->query->get('offsale', null);
        if (!empty($request->query->get('longhaul', null))) {
            $long = $request->query->get('longhaul', null);
        } else {
            $long = '';
        }

        // Find last four weeks
        $daysBack = (date('N') == 1) ? 7 : (date('N') - 1);
        $timeStamp = time() - 60 * 60 * 24 * $daysBack;
        $weekNo = [];
        for ($i = 0; $i <= 4; $i++) {
            $ts = $timeStamp - 60 * 60 * 24 * 7 * $i;
            $key = date('W', $ts);
            $weekNo[] = $key;
        }

        if ($request->query->has('prom_cd')) {

            $sectorRep = $this->getDoctrine()->getRepository('PrimeraAtcomResBundle:DB\TransInvSector', 'atcore');

            $sts = !$this->offSale ? 'ON' : null;
            $long_value = !$long ? null : 'LNG';

            $seats = [];
            $sectors = $sectorRep->loadByDatesArrCdAndDirection($this->cycleStDt, $this->cycleEndDt, $this->arrCd, 'OUT', $sts, $long_value);

            foreach ($sectors as $sector) {
                $transInvRoute = $sector->getInvRouteSectors()[0]->getTransInvRoute();
                $arrCd = $transInvRoute->getTransRoute()->getArrPoint()->getPtCd();
                $depCd = $transInvRoute->getTransRoute()->getDepPoint()->getPtCd();
                $cycleDt = $sector->getCycleDt()->format('Y-m-d');
                $routeCd = $transInvRoute->getTransRoute()->getRouteCd();
                $fltNb = $sector->getRouteNum();

                foreach ($sector->getYieldSells() as $yieldSell) {
                    $sellRule = $yieldSell->getSellRule();
                    $inventory = $yieldSell->getInventory();
                    $rule = $sellRule->getRule();
                    $sellDetails = $sellRule->getSellDetails();
                    $sharer = substr($sellDetails[0]->getPromotion()->getCd(), 0, 2);
                    $duration = $sellDetails[0]->getDuration();

                    if (!isset($seats[$cycleDt])) {
                        $seats[$cycleDt] = [];
                    }

                    if (!isset($seats[$cycleDt][$routeCd])) {
                        $seats[$cycleDt][$routeCd] = [
                            'FLT_NB' => $fltNb,
                            'DEP' => $depCd,
                            'ARR' => $arrCd,
                            'STS' => $transInvRoute->getSaleSts(),
                            'ROUTE_TYPE' => $transInvRoute->getTransRouteId(),
                            'LONG_HAUL' => $transInvRoute->getHaulMth(),
                            'TOTAL' => ['alt' => 0, 'bkd' => 0],
                            'SECTOR' => ['alt' => $sector->getAlt(), 'bkd' => $sector->getOB()],
                            'WEEKS' => ['PAX' => [], 'PRC' => []],
                            'RULES' => []
                        ];
                        foreach ($weekNo as $week) {
                            $seats[$cycleDt][$routeCd]['WEEKS']['PAX'][$week] = 0;
                            $seats[$cycleDt][$routeCd]['WEEKS']['PRC'][$week] = 0;
                        }
                    }

                    if (!$this->promCd || in_array($sharer, $this->promCds)) {

                        if (!isset($seats[$cycleDt][$routeCd][$sharer])) {
                            $seats[$cycleDt][$routeCd][$sharer] = ['alt' => 0, 'bkd' => 0];
                        }

                        if (!isset($seats[$cycleDt][$routeCd]['RULES'][$rule])) {
                            $seats[$cycleDt][$routeCd]['RULES'][$rule] = ['alt' => 0, 'bkd' => 0, 'dur' => $duration];
                        }

                        $seats[$cycleDt][$routeCd][$sharer]['alt'] += $inventory->getAlt();
                        $seats[$cycleDt][$routeCd][$sharer]['bkd'] += $inventory->getOB();
                        $seats[$cycleDt][$routeCd]['RULES'][$rule]['alt'] += $inventory->getAlt();
                        $seats[$cycleDt][$routeCd]['RULES'][$rule]['bkd'] += $inventory->getOB();
                    }

                    $seats[$cycleDt][$routeCd]['TOTAL']['alt'] += $inventory->getAlt();
                    $seats[$cycleDt][$routeCd]['TOTAL']['bkd'] += $inventory->getOB();
                }
            }

            for ($i = 0; $i <= 4; $i++) {
                $ts = $timeStamp - 60 * 60 * 24 * 7 * $i;
                $key = date('W', $ts);
                $weekTo = date('Y-m-d', $ts + 60 * 60 * 24 * 6);

                $results = $this->getPaxOnDateFromAr($weekTo);
                foreach ($results as $result) {
                    if ($result['BKG_STS'] == 'BKG') {
                        $cycleDtPax = new \Datetime($result['CYCLE_DT']);
                        $seats[$cycleDtPax->format('Y-m-d')][$result['ROUTE_CD']]['WEEKS']['PAX'][$key] = $result['PAX'];
                        $seats[$cycleDtPax->format('Y-m-d')][$result['ROUTE_CD']]['WEEKS']['PRC'][$key] = $result['PRICE'];
                    }
                }
            }
        } else {
            $seats = [];
        }

        return $this->render('yield/flightsales.html.twig', [
                    'search' => $request->query->all(),
                    'seats' => $seats,
                    'week_no' => $weekNo,
        ]);
    }

    /**
     * @Route("/yield/movement", name="yield_movement")
     */
    public function yieldMovementAction(Request $request) {
//        return $this->redirectToRoute('yield_flightsales');

        date_default_timezone_set('UTC');

        $request = Request::createFromGlobals();
        if ($request->query->has('st_dt')) {
            $this->dateFrom = date('Y-m-d', strtotime($request->query->get('st_dt')));
        } else {
            $this->dateFrom = date('Y-m-d');
        }
        if ($request->query->has('end_dt')) {
            $this->dateTo = date('Y-m-d', strtotime($request->query->get('end_dt')));
        } else {
            $this->dateTo = date('Y-m-d', time() + 60 * 60 * 24 * 30);
        }
        if ($request->query->has('prom_cd')) {
            $this->prom = preg_replace('/[^A-Z,]+/', '', strtoupper($request->query->get('prom_cd')));
        }
        if ($request->query->has('arr_cd')) {
            $this->airport = preg_replace('/[^A-Z,]+/', '', strtoupper($request->query->get('arr_cd')));
        }
        if ($request->query->has('offsale')) {
            $this->offsale = intval($request->query->get('offsale'));
        }
        $this->prom = explode(',', $this->prom);
        $this->airport = explode(',', $this->airport);

        $daysBack = (date('N') == 1) ? 7 : (date('N') - 1);
        $timeStamp = time() - 60 * 60 * 24 * $daysBack;
        $weekNo = [];
        for ($i = 0; $i <= 4; $i++) {
            $ts = $timeStamp - 60 * 60 * 24 * 7 * $i;
            $key = date('W', $ts);
            $weekNo[] = $key;
        }

        if (!empty($this->prom) && !empty($this->airport)) {

            $conn = $this->get('doctrine.dbal.atcore_connection');

            $sql = sprintf("SELECT
                        tir.route_num,
                        tr.route_cd, to_char(tis.cycle_dt, 'YYYY-MM-DD') AS cycle_dt,
                        tis.alt AS sector_alt, tis.bkd AS sector_bkd,
                        ti.alt, ti.bkd, tsr.rule, pt1.pt_cd as dep_air_cd,
                        pt2.pt_cd as arr_air_cd, tir.sale_sts
                    FROM
                        ATCOMRES.AR_TRANSROUTE tr
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
                        tis.cycle_dt >= TO_DATE('%1\$s', 'YYYY-MM-DD')
                            AND
                        tis.cycle_dt <= TO_DATE('%2\$s', 'YYYY-MM-DD')
                            AND
                        tr.dir_mth = 'OUT'
                            AND
                        tiys.sts != 'OFF'
                        %3\$s
                        %4\$s
                    ORDER BY pt2.pt_cd, tis.cycle_dt, tr.route_cd", $this->dateFrom, $this->dateTo, ($this->airport[0] != 'ALL') ? "AND pt2.pt_cd IN ('" . implode($this->airport, "','") . "')" : '', (!$this->offsale) ? "AND tir.sale_sts = 'ON'" : ''
            );

            $seats = [];
            $results = $conn->fetchAll($sql);

            foreach ($results as $result) {
                $arrAirCd = $result['ARR_AIR_CD'];
                $cycleDt = $result['CYCLE_DT'];
                $routeCd = $result['ROUTE_CD'];
                $fltNb = $result['ROUTE_NUM'];
                $rule = $result['RULE'];
                $sharer = substr($result['RULE'], 0, 2);

                if (!isset($seats[$arrAirCd])) {
                    $seats[$arrAirCd] = [];
                }
                if (!isset($seats[$arrAirCd][$cycleDt])) {
                    $seats[$arrAirCd][$cycleDt] = [];
                }
                if (!isset($seats[$arrAirCd][$cycleDt][$routeCd])) {
                    $seats[$arrAirCd][$cycleDt][$routeCd] = [
                        'FLT_NB' => $fltNb,
                        'DEP' => $result['DEP_AIR_CD'],
                        'STS' => $result['SALE_STS'],
                        'TOTAL' => ['alt' => 0, 'bkd' => 0],
                        'SECTOR' => ['alt' => $result['SECTOR_ALT'], 'bkd' => $result['SECTOR_BKD']],
                        'WEEKS' => ['PAX' => [], 'PRC' => []],
                        'RULES' => []
                    ];
                    foreach ($weekNo as $week) {
                        $seats[$arrAirCd][$cycleDt][$routeCd]['WEEKS']['PAX'][$week] = 0;
                        $seats[$arrAirCd][$cycleDt][$routeCd]['WEEKS']['PRC'][$week] = 0;
                    }
                }

                if (!isset($seats[$arrAirCd][$cycleDt][$routeCd][$sharer])) {
                    $seats[$arrAirCd][$cycleDt][$routeCd][$sharer] = ['alt' => 0, 'bkd' => 0];
                }
                if (!isset($seats[$arrAirCd][$cycleDt][$routeCd]['RULES'][$rule])) {
                    $seats[$arrAirCd][$cycleDt][$routeCd]['RULES'][$rule] = ['alt' => 0, 'bkd' => 0];
                }

                $seats[$arrAirCd][$cycleDt][$routeCd][$sharer]['alt'] += $result['ALT'];
                $seats[$arrAirCd][$cycleDt][$routeCd][$sharer]['bkd'] += $result['BKD'];
                $seats[$arrAirCd][$cycleDt][$routeCd]['TOTAL']['alt'] += $result['ALT'];
                $seats[$arrAirCd][$cycleDt][$routeCd]['TOTAL']['bkd'] += $result['BKD'];
                $seats[$arrAirCd][$cycleDt][$routeCd]['RULES'][$rule]['alt'] += $result['ALT'];
                $seats[$arrAirCd][$cycleDt][$routeCd]['RULES'][$rule]['bkd'] += $result['BKD'];
            }

            for ($i = 0; $i <= 4; $i++) {
                $ts = $timeStamp - 60 * 60 * 24 * 7 * $i;
                $key = date('W', $ts);
                $weekTo = date('Y-m-d', $ts + 60 * 60 * 24 * 6);

                $results = $this->getPaxOnDate($weekTo);
                foreach ($results as $result) {
                    if ($result['BKG_STS'] == 'BKG') {
                        $seats[$result['ARR_AIR_CD']][$result['CYCLE_DT']][$result['ROUTE_CD']]['WEEKS']['PAX'][$key] = $result['PAX'];
                        $seats[$result['ARR_AIR_CD']][$result['CYCLE_DT']][$result['ROUTE_CD']]['WEEKS']['PRC'][$key] = $result['PRICE'];
                    }
                }
            }
        } else {
            $seats = [];
        }

        return $this->render('yield/movement.html.twig', [
                    'search' => $request->query->all(),
                    'seats' => $seats,
                    'week_no' => $weekNo,
        ]);
    }

    /**
     * @Route("/yield/movement/modal", name="yield_movement_modal")
     */
    public function yieldMovementModalAction(Request $request) {
        $request = Request::createFromGlobals();

//        $conn = $this->get('doctrine.dbal.atcore_connection');
        $packages = new Packages();
        $packages->setAdults(2);
        $packages->setSort('price');

        if ($request->query->has('from')) {
            $packages->setFrom($request->query->get('from'));
        }
        if ($request->query->has('to')) {
            $packages->setTo($request->query->get('to'));
        }
        if ($request->query->has('date')) {
            $packages->setDate($request->query->get('date'));
        }
        if ($request->query->has('prom_cd')) {
            $packages->setPromotion(strtoupper($request->query->get('prom_cd')));
        }
        $packages->setStay(14);
        $packages->setStayMatch(14);

        $results = $packages->getResults(false);

        return $this->render('yield/modal.html.twig', [
                    'results' => $results,
        ]);
    }

    /**
     * @Route("/yield/info/modal", name="yield_info_modal")
     */
    public function yieldInfoModalAction(Request $request) {
        $keyData = $request->query->get('key_data', null);

        $changes = $this->getDoctrine()->getRepository('AppBundle\Entity\PriceDefinition\Log\Change')->findByKeyData($keyData, ['id' => 'DESC']);

        $reservations = [];
        $conn = $this->get('doctrine.dbal.atcore_connection');

        $sql = "SELECT
                    pd.dep_dt, pd.stc_stk_dt, pd.stay,
                    pd.out_trans_inv_route_id, pd.in_trans_inv_route_id,
                    sellu.inv_unit_id
                FROM
                    atcomres.ar_pricedefinition pd
                    INNER JOIN atcomres.ar_sellunit sellu
                        ON sellu.sell_unit_id = pd.sell_unit_id
                WHERE
                    pd.key_data = :key_data";

        $stmt = $conn->prepare($sql);
        $stmt->bindValue('key_data', $keyData);
        $stmt->execute();
        $result = $stmt->fetch();

        if ($result) {
            $outStDt = new \Datetime($result['DEP_DT']);
            $outTransInvRouteId = $result['OUT_TRANS_INV_ROUTE_ID'];

            $accStDt = new \Datetime($result['STC_STK_DT']);
            $accInvId = $result['INV_UNIT_ID'];

            $inStDt = clone $accStDt;
            $inStDt->modify('+' . $result['STAY'] . ' days');
            $inTransInvRouteId = $result['IN_TRANS_INV_ROUTE_ID'];

            $inStDtAlt = clone $inStDt;
            $inStDtAlt->modify('+1 day');

            $sql = "SELECT
                        res.res_id, res.bkg_sts, res.origin_dt,
                        res.n_adu, res.n_chd, res.n_inf,
                        res.sell_prc, cur.cd CUR_CD,
                        res.stk_cost, res.prof_ex_vat
                    FROM
                        atcomres.ar_reservation res
                        INNER JOIN atcomres.ar_resservice ser_trs_out
                            ON ser_trs_out.res_id = res.res_id
                            AND ser_trs_out.ser_tp = 'TRS'
                            AND ser_trs_out.ser_sts = 'CON'
                            AND ser_trs_out.dir = 'OUT'
                        INNER JOIN atcomres.ar_resservice ser_trs_in
                            ON ser_trs_in.res_id = res.res_id
                            AND ser_trs_in.ser_tp = 'TRS'
                            AND ser_trs_in.ser_sts = 'CON'
                            AND ser_trs_in.dir = 'IN'
                        INNER JOIN atcomres.ar_resservice ser_acc
                            ON ser_acc.res_id = res.res_id
                            AND ser_acc.ser_tp = 'ACC'
                            AND ser_acc.ser_sts = 'CON'
                            INNER JOIN atcomres.ar_ressubservice sub_acc
                                ON sub_acc.res_ser_id = ser_acc.res_ser_id
                        INNER JOIN atcomres.ar_currency cur
                            ON cur.cur_id = res.sell_cur_id
                    WHERE
                        ser_trs_out.ser_id = :out_trans_inv_route_id
                            AND
                        TRUNC(ser_trs_out.st_dt) = :out_st_dt
                            AND
                        ser_trs_in.ser_id = :in_trans_inv_route_id
                            AND
                        TRUNC(ser_trs_in.st_dt) BETWEEN :in_st_dt AND :in_st_dt_alt
                            AND
                        sub_acc.inv_id = :inv_id
                            AND
                        ser_acc.st_dt = :acc_st_dt
                    ORDER BY
                        res.origin_dt DESC";

            $stmt = $conn->prepare($sql);
            $stmt->bindValue('out_trans_inv_route_id', $outTransInvRouteId);
            $stmt->bindValue('out_st_dt', $outStDt, 'date');
            $stmt->bindValue('in_trans_inv_route_id', $inTransInvRouteId);
            $stmt->bindValue('in_st_dt', $inStDt, 'date');
            $stmt->bindValue('in_st_dt_alt', $inStDtAlt, 'date');
            $stmt->bindValue('inv_id', $accInvId);
            $stmt->bindValue('acc_st_dt', $accStDt, 'date');

            $stmt->execute();
            $reservations = $stmt->fetchAll();
        } else {
            die('Something went wrong, key_data doesn\'t match...');
        }

        return $this->render('yield/packages/info_modal.html.twig', [
                    'changes' => $changes,
                    'reservations' => $reservations,
        ]);
    }

    /**
     * @Route("/yield/packages/log", name="yield_packages_log")
     */
    public function yieldPackagesLogAction(Request $request) {
        $batches = $this->getDoctrine()->getRepository('AppBundle\Entity\PriceDefinition\Log\Batch')->findBy([], ['updateDtTm' => 'DESC'], 5000);

        return $this->render('yield/packages/log.html.twig', [
                    'batches' => $batches,
        ]);
    }

    /**
     * @Route("/yield/packages/log/batch_modal", name="yield_packages_log_batch_modal")
     */
    public function yieldPackagesLogBatchModalAction(Request $request) {
        $batchId = $request->query->get('batch_id', null);

        $batch = $this->getDoctrine()->getRepository('AppBundle\Entity\PriceDefinition\Log\Batch')->find($batchId);

        $changes = $this->getDoctrine()->getRepository('AppBundle\Entity\PriceDefinition\Log\Change')->findByBatch($batch, ['keyData' => 'ASC']);

        return $this->render('yield/packages/log_batch_modal.html.twig', [
                    'batch' => $batch,
                    'changes' => $changes,
        ]);
    }

    private function getPaxOnDate($lookupDate) {
        $sql = sprintf("SELECT
                    trs.arr_air_cd, trs.cycle_dt,
                    trs.route_cd, SUM(ser.n_pax) AS pax,
                    SUM(TO_NUMBER(res.sell_prc, '999999999.99')) AS price,
                    res.bkg_sts
                FROM
                    ATCOMRES.DH_RESERVATION res
                        INNER JOIN ATCOMRES.DH_SERVICETRS trs
                        ON trs.res_id = res.res_id
                        AND trs.ver_num = res.ver_num
                            INNER JOIN ATCOMRES.DH_SERVICE ser
                            ON ser.res_id = trs.res_id
                            AND ser.ver_num = trs.ver_num
                            AND ser.res_ser_id = trs.res_ser_id
                		LEFT OUTER JOIN ATCOMRES.DH_RESERVATION res2 ON
                            res2.res_id = res.res_id
                                AND
                            res2.ver_num > res.ver_num
                                AND
                            TO_DATE(res2.mod_dt_tm, 'YYYY-MM-DD\"T\"hh24:MI:SS') <= TO_DATE('%1\$s 23:59:59', 'YYYY-MM-DD HH24:MI:SS')
        
                WHERE
                    res2.res_id IS NULL
                        AND
                    (
                        (
                            res.mod_dt_tm IS NULL
                                AND
                            TO_DATE(res.con_dt_tm, 'YYYY-MM-DD\"T\"hh24:MI:SS') <= TO_DATE('%1\$s 23:59:59', 'YYYY-MM-DD HH24:MI:SS')
                        )
                            OR
                        TO_DATE(res.mod_dt_tm, 'YYYY-MM-DD\"T\"hh24:MI:SS') <= TO_DATE('%1\$s 23:59:59', 'YYYY-MM-DD HH24:MI:SS')
                    )
                        AND
                    trs.dir_mth = 'OUT'
                        AND
                    ser.srch_prom_gp_cd IN ('%2\$s')
                        AND
                    TO_DATE(trs.cycle_dt, 'YYYY-MM-DD') BETWEEN TO_DATE('%3\$s', 'YYYY-MM-DD') AND TO_DATE('%4\$s', 'YYYY-MM-DD')
                    %5\$s
                GROUP BY
                    trs.cycle_dt, trs.route_cd, trs.flt_num, trs.dep_air_cd, trs.arr_air_cd, res.bkg_sts
                ORDER BY
                    trs.arr_air_cd, trs.cycle_dt, trs.route_cd", $lookupDate, implode($this->prom, "','"), $this->dateFrom, $this->dateTo, ($this->airport[0] != 'ALL') ? "AND trs.arr_air_cd IN ('" . implode($this->airport, "','") . "')" : ''
        );

        $conn = $this->get('doctrine.dbal.atcore_connection');
        $results = $conn->fetchAll($sql);
        return $results;
    }

    private function getPaxOnDateFromAr($lookupDate) {
        $endDt = new \Datetime($lookupDate);

        $placeholders = $binds = [];
        $index = 0;
        if ($this->promCd) {
            foreach ($this->promCds as $promCd) {
                $placeholders[] = ':prom' . $index;
                $binds['prom' . $index++] = $promCd;
            }
        }

        $sql = sprintf("SELECT
                            tr.route_cd,
                            pt.pt_cd as arr_cd,
                            tis.cycle_dt,
                            CASE res.bkg_sts
                              WHEN 'OPT' THEN 'BKG'
                              ELSE res.bkg_sts
                            END as bkg_sts,
                            sum(ser.n_pax) as pax,
                            sum(res.sell_prc) as price
                        FROM
                            atcomres.ar_resservice ser
                                inner join atcomres.ar_transinvroute tir
                                    on tir.trans_inv_route_id = ser.ser_id
                                    inner join atcomres.ar_transroute tr
                                        on tr.trans_route_id = tir.trans_route_id
                                        inner join atcomres.ar_transhead th
                                            on th.trans_head_id = tr.trans_head_id
                                        inner join atcomres.ar_point pt
                                            on pt.pt_id = tr.arr_pt_id
                                    inner join atcomres.ar_transinvsector tis
                                        on tis.trans_inv_sec_id = tir.dep_sec_id
                                inner join atcomres.ar_reservation res
                                    on res.res_id = ser.res_id
                                    inner join atcomres.ar_promotion prom
                                        on prom.prom_id = res.prom_id
                                left outer join atcomres.ar_resservice ser2
                                    on ser2.res_id = ser.res_id
                                    and ser2.ser_tp = ser.ser_tp
                                    and ser2.dir = ser.dir
                                    and ser2.cre_dt > ser.cre_dt
                                    and trunc(ser2.cre_dt) <= :end_dt
                        WHERE
                            ser2.res_ser_id is null
                                and
                            tr.dir_mth = 'OUT'
                                and
                            res.bkg_sts IN ('BKG','CNX','OPT')
                                and
                            ser.ser_tp = 'TRS'
                                and
                            TRUNC(ser.cre_dt) <= :end_dt
                                and
                            (:arr_cd is null OR pt.pt_cd = :arr_cd)
                            %s
                                and
                            tis.cycle_dt BETWEEN :cycle_st_dt AND :cycle_end_dt
                        GROUP BY
                            tr.route_cd,
                            pt.pt_cd,
                            tis.cycle_dt,
                            (CASE res.bkg_sts WHEN 'OPT' THEN 'BKG' ELSE res.bkg_sts END)
                        ORDER BY
                            tis.cycle_dt,
                            tr.route_cd,
                            (CASE res.bkg_sts WHEN 'OPT' THEN 'BKG' ELSE res.bkg_sts END)", count($placeholders) ? 'and prom.cd IN (' . implode(',', $placeholders) . ')' : ''
        );

        $conn = $this->get('doctrine.dbal.atcore_connection');

        $stmt = $conn->prepare($sql);
        $stmt->bindValue('end_dt', $endDt, 'date');
        $stmt->bindValue('cycle_st_dt', $this->cycleStDt, 'date');
        $stmt->bindValue('cycle_end_dt', $this->cycleEndDt, 'date');
        $stmt->bindValue('arr_cd', $this->arrCd);
        foreach ($binds as $placeholder => $value) {
            $stmt->bindValue($placeholder, $value);
        }

        $stmt->execute();
        $results = $stmt->fetchAll();

        return $results;
    }

    /**
     * @Route("/yield/reservations", name="yield_reservations")
     */
    public function yieldReservationsAction(Request $request) {
        $conn = $this->get('doctrine.dbal.atcore_connection');

        $table = [
            2239653 => 'BT',
            2239652 => 'SR',
            2239656 => 'LM',
            2239654 => 'SO',
            2239655 => 'HF',
            30345213 => 'UK'
        ];

        $currencies = [
            'BT' => 2226766,
            'SR' => 2226767,
            'LM' => 2226765,
            'SO' => 2226768,
            'HF' => 2226769,
            'UK' => 2226771
        ];

        $exchanges = [];

        $sql = sprintf("SELECT
                            ert.exch_table_id, ert.cur_id, ert.exch_rt
                        FROM
                            ATCOMRES.AR_EXCHANGERATETABLE ert
                                LEFT OUTER JOIN ATCOMRES.AR_EXCHANGERATETABLE ert2
                                    ON ert2.exch_table_id = ert.exch_table_id
                                    AND ert2.cur_id = ert.cur_id
                                    AND ert2.exch_mth = ert.exch_mth
                                    AND ert2.eff_dt > ert.eff_dt
                        WHERE
                            ert.exch_mth = 'SELL'
                                AND
                            ert2.exch_table_id IS NULL
                                AND
                            ert.cur_id IN (%s)
                                AND
                            ert.exch_table_id IN (%s)", implode($currencies, ','), implode(array_keys($table), ',')
        );

        $results = $conn->fetchAll($sql);
        foreach ($results as $result) {
            if (isset($currencies[$table[$result['EXCH_TABLE_ID']]])) {
                if ($currencies[$table[$result['EXCH_TABLE_ID']]] == $result['CUR_ID']) {
                    $exchanges[$table[$result['EXCH_TABLE_ID']]] = $result['EXCH_RT'];
                }
            }
        }
        $exchanges['ST'] = $exchanges['BT'];

        $sql = sprintf("SELECT
                            distinct res.res_id, to_char(res.origin_dt, 'YYYY-MM-DD HH24:MI:SS') AS origin_dt,
                            res.first_st_dt, res.last_end_dt,
                            res.bkg_sts,
                            LISTAGG(stc.cd, ',') WITHIN GROUP (ORDER BY stc.name) OVER (PARTITION BY res.res_id) as hotel_cd,
                            LISTAGG(stc.name, ', ') WITHIN GROUP (ORDER BY stc.name) OVER (PARTITION BY res.res_id) as hotel_name,
                            prom.cd AS prom_cd, res.sell_prc, res.stk_cost, cur.cd AS cur_cd,
                            res.n_adu, res.n_chd, res.n_inf
                        FROM
                            ATCOMRES.AR_RESERVATION res
                                INNER JOIN ATCOMRES.AR_PROMOTION prom
                                    ON prom.prom_id = res.prom_id
                                INNER JOIN ATCOMRES.AR_CURRENCY cur
                                    ON cur.cur_id = res.sell_cur_id
                                LEFT JOIN ATCOMRES.AR_RESSERVICE ser
                                    ON ser.res_id = res.res_id
                                    AND ser.ser_tp = 'ACC'
                                    AND ser.ser_sts = 'CON'
                                    INNER JOIN ATCOMRES.AR_SELLSTATIC sell
                                        ON sell.sell_stc_id = ser.ser_id
                                            INNER JOIN ATCOMRES.AR_STATICSTOCK stc
                                                ON stc.stc_stk_id = sell.stc_stk_id
                        WHERE
                            res.origin_dt > SYSDATE-1");

        $results = $conn->fetchAll($sql);

        $stockCodeCount = [];
        $reservations = [];
        foreach ($results as $result) {
            $reservations[] = $result;
            $stockCodes = explode(',', $result['HOTEL_CD']);
            foreach ($stockCodes as $stockCode) {
                if (!isset($stockCodeCount[$stockCode])) {
                    $stockCodeCount[$stockCode] = 0;
                }
                $stockCodeCount[$stockCode] += 1;
            }
        }

        return $this->render('yield/reservations.html.twig', [
                    'reservations' => $reservations,
                    'exchanges' => $exchanges,
                    'stock_count' => $stockCodeCount,
                    'stock_max' => max($stockCodeCount)
        ]);
    }

    /**
     * @Route("/yield/packages", name="yield_packages")
     */
    public function yieldPackagesAction(Request $request) {
        $maxResults = 5000;
        $maxResultsReached = false;

        if ($request->query->has('prom') && $request->query->has('from') && $request->query->has('to')) {
            $key_data = $request->query->get('key_data', null);

            $promotion = strtoupper($request->query->get('prom'));
            $dep_date_from = $request->query->get('from');
            $dep_date_to = $request->query->get('to');
            $out_dep_pt = $request->query->get('dep_pt', null);
            $out_arr_pt = $request->query->get('arr_pt', null);
            $flights = $request->query->get('flights', null);
            $sstay = $request->query->get('sstay', null);
            $estay = $request->query->get('estay', null);
            $hide_sale = $request->query->get('hide_sale', null);
            $hide_seats = $request->query->get('hide_seats', null);
            $hide_rooms = $request->query->get('hide_rooms', null);
            $hide_ht_name = $request->query->get('hide_ht_name', null);
            $hide_rm_name = $request->query->get('hide_rm_name', null);
            $show_del = $request->query->get('show_del', null);
            $remember_state = $request->query->get('remember_state', null);
            $stc_stk_cd = strtoupper($request->query->get('stc_stk_cd', null));
            $cost_data = $request->query->get('cost_data', null);  //hide or show cost details            
            $doSearch = true;
        } else {
            $doSearch = false;
            $costChecked = 0;
            $costData = '';
        }

        $packages = [];
        $search = [
            'key_data' => isset($key_data) ? $key_data : '',
            'prom' => isset($promotion) ? $promotion : '',
            'from' => isset($dep_date_from) ? $dep_date_from : date('d-M-Y'),
            'to' => isset($dep_date_to) ? $dep_date_to : date('d-M-Y'),
            'dep_pt' => isset($out_dep_pt) ? $out_dep_pt : '',
            'arr_pt' => isset($out_arr_pt) ? $out_arr_pt : '',
            'flights' => isset($flights) ? $flights : [],
            'sstay' => isset($sstay) ? $sstay : '',
            'estay' => isset($estay) ? $estay : '',
            'hide_sale' => isset($hide_sale) ? $hide_sale : '',
            'hide_seats' => isset($hide_seats) ? $hide_seats : 0,
            'hide_rooms' => isset($hide_rooms) ? $hide_rooms : 0,
            'hide_ht_name' => isset($hide_ht_name) ? $hide_ht_name : 0,
            'hide_rm_name' => isset($hide_rm_name) ? $hide_rm_name : 0,
            'show_del' => isset($show_del) ? $show_del : 0,
            'remember_state' => isset($remember_state) ? $remember_state : 0,
            'stc_stk_cd' => isset($stc_stk_cd) ? $stc_stk_cd : '',
        ];


        $promotions = [
            'Packages (Flight + Accomodation)' => [
                'BTLP' => 'Bravo Tours Load Price',
                'SRLP' => 'Solresor Load Price',
                'LMLP' => 'Matkavekka Load Price',
                'SOLP' => 'Solia Load Price',
                'HFLP' => 'Heimsferdir Load Price',
                'STLP' => 'Sun Tours Load Price',
                'UKLP' => 'Primera Holidays UK Load Price'
            ],
            'Specials' => [
                'LMFL' => 'Matkavekka Ferry Load Price',
                'LMTT' => 'Matkavekka Tema Trips', // New Matkaveka Tema Trips
                'LMTL' => 'Matkavekka Tema Trips Load Price', // New Matkaveka Tema Trips Load Price 
            ],
        ];

        $accommodations = [];

        $conn = $this->get('doctrine.dbal.atcore_connection');

        $sql = sprintf("SELECT
                            pt1.pt_cd, pt1.name,
                            pt2.pt_id AS country_id, pt2.name AS country_name
                        FROM
                            ATCOMRES.ar_transroute tr
                                INNER JOIN ATCOMRES.ar_point pt1
                                    ON pt1.pt_id = tr.dep_pt_id
                                        LEFT JOIN ATCOMRES.AR_POINT pt2
                                            ON pt2.pt_id = pt1.parent_id
                        WHERE
                            tr.dir_mth='OUT'
                        GROUP BY
                            pt1.pt_cd, pt1.name, pt2.pt_id, pt2.name
                        ORDER BY
                            pt1.pt_cd, pt1.name");

        $depAirportsByCountry = [];
        $results = $conn->fetchAll($sql);
        $depAirports = $results;
        foreach ($results as $result) {
            if (!isset($depAirportsByCountry[$result['COUNTRY_ID']])) {
                $depAirportsByCountry[$result['COUNTRY_ID']] = [
                    'name' => $result['COUNTRY_NAME'],
                    'airports' => []
                ];
            }
            $depAirportsByCountry[$result['COUNTRY_ID']]['airports'][$result['PT_CD']] = $result['NAME'];
        }

        $sql = sprintf("SELECT
                            pt1.pt_cd, pt1.name,
                            pt2.pt_id AS country_id, pt2.name AS country_name
                        FROM
                            ATCOMRES.ar_transroute tr
                                INNER JOIN ATCOMRES.ar_point pt1
                                    ON pt1.pt_id = tr.dep_pt_id
                                        LEFT JOIN ATCOMRES.AR_POINT pt2
                                            ON pt2.pt_id = pt1.parent_id
                        WHERE
                            tr.dir_mth='RET'
                        GROUP BY
                            pt1.pt_cd, pt1.name, pt2.pt_id, pt2.name
                        ORDER BY
                            pt1.pt_cd, pt1.name");

        $arrAirportsByCountry = [];
        $results = $conn->fetchAll($sql);
        $arrAirports = $results;
        foreach ($results as $result) {
            if (!isset($arrAirportsByCountry[$result['COUNTRY_ID']])) {
                $arrAirportsByCountry[$result['COUNTRY_ID']] = [
                    'name' => $result['COUNTRY_NAME'],
                    'airports' => []
                ];
            }
            $arrAirportsByCountry[$result['COUNTRY_ID']]['airports'][$result['PT_CD']] = $result['NAME'];
        }

        $sql = "SELECT
                    th.cd
                FROM
                    ATCOMRES.AR_TRANSHEAD th
                WHERE
                    th.end_dt > SYSDATE
                ORDER BY
                    th.cd";

        $results = $conn->fetchAll($sql);
        $availableFlights = $results;

        $user = $this->get('security.token_storage')->getToken()->getUser();
        $kernel = $this->get('kernel');

        $showAllowWarning = false;
        if ((in_array(strtolower($user->getUsername()), $this->allowedUsers) || $this->get('security.authorization_checker')->isGranted('ROLE_YIELD')) && $kernel->getEnvironment() == 'prod') {
            $showAllowWarning = true;
        }

        if ($doSearch) {
            if ($flights) {
                $flightsPlaceholder = [];
                $flightsBind = [];
                $index = 0;
                foreach ($flights as $flight) {
                    $flightsPlaceholders[] = ':flight' . $index;
                    $flightsBind['flight' . $index++] = $flight;
                }
            }

            $hint = '/*+ INDEX(pd AR_PRICEDEFINITION_SI10) */';
            if ($stc_stk_cd) {
                $hint = '/*+ INDEX(pd AR_PRICEDEFINITION_SI11) */';
            }
            if ($key_data) {
                $hint = '';
            }
            $costData = '';
            $costData1 = '';
            $costChecked = 1;
            //echo "cost Data::".$cost_data;
            if ($cost_data == '0') {
                $result['COST_BB_CD'] = '1';
                $result['SELL_BB_CD'] = '2';
                $result['COST_CBB'] = '3';
                $result['COST_SBB'] = '4';
                $result['CUR_CD'] = '5';
                $costChecked = 1;
            } else {
                // Fetch price cost cache details
                $costData = 'acs.COST_BB_CD,acs.SELL_BB_CD
                                ,acip.COST_CBB,acip.COST_SBB,acip.CUR_CD,';
                $costData1 = 'LEFT JOIN 
                                atcomres.ar_costcachesellunit acs ON acs.sell_unit_id = pd.sell_unit_id
                              LEFT JOIN 
                                atcomres.ar_costcacheincprc acip ON acip.sell_unit_id = pd.sell_unit_id 
';
                $costChecked = 0;
            }

            $sql = sprintf("SELECT %s
                                pd.key_data,pd.stay,
                                $costData
                                pd.acc_adu_prc,pd.acc_chd1_prc,pd.acc_chd2_prc,
                                pd.sell_unit_id,pd.out_dep_pt, pd.out_arr_pt,
                                pd.stc_stk_id, stc.name AS hotel_name, stc.cd as hotel_cd,
                                pd.dep_dt, pd.out_trans_cd,su.rm_id AS room_id,ctp.pair_remain,
                                COALESCE(ovrrm.name, rm.name) AS room_name, rm.cd AS rm_cd,
                                ysn.adu_sup, ysn.chd_sup, ysn.chd_sup_2,inv.alt_alloc, inv.alt_bkd,
                                invsub.alt_alloc AS shr_alloc,(invsub.alt_occ1_ob + invsub.alt_occ2_ob
                                + invsub.alt_occ3_ob + invsub.alt_occ4_ob) AS shr_bkd,ysn.hide_sale
                            FROM
                                ATCOMRES.AR_PRICEDEFINITION pd
                                    INNER JOIN ATCOMRES.AR_STATICSTOCK stc
                                        ON stc.stc_stk_id = pd.stc_stk_id
                                    INNER JOIN ATCOMRES.AR_SELLUNIT su
                                        ON su.sell_unit_id = pd.sell_unit_id
                                        INNER JOIN ATCOMRES.AR_ROOM rm
                                            ON rm.rm_id = su.rm_id
                                        LEFT JOIN ATCOMRES.AR_ROOM ovrrm
                                            ON ovrrm.rm_id = su.or_rm_id
                                        INNER JOIN ATCOMRES.AR_INVENTORY inv
                                            ON inv.stk_sub_id = su.inv_unit_id
                                            AND inv.inv_dt = pd.stc_stk_dt
                                            LEFT JOIN ATCOMRES.AR_INVENTORYSUB invsub
                                                ON invsub.stk_sub_id = inv.stk_sub_id
                                                AND invsub.inv_dt = inv.inv_dt
                                                LEFT JOIN ATCOMRES.AR_USERCODES usr
                                                    ON usr.user_cd_id = invsub.shr_id
                                    INNER JOIN ATCOMRES.AR_YIELDSUPPLEMENTNOW ysn
                                        ON ysn.key_data = pd.key_data
                                    LEFT JOIN ATCOMRES.AR_CACHETRANSPAIR ctp
                                        ON ctp.prom_id      = pd.prom_id
                                        AND ctp.cycle_dt    = pd.dep_dt
                                        AND ctp.prc_mth     = 'INC'
                                        AND ctp.out_trans_inv_route_id  = pd.out_trans_inv_route_id
                                        AND ctp.in_trans_inv_route_id   = pd.in_trans_inv_route_id
                                        AND ctp.out_trans_sell_rule_id  = pd.out_trans_sell_rule_id
                                        AND ctp.in_trans_sell_rule_id   = pd.in_trans_sell_rule_id
                                    $costData1
                            WHERE                            
                                (:key_data is null OR pd.key_data = :key_data)
                                    AND
                                (usr.cd IS NULL OR usr.cd = :usr_cd)
                                    AND
                                pd.dep_dt BETWEEN :dep_date_from AND :dep_date_to
                                    AND
                                pd.prom_cd = :promotion
                                    AND
                                (:del_fg is null OR pd.del_fg = :del_fg)
                                    AND
                                (:out_dep_pt is null OR pd.out_dep_pt = :out_dep_pt)
                                    AND
                                (:out_arr_pt is null OR pd.out_arr_pt = :out_arr_pt)
                                    AND
                                (:sstay is null OR pd.stay >= :sstay)
                                    AND
                                (:estay is null OR pd.stay <= :estay)
                                    AND
                                (:stc_stk_cd is null OR pd.stc_stk_cd = :stc_stk_cd)
                                %s
                            FETCH FIRST %d ROWS ONLY", $hint, $flights ? ' AND pd.out_trans_cd IN (' . implode(',', $flightsPlaceholders) . ')' : '', $maxResults
            );
            //echo $sql; exit;  
            $dep_date_from = new \DateTime($dep_date_from);
            $dep_date_to = new \DateTime($dep_date_to);

            $del_fg = $show_del ? null : 'N';

            $stmt = $conn->prepare($sql);


            $stmt->bindValue('del_fg', $del_fg);
            $stmt->bindValue('dep_date_from', $dep_date_from, 'date');
            $stmt->bindValue('dep_date_to', $dep_date_to, 'date');
            $stmt->bindValue('key_data', $key_data);
            $stmt->bindValue('promotion', $promotion);
            $stmt->bindValue('usr_cd', substr($promotion, 0, 2));
            $stmt->bindValue('out_dep_pt', $out_dep_pt);
            $stmt->bindValue('out_arr_pt', $out_arr_pt);
            $stmt->bindValue('sstay', $sstay);
            $stmt->bindValue('estay', $estay);
            $stmt->bindValue('stc_stk_cd', $stc_stk_cd);

            if ($flights) {
                foreach ($flightsBind as $placeholder => $flight) {
                    $stmt->bindValue($placeholder, $flight);
                }
            }

            $stmt->execute();
            $results = $stmt->fetchAll();

            $maxResultsReached = (count($results) == $maxResults) ? true : false;

            foreach ($results as $result) {
                if (!is_null($result['SHR_ALLOC'])) {
                    $avail = min($result['ALT_ALLOC'] - $result['ALT_BKD'], max($result['SHR_ALLOC'] - $result['SHR_BKD'], 0));
                } else {
                    $avail = $result['ALT_ALLOC'] - $result['ALT_BKD'];
                }
                $result['AVAILABLE'] = $avail;

                if ($hide_seats && $result['PAIR_REMAIN'] < 1) {
                    continue;
                }

                if ($hide_rooms && $result['AVAILABLE'] < 1) {
                    continue;
                }

                if ($hide_sale == 'hide' && $result['HIDE_SALE'] == 'Y') {
                    continue;
                }

                if ($hide_sale == 'show' && $result['HIDE_SALE'] == 'N') {
                    continue;
                }

                if (isset($packages[$result['KEY_DATA']])) {
                    print $result['KEY_DATA'] . ' exists!<br><br>';
                    print '<pre>';
                    print_r($packages);
                    print_r($result);
                    die();
                }
                if ($costChecked == 1) {
                    $result['COST_BB_CD'] = '';
                    $result['SELL_BB_CD'] = '';
                    $result['COST_CBB'] = '';
                    $result['COST_SBB'] = '';
                    $result['CUR_CD'] = '';
                }

                $shortNames = [
                    'stay' => $result['STAY'],
                    'cost_bb_cd' => $result['COST_BB_CD'],
                    'sell_bb_cd' => $result['SELL_BB_CD'],
                    'cost_cbb' => $result['COST_CBB'],
                    'sell_cbb' => $result['COST_SBB'],
                    'cur_id' => $result['CUR_CD'],
                    'a_prc' => $result['ACC_ADU_PRC'],
                    'c1_prc' => $result['ACC_CHD1_PRC'],
                    'c2_prc' => $result['ACC_CHD2_PRC'],
                    'su_id' => $result['SELL_UNIT_ID'],
                    'dep_cd' => $result['OUT_DEP_PT'],
                    'arr_cd' => $result['OUT_ARR_PT'],
                    'h_nm' => $result['HOTEL_NAME'],
                    'h_cd' => $result['HOTEL_CD'],
                    'dt' => $result['DEP_DT'],
                    'rm_nm' => $result['ROOM_NAME'],
                    'rm_cd' => $result['RM_CD'],
                    'a_sup' => $result['ADU_SUP'],
                    'c1_sup' => $result['CHD_SUP'],
                    'c2_sup' => $result['CHD_SUP_2'],
                    'rooms' => $result['AVAILABLE'],
                    'seats' => $result['PAIR_REMAIN'],
                    'hide_sale' => $result['HIDE_SALE'] == 'Y'
                        // 'hide_sale' => rand(1, 100) >= 50
                ];

                $packages[$result['KEY_DATA']] = $shortNames;

                if (!array_key_exists($result['HOTEL_CD'], $accommodations)) {
                    $accommodations[$result['HOTEL_CD']] = $result['HOTEL_NAME'];
                }
            }
        }

        asort($accommodations);

        return $this->render('yield/packages.html.twig', [
                    'promotions' => $promotions,
                    'dep_airports' => $depAirports,
                    'arr_airports' => $arrAirports,
                    'flights' => $availableFlights,
                    'allow_warning' => $showAllowWarning,
                    'packages' => $packages,
                    'accommodations' => $accommodations,
                    'search' => $search,
                    'max_results_reached' => $maxResultsReached,
                    'costCache' => $costChecked,
        ]);
    }

    /**
     * @Route("/yield/ajax/update", name="yield_ajax_update")
     */
    public function yieldAjaxUpdateAction(Request $request) {
        $response = new JsonResponse();

        $packagesJson = $request->request->get('packages', null);
        if ($packagesJson) {

            $doctrine = $this->getDoctrine();
            $em = $doctrine->getManager();

            // Log
            $user = $this->get('security.token_storage')->getToken()->getUser();
            $batch = new Batch($user);
            $em->persist($batch);
            $em->flush();

            $packages = json_decode($packagesJson);

            $ysr = new YieldSupRequest();
            $ysr->Control = new YieldSup\Control();
            $ysr->Yield_Supps = new YieldSup\Yield_Supps();

            foreach ($packages as $package) {
                $pkg_id = $package->pkg_id;
                $adu_sup = $package->adu_sup;
                $chd_sup_1 = $package->chd1_sup;
                $chd_sup_2 = $package->chd2_sup;
                $rm_cd = $package->rm_cd;
                $adu_prc = $package->adu_prc;
                $chd_prc_1 = $package->chd1_prc;
                $chd_prc_2 = $package->chd2_prc;

                $supplement = new YieldSup\Yield_Supp();
                $supplement->Pkg_ID = $pkg_id;
                $supplement->Adu_Sup = $adu_sup;
                $supplement->Chd_Sup_1 = $chd_sup_1;
                $supplement->Chd_Sup_2 = $chd_sup_2;

                $ysr->Yield_Supps->Yield_Supp[] = $supplement;

                // Log
                $change = new Change($pkg_id, $rm_cd, $adu_sup, $chd_sup_1, $chd_sup_2, $adu_prc, $chd_prc_1, $chd_prc_2);
                $change->setBatch($batch);
                $em->persist($change);
                $em->flush();
            }

            $serializer = $this->get('jms_serializer');
            $json = $serializer->serialize($ysr, 'json');

            $kernel = $this->get('kernel');
            if ($kernel->getEnvironment() != 'prod') {
                $server = '192.168.218.4';
                $environment = 'PRMUAT';
            } else {
                $server = '192.168.218.30';
                $environment = 'PRMPROD';
                if (!in_array(strtolower($user->getUsername()), $this->allowedUsers) && !$this->get('security.authorization_checker')->isGranted('ROLE_YIELD')) {
                    return $response->setData(json_encode([
                                'Response' => [
                                    'Status' => 'ERROR',
                                    'Error' => [
                                        'Err_No' => '998',
                                        'Err_Desc' => 'Not allowed to change prices on production with the current user. If you want to help test this system contact anders@primerait.com.'
                                    ]
                                ]
                    ]));
                }
            }

            $client = new \nusoap_client('http://' . $server . '/' . $environment . '/YieldPricingWS/YieldPricing.asmx?WSDL', true);
            $wsResponse = $client->call('YieldPricing', ['Request' => json_decode($json, true)]);

            return $response->setData(json_encode($wsResponse));
        } else {
            return $response->setData(json_encode([
                        'Response' => [
                            'Status' => 'ERROR',
                            'Error' => [
                                'Err_No' => '999',
                                'Err_Desc' => 'Utils: Missing input data.'
                            ]
                        ]
            ]));
        }
    }

    /**
     * @Route("/yield/packages/modal/sales", name="yield_packages_modal_sales")
     */
    public function yieldPackagesModalSalesAction(Request $request) {
        /*
          $keyData = $request->query->get('key_data', null);

          $conn = $this->get('doctrine.dbal.atcore_connection');

          $sql = "SELECT
          pd.prom_cd, pd.dep_dt, pd.stay,
          pd.stc_stk_id, pd.rm_cd RM_GRP_CD,
          tp.out_trans_inv_route_id, tp.in_trans_inv_route_id
          FROM
          ATCOMRES.AR_PRICEDEFINITION pd
          INNER JOIN ATCOMRES.AR_TRANSPAIR tp
          ON tp.transpair_id = pd.transpair_id
          WHERE
          pd.key_data = :key_data";

          $stmt = $conn->prepare($sql);
          $stmt->bindValue('key_data', $keyData);
          $stmt->execute();
          $result = $stmt->fetch();

          $sql = "SELECT
          res.res_id
          FROM
          ATCOMRES.AR_RESERVATION res
          INNER JOIN ATCOMRES.AR_RESSERVICE ser_acc
          ON ser_acc.res_id = res.res_id
          AND ser_acc.ser_tp = 'ACC'
          AND ser_acc.ser_sts = 'CON'
          INNER JOIN ATCOMRES.AR_SELLSTATIC sell
          ON sell.sell_stc_id = ser_acc.ser_id
          INNER JOIN ATCOMRES.AR_STATICSTOCK stc
          ON stc.stc_stk_id = sell.stc_stk_id
          INNER JOIN ATCOMRES.AR_RESSUBSERVICE subser
          ON subser.res_ser_id = ser_acc.res_ser_id
          INNER JOIN ATCOMRES.AR_SELLUNIT sellu
          ON sellu.sell_unit_id = subser.inv_id
          INNER JOIN ATCOMRES.AR_STATICROOM srm
          ON srm.rm_id = sellu.rm_id
          AND srm.stc_stk_id = stc.stc_stk_id
          INNER JOIN ATCOMRES.AR_USERCODES rm_grp
          ON rm_grp.user_cd_id = srm.rm_gp_id
          INNER JOIN ATCOMRES.AR_RESSERVICE ser_trs_out
          ON ser_trs_out.res_id = res.res_id
          AND ser_trs_out.ser_tp = 'TRS'
          AND ser_trs_out.ser_sts = 'CON'
          AND ser_trs_out.dir = 'OUT'
          INNER JOIN ATCOMRES.AR_RESSERVICE ser_trs_in
          ON ser_trs_in.res_id = res.res_id
          AND ser_trs_in.ser_tp = 'TRS'
          AND ser_trs_in.ser_sts = 'CON'
          AND ser_trs_in.dir = 'IN'
          WHERE
          stc.stc_stk_id = :stc_stk_id
          AND
          rm_grp.cd = :rm_grp_cd
          AND
          ser_acc.ser_len = :stay
          AND
          ser_trs_out.st_dt = :st_dt
          AND
          ser_trs_out.ser_id = :out_trans_inv_route_id
          AND
          ser_trs_in.ser_id = :in_trans_inv_route_id
          ";

          $stmt = $conn->prepare($sql);
          $stmt->bindValue('route_cd', $routeCd);

          $stmt->execute();
          $results = $stmt->fetchAll();

          return $this->render('yield/routes.html.twig', [
          'route_cd' => $routeCd,
          'route' => $route,
          'global_rules' => $rules,
          ]);
         */
    }

    /**
     * @Route("/yield/routes", name="yield_routes")
     */
    public function yieldRoutesAction(Request $request) {
        $routeCd = '';
        $route = [];
        $rules = [];

        if ($request->query->has('route_cd')) {
            $routeCd = strtoupper($request->query->get('route_cd'));

            $conn = $this->get('doctrine.dbal.atcore_connection');

            $sql = "SELECT
                        tr.route_cd, to_char(tis.cycle_dt, 'YYYY-MM-DD') as cycle_dt,
                        tis.alt, tis.opt, tis.bkd,
                        ti.alt AS rule_alt, ti.opt AS rule_opt,
                        ti.bkd AS rule_bkd, tsr.rule, tsr.name
                    FROM
                        ATCOMRES.ar_transinvsector tis,
                        ATCOMRES.ar_transhead th,
                        ATCOMRES.ar_transroute tr,
                        ATCOMRES.ar_transinvroute tir,
                        ATCOMRES.ar_transinvshareryield tisy,
                        ATCOMRES.ar_transinvyieldsell tiys,
                        ATCOMRES.ar_transsellrule tsr,
                        ATCOMRES.ar_transinventory ti,
                        ATCOMRES.ar_point pt1, 
                        ATCOMRES.ar_point pt2
                    WHERE
                        substr(tr.route_cd,0,8) = :route_cd
                            and
                        tis.cycle_Dt >= SYSDATE
                            and
                        tr.dir_mth='OUT'
                            and
                        th.trans_head_id = tr.trans_head_id
                            and
                        tis.trans_head_id=th.trans_head_id
                            and
                        tis.trans_inv_sec_id=tir.dep_sec_id
                            and
                        tis.dep_pt_id=pt1.pt_id
                            and
                        tis.arr_pt_id=pt2.pt_id
                            and
                        tir.trans_route_id= tr.trans_route_id
                            and
                        tisy.yield_inv_Id=tir.yield_inv_id
                            and
                        tiys.trans_inv_sharer_yield_id=tisy.trans_inv_sharer_yield_id
                            and
                        ti.box_id=tiys.box_id
                            and
                        tsr.trans_sell_rule_id=tiys.trans_sell_rule_id
                            and
                        ti.alt > 0
                            and
                        tir.sale_sts = 'ON'
                            and
                        tis.sale_sts = 'ON'
                    ORDER BY
                        tis.cycle_dt,tsr.rule";

            $stmt = $conn->prepare($sql);
            $stmt->bindValue('route_cd', $routeCd);

            $stmt->execute();
            $results = $stmt->fetchAll();

            foreach ($results as $result) {
                if (!isset($route[$result['CYCLE_DT']])) {
                    $route[$result['CYCLE_DT']] = [
                        'rules' => [],
                        'rules_alt' => 0,
                        'rules_opt' => 0,
                        'rules_bkd' => 0,
                        'alt' => $result['ALT'],
                        'opt' => $result['OPT'],
                        'bkd' => $result['BKD'],
                    ];
                }

                $route[$result['CYCLE_DT']]['rules_alt'] += $result['RULE_ALT'];
                $route[$result['CYCLE_DT']]['rules_opt'] += $result['RULE_OPT'];
                $route[$result['CYCLE_DT']]['rules_bkd'] += $result['RULE_BKD'];

                $route[$result['CYCLE_DT']]['rules'][$result['RULE']] = [
                    'alt' => $result['RULE_ALT'],
                    'opt' => $result['RULE_OPT'],
                    'bkd' => $result['RULE_BKD'],
                ];

                if (!isset($rules[$result['RULE']])) {
                    $rules[$result['RULE']] = $result['NAME'];
                }
            }
        }

        ksort($rules);

        return $this->render('yield/routes.html.twig', [
                    'route_cd' => $routeCd,
                    'route' => $route,
                    'global_rules' => $rules,
        ]);
    }

    /**
     * @Route("/yield/offers", name="yield_offers")
     */
    public function yieldOffersAction(Request $request) {
        $conn = $this->get('doctrine.dbal.atcore_connection');
        $offers = [];
        $flag = true;

        $arrCd = strtoupper($request->query->get('arr_cd'));
        $accomCd = strtoupper($request->query->get('accom_cd'));
        $stay = $request->query->get('stay');
        $stDt = $request->query->get('st_dt');
        $endDt = $request->query->get('end_dt');
        $bkgStSt = $request->query->get('bkg_st_dt');
        $bkgEndDt = $request->query->get('bkg_end_dt');

        //When no search filters are given
        if ($arrCd == null && $accomCd == null && $stay == null && $stDt == null && $endDt == null && $bkgStSt == null && $bkgEndDt == null) {
            $sql = "SELECT off.name
                ,off.st_dt
                ,off.end_dt
                ,off.bk_from
                ,off.bk_to
                ,off.stay
                ,off.min_stay
                ,off.max_stay
                ,off.load_dt_tm
                ,CASE WHEN NVL(off.free,0) > 1 THEN off.free || ' nights'
                      ELSE NVL(off.free,0) || ' night'
                 END AS FREE
                ,CASE WHEN NVL(off.stay,0) > 0 THEN ROUND (NVL(off.free,0) / off.stay * 100)
                      ELSE ROUND (NVL(off.free,0) / 1 * 100)
                 END AS FREE_PCT
                ,stc.name ||' ('||stc.cd||')' AS accom
                ,CASE WHEN nvl(off.rm1_id,0 ) > 0 AND nvl(off.rm2_id,0 ) > 0 THEN 'Multiple'
                      ELSE nvl( rm.cd,'-' )
                 END AS rm_cd
           FROM atcomres.ar_offer off
          INNER JOIN atcomres.ar_sellstatic sell     ON sell.sell_stc_id   =off.offer_link_id
          INNER JOIN atcomres.ar_staticstock stc     ON stc.stc_stk_id   =sell.stc_stk_id
           LEFT JOIN atcomres.ar_room rm             ON rm.rm_id   = off.rm1_id
          WHERE off.load_dt_tm > SYSDATE - 7
          ORDER BY off.load_dt_tm DESC";


            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll();

            foreach ($results as $result) {
                $offers[] = [
                    'name' => $result['NAME'],
                    'accom' => $result['ACCOM'],
                    'rm_cd' => $result['RM_CD'],
                    'details' => $result['FREE'],
                    'details_pct' => $result['FREE_PCT'],
                    'stay' => $result['STAY'],
                    'bk_from' => $result['BK_FROM'],
                    'bk_to' => $result['BK_TO'],
                    'st_dt' => $result['ST_DT'],
                    'end_dt' => $result['END_DT'],
                    'load_dt_tm' => $result['LOAD_DT_TM'],
                ];
            }

            $sql = "SELECT usr.name   
                ,sup.bk_from_dt
                ,sup.bk_to_dt
                ,sup.load_dt_tm
                ,supd.st_dt
                ,supd.end_dt
                ,NVL(rm.cd,'-') AS rm_cd
                ,stc.name ||' ('||stc.cd||')' AS accom
                ,sup.min_stay||'-'||sup.max_stay AS stay
                ,CASE WHEN NVL(supd.rt_basis,'NA') = 'AMT' THEN NVL(supd.rt,'')||' '||cur.cd||'/'||supd.rt_tp
                      WHEN NVL(supd.rt_basis,'NA') = 'PER' THEN NVL(supd.rt,'')||'%'
                      ELSE 'Unknown'
                 END || CASE WHEN NVL(usrbb.cd,'NA') <> 'NA' THEN ' ('||usrbb.cd||')' 
                        END AS FREE
                ,CASE WHEN NVL(supd.rt_basis,'NA') = 'PER' THEN NVL(supd.rt,1) * (-1)
                       ELSE 0
                 END AS FREE_PCT
           FROM atcomres.ar_sellstaticsup sup
          INNER JOIN atcomres.ar_sellstaticsupdtl supd   ON supd.sell_stc_sup_id   =sup.sell_stc_sup_id
          INNER JOIN atcomres.ar_usercodes usr           ON usr.user_cd_id   =sup.sup_cd_id
          INNER JOIN atcomres.ar_sellstatic sell         ON sell.sell_stc_id   =sup.sell_stc_id
          INNER JOIN atcomres.ar_staticstock stc         ON stc.stc_stk_id   =sell.stc_stk_id
          INNER JOIN atcomres.ar_promotion prom          ON prom.prom_id   =sell.prom_id
           LEFT JOIN atcomres.ar_room rm                 ON rm.rm_id   =sup.target_id
           LEFT JOIN atcomres.ar_currency cur            ON cur.cur_id   =sup.cur_cd_id
           LEFT JOIN atcomres.ar_usercodes usrbb         ON usrbb.user_cd_id   =sup.bb_cd_id
          WHERE sup.ebo_fg   ='Y'
            AND sup.load_dt_tm >= SYSDATE - 7
          ORDER BY sup.load_dt_tm DESC";

            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll();

            foreach ($results as $result) {
                $offers[] = [
                    'name' => $result['NAME'],
                    'accom' => $result['ACCOM'],
                    'rm_cd' => $result['RM_CD'],
                    'details' => $result['FREE'],
                    'details_pct' => $result['FREE_PCT'],
                    'stay' => $result['STAY'],
                    'bk_from' => $result['BK_FROM_DT'],
                    'bk_to' => $result['BK_TO_DT'],
                    'st_dt' => $result['ST_DT'],
                    'end_dt' => $result['END_DT'],
                    'load_dt_tm' => $result['LOAD_DT_TM'],
                ];
            }

            $flag = true;
        }

        //When search filters are given
        else {
            $sql = "SELECT name
                        ,st_dt
                        ,end_dt
                        ,bk_from
                        ,bk_to
                        ,stay
                        ,load_dt_tm
                        ,free
                        ,free_pct
                        ,accom
                        ,rm_cd 
                 FROM(
                 SELECT off.name         AS NAME
                        ,off.st_dt       AS ST_DT
                        ,off.end_dt      AS END_DT
                        ,off.bk_from     AS BK_FROM
                        ,off.bk_to       AS BK_TO
                        ,TO_CHAR(off.stay) AS STAY
                        ,off.load_dt_tm  AS LOAD_DT_TM
                        ,CASE WHEN NVL(off.free,0) > 1 THEN off.free || ' nights'
                              ELSE NVL(off.free,0) || ' night'
                         END AS FREE
                        ,CASE WHEN NVL(off.stay,0) > 0 THEN ROUND (NVL(off.free,0) / off.stay * 100)
                              ELSE ROUND (NVL(off.free,0) / 1 * 100)
                         END AS FREE_PCT
                        ,stc.name ||' ('||stc.cd||')' AS accom
                        ,CASE WHEN nvl(off.rm1_id,0 ) > 0 AND nvl(off.rm2_id,0 ) > 0 THEN 'Multiple'
                              ELSE nvl( rm.cd,'-' )
                         END AS rm_cd
                   FROM atcomres.ar_offer off
                  INNER JOIN atcomres.ar_sellstatic sell     ON sell.sell_stc_id   =off.offer_link_id
                  INNER JOIN atcomres.ar_staticstock stc     ON stc.stc_stk_id   =sell.stc_stk_id
                   LEFT JOIN atcomres.ar_room rm             ON rm.rm_id   = off.rm1_id
                   left join atcomres.ar_statictransport stt ON stt.stc_stk_id = stc.stc_Stk_id
                   left join atcomres.ar_point pt            ON pt.pt_id = stt.pt_id
                  WHERE 1=1 
                    AND (:arr_cd IS NULL OR pt.pt_cd IN (:arr_cd) )	
                    AND (:accom_cd IS NULL OR stc.cd IN(:accom_cd))
                    AND (:stay IS NULL OR off.stay = :stay )
                    --AND (:st_dt IS NULL OR off.st_dt >= :st_dt)
                    AND (:st_dt IS NULL OR off.st_dt >= TO_DATE(:st_dt,'DD-MM-YYYY'))
                    AND (:end_dt IS NULL OR off.end_dt < TO_DATE(:end_dt,'DD-MM-YYYY')+1)
                    AND (:bkg_st_dt IS NULL OR off.bk_from >= TO_DATE(:bkg_st_dt,'DD-MM-YYYY'))
                    AND (:bkg_end_dt IS NULL OR off.bk_to  < TO_DATE(:bkg_end_dt,'DD-MM-YYYY')+1)
                   UNION ALL
                  SELECT usr.name        AS NAME
                        ,supd.st_dt      AS ST_DT
                        ,supd.end_dt     AS END_DT
                        ,sup.bk_from_dt  AS BK_FROM
                        ,sup.bk_to_dt    AS BK_TO
                        ,sup.min_stay||'-'||sup.max_stay AS stay
                        ,sup.load_dt_tm  AS LOAD_DT_TM
                        ,CASE WHEN NVL(supd.rt_basis,'NA') = 'AMT' THEN NVL(supd.rt,'')||' '||cur.cd||'/'||supd.rt_tp
                              WHEN NVL(supd.rt_basis,'NA') = 'PER' THEN NVL(supd.rt,'')||'%'
                              ELSE 'Unknown'
                         END || CASE WHEN NVL(usrbb.cd,'NA') <> 'NA' THEN ' ('||usrbb.cd||')' 
                                END AS FREE
                         ,CASE WHEN NVL(supd.rt_basis,'NA') = 'PER' THEN NVL(supd.rt,1) * (-1)
                               ELSE 0
                         END AS FREE_PCT
                        ,stc.name ||' ('||stc.cd||')' AS accom
                        ,NVL(rm.cd,'-') AS rm_cd
                   FROM atcomres.ar_sellstaticsup sup
                  INNER JOIN atcomres.ar_sellstaticsupdtl supd   ON supd.sell_stc_sup_id   =sup.sell_stc_sup_id
                  INNER JOIN atcomres.ar_usercodes usr           ON usr.user_cd_id   =sup.sup_cd_id
                  INNER JOIN atcomres.ar_sellstatic sell         ON sell.sell_stc_id   =sup.sell_stc_id
                  INNER JOIN atcomres.ar_staticstock stc         ON stc.stc_stk_id   =sell.stc_stk_id
                  INNER JOIN atcomres.ar_promotion prom          ON prom.prom_id   =sell.prom_id
                   LEFT JOIN atcomres.ar_room rm                 ON rm.rm_id   =sup.target_id
                   LEFT JOIN atcomres.ar_currency cur            ON cur.cur_id   =sup.cur_cd_id
                   LEFT JOIN atcomres.ar_usercodes usrbb         ON usrbb.user_cd_id   =sup.bb_cd_id
                   left join atcomres.ar_statictransport stt     ON stt.stc_stk_id = stc.stc_Stk_id
                   left join atcomres.ar_point pt                ON pt.pt_id = stt.pt_id
                  WHERE sup.ebo_fg   ='Y'
                    AND (:arr_cd IS NULL OR pt.pt_cd IN (:arr_cd))
                    AND (:accom_cd IS NULL OR stc.cd IN (:accom_cd))
                    AND (:stay IS NULL OR :stay BETWEEN sup.min_stay AND sup.max_stay)
                    AND (:st_dt IS NULL OR supd.st_dt >= TO_DATE(:st_dt,'DD-MM-YYYY'))
                    AND (:end_dt IS NULL OR supd.end_dt < TO_DATE(:end_dt,'DD-MM-YYYY')+1)
                    AND (:bkg_st_dt IS NULL OR sup.bk_from_dt >= TO_DATE(:bkg_st_dt,'DD-MM-YYYY'))
                    AND (:bkg_end_dt IS NULL OR sup.bk_to_dt < TO_DATE(:bkg_end_dt,'DD-MM-YYYY')+1)
                 )
                 ORDER BY LOAD_DT_TM DESC";

            $stmt = $conn->prepare($sql);
            $stmt->bindValue('arr_cd', $arrCd);
            $stmt->bindValue('accom_cd', $accomCd);
            $stmt->bindValue('stay', $stay);
            $stmt->bindValue('st_dt', $stDt);
            $stmt->bindValue('end_dt', $endDt);
            $stmt->bindValue('bkg_st_dt', $bkgStSt);
            $stmt->bindValue('bkg_end_dt', $bkgEndDt);

            $stmt->execute();
            $results = $stmt->fetchAll();

            foreach ($results as $result) {
                $offers[] = [
                    'name' => $result['NAME'],
                    'accom' => $result['ACCOM'],
                    'rm_cd' => $result['RM_CD'],
                    'details' => $result['FREE'],
                    'details_pct' => $result['FREE_PCT'],
                    'stay' => $result['STAY'],
                    'bk_from' => $result['BK_FROM'],
                    'bk_to' => $result['BK_TO'],
                    'st_dt' => $result['ST_DT'],
                    'end_dt' => $result['END_DT'],
                    'load_dt_tm' => $result['LOAD_DT_TM'],
                ];
            }

            $flag = false;
        }

        //Return to VIEW
        return $this->render('yield/offers.html.twig', [
                    'search' => $request->query->all(),
                    'offers' => $offers,
                    'flag' => $flag
        ]);
    }

    /**
     * @Route("/yield/transport", name="yield_transport")
     */
    public function transport(Request $request) {
        $transData = array();
        $dep_cd = '';
        $arr_cd = '';
        $head_cd = '';
        $st_dtt = '';
        $s = '';
        $e = '';
        $costprc = '';
        
        $table = $this->getParameter('exch_table_ids');
        $currencies = $this->getParameter('currency_ids');

        if ($request->query->has('head_cd')) {
            
            $promCd = strtoupper($request->query->get('prom_cd'));

            $dep_cdd = strtoupper($request->query->get('dep_cd'));
            $dep_cd = $dep_cdd != '' ? $dep_cdd : "";

            $arr_cdd = strtoupper($request->query->get('arr_cd'));
            $arr_cd = $arr_cdd != '' ? $arr_cdd : "";

            $head_cdd = strtoupper($request->query->get('head_cd'));
            $head_cd_q = "'" . $head_cdd . "'";
            $head_cd = $head_cdd != '' ? $head_cd_q : "NULL";

            $st_dt = "'" . strtoupper($request->query->get('st_dt')) . "'";
            $end_dt = "'" . strtoupper($request->query->get('end_dt')) . "'";
            $conn = $this->get('doctrine.dbal.atcore_connection');

            $s = date("m-d-Y", strtotime($request->query->get('st_dt'))); //
            $dd = explode("-", $s);
            $startDate = $dd['0'] . $dd['1'] . $dd['2'];

            $ss = date("m-d-Y", strtotime($request->query->get('end_dt'))); //
            $ddd = explode("-", $ss);
            $endDate = $ddd['0'] . $ddd['1'] . $ddd['2'];

            $s = $request->query->get('st_dt');
            $e = $request->query->get('end_dt');
            
            $EURCurChecked = $request->query->get('EUR_Cur',null);
            
            //Getting currency code
            $curCdSql = "SELECT DISTINCT
                            cur.cd AS Curr_Cd
                        FROM
                            atcomres.ar_promotion prm
                            LEFT JOIN atcomres.ar_product prd ON prm.prd_id = prd.prd_id
                            LEFT JOIN atcomres.ar_currency cur ON cur.cur_id = prd.sell_cur_cd_id
                        WHERE
                            substr(prm.cd,1,2) = '$promCd'";
            $stmtcurCd = $conn->prepare($curCdSql);
            $stmtcurCd->execute();
            $resultcurCd = $stmtcurCd->fetchAll();
            
            $currCd = $resultcurCd['0']['CURR_CD'];
            
            //Currency calculation
            if($EURCurChecked){
                $exTableId = $promCd == 'ST' ? 2239653 : array_search($promCd, $table); 
                $curId = $currencies["$promCd"];

                $cursql = "SELECT
                                exch_rt
                            FROM
                                (
                                    SELECT
                                        *
                                    FROM
                                        atcomres.ar_exchangeratetable ex
                                    WHERE
                                        1 = 1
                                        AND   ex.exch_mth = 'SELL'
                                        AND   ex.exch_table_id = $exTableId
                                        AND   ex.cur_id = $curId
                                    ORDER BY
                                        ex.eff_dt DESC
                                )
                            WHERE
                                ROWNUM < 2";
                $stmtcur = $conn->prepare($cursql);
                $stmtcur->execute();
                $resultscur = $stmtcur->fetchAll();
                
                $exchRate = $resultscur['0']['EXCH_RT'];
                
                $currCd = 'EUR';
            }
            else $exchRate = 1;            
            
            //After Searching the result Query			
            $sql = "SELECT Dep_Day
                    ,Dep_Dt
                    ,Ext_Int_Sts
                    ,Route_Cd
                    ,Dep_Tm
                    ,Arr_Tm
                    ,Flt_Tm
                    ,Flight
                    ,Maxqty
                    ,Qty
                    ,Opt
                    ,Bkd
                    ,Man
                    ,Rem
                    ,Total
                    ,Add_Sales
                    ,(Total + Add_Sales) AS Total_Sales
                    ,Cost_Prc
                    ,(Cost_Prc * Maxqty) AS Flight_Cost
                    ,Gc
                    ,(Gc + Cost_Prc) AS Sales_Price
                    ,(Gc + Cost_Prc) * (Total + Add_Sales) AS Sales
                    ,(Cost_Prc * Maxqty) / (CASE WHEN (Gc + Cost_Prc) = 0 THEN 1 ELSE(Gc + Cost_Prc) END) AS Breakeven
                    ,((Gc + Cost_Prc) * (Total + Add_Sales)) - (Cost_Prc * Maxqty) AS Pl
                FROM (SELECT /*+ FIRST_ROWS(10) */
                      DISTINCT To_Char(Tis.Dep_Dt_Tm, 'Dy') AS Dep_Day
                              ,To_Char(Trunc(Tis.Dep_Dt_Tm), 'DD-Mon-YYYY') AS Dep_Dt
                              ,Initcap(Tir.Sale_Sts) || '/' || Initcap(Tir.Int_Sts) AS Ext_Int_Sts
                              ,Tr.Route_Cd AS Route_Cd
                              ,To_Char(Tis.Dep_Dt_Tm, 'HH:MI') AS Dep_Tm
                              ,To_Char(Tis.Arr_Dt_Tm, 'HH:MI') AS Arr_Tm
                              ,Lpad(Tir.Utc_Dur_Hour, 2, '0') || ':' || Lpad(Tir.Utc_Dur_Min, 2, 0) AS Flt_Tm
                              ,Tir.Route_Num AS Flight
                              ,Tiys.Alt_Max AS Maxqty
                              ,Tis.Alt AS Qty
                              ,Tis.Opt AS Opt
                              ,Tis.Bkd AS Bkd
                              ,Nvl(Tis.Man_Seats, 0) AS Man
                              ,Tis.Alt - Tis.Bkd AS Rem
                              ,Tis.Bkd + Nvl(Tis.Man_Seats, 0) AS Total
                              ,0 AS Add_Sales
                              ,Tiys.Cost_Prc
                              ,0 AS Gc
                        FROM Atcomres.Ar_Transhead Th
                        LEFT JOIN Atcomres.Ar_Transroute Tr             ON Th.Trans_Head_Id = Tr.Trans_Head_Id
                        LEFT JOIN Atcomres.Ar_Transinvroute Tir         ON Tr.Trans_Route_Id = Tir.Trans_Route_Id
                        LEFT JOIN Atcomres.Ar_Transinvsector Tis        ON (Tis.Trans_Head_Id = Th.Trans_Head_Id AND Tis.Trans_Inv_Sec_Id = Tir.Dep_Sec_Id)
                        LEFT JOIN Atcomres.Ar_Transinvshareryield Tiys  ON Tiys.Yield_Inv_Id = Tir.Yield_Inv_Id
                        LEFT JOIN Atcomres.Ar_Resservice Rs             ON Tir.Trans_Inv_Route_Id = Rs.Ser_Id
                        LEFT JOIN Atcomres.Ar_Promotion Prom            ON Prom.Prom_Id = Rs.Prom_Id
                       WHERE Th.Trans_Tp = 'FLT'
                         AND Tr.Dir_Mth  = 'OUT'
                         AND Rs.Ser_Tp   = 'TRS'
                      AND ('" . $promCd . "' IS NULL OR prom.prom_id IN ( SELECT DISTINCT prom_id FROM atcomres.ar_promotion WHERE '" . $promCd . "' = 'ALL' OR substr( cd,1,2 ) = '" . $promCd . "' ))
                      AND ($head_cd IS NULL OR th.cd = $head_cd )
                      AND ('" . $arr_cd . "' IS NULL OR tr.arr_pt_id IN (SELECT pt_id FROM atcomres.ar_point WHERE pt_tp = 'AIR' AND pt_cd ='" . $arr_cd . "'))
                      AND ('" . $dep_cd . "' IS NULL OR tr.dep_pt_id IN (SELECT pt_id FROM atcomres.ar_point WHERE pt_tp ='AIR'  AND pt_cd   ='" . $dep_cd . "'))
                      AND tis.dep_dt_tm >= TO_DATE( " . $st_dt . ",'DD-MON-YYYY' )
                      AND tis.dep_dt_tm < TO_DATE( " . $end_dt . ",'DD-MON-YYYY' ) + 1
                      ORDER BY To_Char(Tis.Dep_Dt_Tm, 'HH:MI')
                               ,Tr.Route_Cd)";

            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll();
            $totalRecords = count($results);
            //After Searching the result Data
            foreach ($results as $key => $value) {
                $transData[] = array(
                    "Day" => $value['DEP_DAY'],
                    "Date" => $value['DEP_DT'],
                    "Sale_Sts" => $value['EXT_INT_STS'],
                    "Route" => $value['ROUTE_CD'],
                    "Dep" => $value['DEP_TM'],
                    "Arr" => $value['ARR_TM'],
                    "Time" => $value['FLT_TM'],
                    "Flight" => $value['FLIGHT'],
                    "Max_Qty" => $value['MAXQTY'],
                    "Qty" => $value['QTY'],
                    "Opt" => $value['OPT'],
                    "Bkd" => $value['BKD'],
                    "Man_Seats" => $value['MAN'],
                    "Rem" => $value['REM'],
                    "Total" => $value['TOTAL'],
                    "Add_Sales" => $value['ADD_SALES'],
                    "Total_Sales" => $value['TOTAL_SALES'],
                    "Cost_per_Seat" => round($value['COST_PRC'] / $exchRate),
                    "Flight_Cost" => round($value['FLIGHT_COST'] / $exchRate),
                    "GC" => round($value['GC'] * $exchRate),
                    "Sales_Price" => round($value['SALES_PRICE'] / $exchRate),
                    "Sales" => round($value['SALES'] / $exchRate),
                    "Breakeven" => $value['BREAKEVEN'],
                    "PL" => round($value['PL'] / $exchRate)                    
                );
            }
            
            if ($head_cd == 'NULL') {
                $head_cd = '';
            }
            return $this->render('yield/transport.html.twig', ['search' => $request->query->all(), 'Curr_Cd' => $currCd, 'transData' => $transData, "startDate" => $startDate, "endDate" => $endDate]);
        } else {
            return $this->render('yield/transport.html.twig', array('search' => $request->query->all()));
        }
    }

    /**
     * @Route("/yield/transport_excel", name="yield_transport_excel")
     */
    public function transportExcelAction(Request $request) {
        //Excel Download functionality

        if ($request->getMethod() == Request::METHOD_POST) {
            $Day = $request->request->get('Day');
            $Date = $request->request->get('Date');
            $Sales_sts = $request->request->get('SALE_STS');
            $Route = $request->request->get('Route');
            $Dep = $request->request->get('Dep');
            $Arr = $request->request->get('arr');
            $Time = $request->request->get('Time');
            $Flight = $request->request->get('flight');
            $Seats = $request->request->get('MAX_QTY');
            $Alt = $request->request->get('QTY');
            $Opt = $request->request->get('OPT');
            $Bkd = $request->request->get('BKD');
            $Manseats = $request->request->get('MAN_SEATS');
            $Rem = $request->request->get('REM');
            $Total = $request->request->get('total');
            $Addsales = $request->request->get('Addsales');
            $SalesTotal = $request->request->get('add_total_sales');
            $CostPrc = $request->request->get('cost_prc');
            $FlighCost = $request->request->get('flighCost');
            $PerClient = $request->request->get('per_client');
            $Sprice = $request->request->get('sPrice');
            $Sales = $request->request->get('Sales');
            $BreakEven = $request->request->get('breakEven');
            $ProfitLoss = $request->request->get('profitLoss');

            $GTotal = $request->request->get('GTotal');
            $grandTotal = $request->request->get('grandTotal');



            $startDate = $request->request->get('startDate');
            $endDate = $request->request->get('endDate');

            $TitleInfo = array("Day", "Date", "Ext/Int", "Route", "Dep", "Arr", "Time", "Flight", "Max Qty",
                "Qty", "Opt", "Bkd", "Man", "Rem", "Total", "Add Sales", "Total Sales", "Cost Per Seat",
                "Flight Cost", "GC Per Client", "Sales Price", "Sales", "Breakeven", "Profit/Loss");

            $phpExcelObject = $this->get('phpexcel')->createPHPExcelObject();
            $key_title = "Profit & Loss";
            $phpExcelObject->createSheet(0);
            $phpExcelObject->setActiveSheetIndex(0);

            $xls_col = 'A';
            $xls_row = 1;

            $l = 0;
            foreach ($TitleInfo as $kl => $vl) {
                $phpExcelObject->getActiveSheet()->getStyle($xls_col . $xls_row)->getFont()->setBold(true);
                $phpExcelObject->getActiveSheet()->setCellValue($xls_col++ . $xls_row, $vl);
                $l++;
            }

            $xls_col = 'A';
            $xls_col_B = 'B';
            $xls_col_C = 'C';
            $xls_col_D = 'D';
            $xls_col_E = 'E';
            $xls_col_F = 'F';
            $xls_col_G = 'G';
            $xls_col_H = 'H';
            $xls_col_I = 'I';
            $xls_col_J = 'J';
            $xls_col_K = 'K';
            $xls_col_L = 'L';
            $xls_col_M = 'M';
            $xls_col_N = 'N';
            $xls_col_O = 'O';
            $xls_col_P = 'P';
            $xls_col_Q = 'Q';
            $xls_col_R = 'R';
            $xls_col_S = 'S';
            $xls_col_T = 'T';
            $xls_col_U = 'U';
            $xls_col_V = 'V';
            $xls_col_W = 'W';
            $xls_col_X = 'X';

            $xls_row = "2";
            $xls_row1 = "2";
            $xls_row2 = "2";
            $xls_row3 = "2";
            $xls_row4 = "2";
            $xls_row5 = "2";
            $xls_row6 = "2";
            $xls_row7 = "2";
            $xls_row8 = "2";
            $xls_row9 = "2";
            $xls_row10 = "2";
            $xls_row11 = "2";
            $xls_row12 = "2";
            $xls_row13 = "2";
            $xls_row14 = "2";
            $xls_row15 = "2";
            $xls_row16 = "2";
            $xls_row17 = "2";
            $xls_row18 = "2";
            $xls_row19 = "2";
            $xls_row20 = "2";
            $xls_row21 = "2";
            $xls_row22 = "2";
            $xls_row23 = "2";
            $cnt = 0;
            foreach ($Day as $key1 => $value) {
                if (isset($Date[$cnt])) {
                    $phpExcelObject->getActiveSheet()->setCellValue($xls_col . $xls_row++, $value);
                    $phpExcelObject->getActiveSheet()->setCellValue($xls_col_B . $xls_row1++, $Date[$cnt]);
                    $phpExcelObject->getActiveSheet()->setCellValue($xls_col_C . $xls_row2++, $Sales_sts[$cnt]);
                    $phpExcelObject->getActiveSheet()->setCellValue($xls_col_D . $xls_row3++, $Route[$cnt]);
                    $phpExcelObject->getActiveSheet()->setCellValue($xls_col_E . $xls_row4++, $Dep[$cnt]);
                    $phpExcelObject->getActiveSheet()->setCellValue($xls_col_F . $xls_row5++, $Arr[$cnt]);
                    $phpExcelObject->getActiveSheet()->setCellValue($xls_col_G . $xls_row6++, $Time[$cnt]);
                    $phpExcelObject->getActiveSheet()->setCellValue($xls_col_H . $xls_row7++, $Flight[$cnt]);
                    $phpExcelObject->getActiveSheet()->setCellValue($xls_col_J . $xls_row8++, $Seats[$cnt]);
                    $phpExcelObject->getActiveSheet()->setCellValue($xls_col_I . $xls_row9++, $Alt[$cnt]);
                    $phpExcelObject->getActiveSheet()->setCellValue($xls_col_K . $xls_row10++, $Opt[$cnt]);
                    $phpExcelObject->getActiveSheet()->setCellValue($xls_col_L . $xls_row11++, $Bkd[$cnt]);
                    $phpExcelObject->getActiveSheet()->setCellValue($xls_col_M . $xls_row12++, $Manseats[$cnt]);
                    $phpExcelObject->getActiveSheet()->setCellValue($xls_col_N . $xls_row13++, $Rem[$cnt]);
                    $phpExcelObject->getActiveSheet()->setCellValue($xls_col_O . $xls_row14++, $Total[$cnt]);
                    $phpExcelObject->getActiveSheet()->setCellValue($xls_col_P . $xls_row15++, $Addsales[$cnt]);
                    $phpExcelObject->getActiveSheet()->setCellValue($xls_col_Q . $xls_row16++, $SalesTotal[$cnt]);
                    $phpExcelObject->getActiveSheet()->setCellValue($xls_col_R . $xls_row17++, $CostPrc[$cnt]);
                    $phpExcelObject->getActiveSheet()->setCellValue($xls_col_S . $xls_row18++, $FlighCost[$cnt]);
                    $phpExcelObject->getActiveSheet()->setCellValue($xls_col_T . $xls_row19++, $PerClient[$cnt]);
                    $phpExcelObject->getActiveSheet()->setCellValue($xls_col_U . $xls_row20++, $Sprice[$cnt]);
                    $phpExcelObject->getActiveSheet()->setCellValue($xls_col_V . $xls_row21++, $Sales[$cnt]);
                    $phpExcelObject->getActiveSheet()->setCellValue($xls_col_W . $xls_row22++, $BreakEven[$cnt]);
                    $phpExcelObject->getActiveSheet()->setCellValue($xls_col_X . $xls_row23++, $ProfitLoss[$cnt]);
                    $cnt++;
                }
            }
            $phpExcelObject->getActiveSheet()->setCellValue($xls_col_W . $xls_row23, 'Total');

            $phpExcelObject->getActiveSheet()->setCellValue($xls_col_X . $xls_row23, $grandTotal);
            $phpExcelObject->getActiveSheet()->getStyle($xls_col_W . $xls_row23)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle($xls_col_X . $xls_row23)->getFont()->setBold(true);


            $phpExcelObject->getActiveSheet()->setTitle($key_title);

            // Set the 2. sheet
            $phpExcelObject = $this->createExcelContentSheet($phpExcelObject, $request);
            $k = 0;
            // Set active sheet index to the first sheet, so Excel opens this as the first sheet
            $phpExcelObject->setActiveSheetIndex($k);
            $k++;

            // create the writer
            $writer = $this->get('phpexcel')->createWriter($phpExcelObject, 'Excel2007');

            // create the response
            $response = $this->get('phpexcel')->createStreamedResponse($writer);

            //TravelcoNordic-Profit & Loss
            $filename = sprintf('Transport_Profit_Loss-%s.xlsx', $startDate . "-" . $endDate);
            $dispositionHeader = $response->headers->makeDisposition(
                    ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);


            $phpExcelObject->setActiveSheetIndex(0);

            $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
            $response->headers->set('Content-Disposition', $dispositionHeader);
            return $response;
        }
    }

    private function createExcelContentSheet($phpExcelObject, $request) {
        return $phpExcelObject;
    }

}
