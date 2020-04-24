<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class StatisticsController extends Controller
{
    /**
     * @Route("/statistics", name="statistics")
     */
    public function statisticsIndexAction(Request $request)
    {
        $promCd = strtoupper($request->query->get('prom_cd', null));

        $geographies = explode(',', $request->query->get('geography', null));
        $excludeCnx = $request->query->get('exclude_cnx', null);
        $excludeQte = $request->query->get('exclude_qte', null);

        // Set created date filters
        $stDt = $endDt = null;
        if ($request->query->has('end_dt') && !empty($request->query->get('end_dt'))) {
            $endDt = date('Y-m-d', strtotime($request->query->get('end_dt')));
        }

        if ($request->query->has('st_dt') && !empty($request->query->get('st_dt'))) {
            $stDt = date('Y-m-d', strtotime($request->query->get('st_dt')));
        }

        // Set start and end dates filters
        $firstStartDt = $lastEndDt = null;
        if ($request->query->has('first_st_dt') && !empty($request->query->get('first_st_dt'))) {
            $firstStartDt = date('Y-m-d', strtotime($request->query->get('first_st_dt')));
        }

        if ($request->query->has('last_end_dt') && !empty($request->query->get('last_end_dt'))) {
            $lastEndDt = date('Y-m-d', strtotime($request->query->get('last_end_dt')));
        }

        // If start and end date are empty and a created date range is empty
        if (is_null($firstStartDt) && is_null($lastEndDt) && (is_null($stDt) || is_null($endDt))) {

            if (is_null($stDt) && $endDt) {
                $stDt = new \Datetime($request->query->get('end_dt', ''));
            }
            else {
                $stDt = new \Datetime($request->query->get('st_dt', ''));
            }

            $endDt = new \Datetime($request->query->get('end_dt', ''));

            $stDt = $stDt->format('Y-m-d');
            $endDt = $endDt->format('Y-m-d');
        }



        // Set filter for booking status
        $bkgStatusFilters = [];
        if ($excludeCnx) {
            $bkgStatusFilters[] = 'CNX';
        }

        if($excludeQte) {
            $bkgStatusFilters[] = 'QTE';
        }

        $placeholders1 = $placeholders2 = $placeholders3 = [];
        $binds1 = $binds2 = $binds3 = [];
        $index1 = $index2 = $index3 = 0;

        foreach ($geographies as $geography) {
            $geography = strtoupper(trim($geography));

            if (strlen($geography) == 2) {
                $placeholders1[] = ':cty1_pt_cd' . $index1;
                $binds1['cty1_pt_cd' . $index1++] = $geography;
            }
            elseif (strlen($geography) == 3) {
                $placeholders2[] = ':cty2_pt_cd' . $index2;
                $binds2['cty2_pt_cd' . $index2++] = $geography;
            }
            elseif (strlen($geography) == 5) {
                $placeholders3[] = ':cty3_pt_cd' . $index3;
                $binds3['cty3_pt_cd' . $index3++] = $geography;
            }
        }

        $limit = 10000;

        $conn = $this->get('doctrine.dbal.atcore_connection');

        $table = $this->container->getParameter('exch_table_ids');
        $currencies = $this->container->getParameter('currency_ids');

        $exchanges = [];
        $sql = sprintf("SELECT
                            ert.exch_table_id, ert.cur_id, ert.exch_rt
                        FROM
                            atcomres.ar_exchangeratetable ert
                                LEFT OUTER JOIN atcomres.ar_exchangeratetable ert2
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
                            ert.exch_table_id IN (%s)",
            implode($currencies, ','),
            implode(array_keys($table), ',')
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
                            DISTINCT res.res_id, mkt.cd MKT_CD, mkt.name MKT_NAME,
                            res.origin_dt,
                            res.bkg_tp, res.bkg_sts,
                            res.first_st_dt, res.last_end_dt,
                            res.n_pax, res.sell_prc, res.prof_ex_vat,
                            res.bal, cur.cd CUR_CD,
                            LISTAGG( stc.cd,',' )WITHIN GROUP(ORDER BY stc.name)OVER(PARTITION BY res.res_id)AS accom_cd,
                            LISTAGG( stc.name,', ' )WITHIN GROUP(ORDER BY stc.name)OVER(PARTITION BY res.res_id) AS accom,
                            arr_pt.pt_cd ARR_CD, dep_pt.pt_cd DEP_CD,
                            prom.cd PROM_CD, prom.name PROM_NAME
                        FROM
                            atcomres.ar_reservation res
                                INNER JOIN atcomres.ar_promotion prom ON prom.prom_id = res.prom_id
                                INNER JOIN atcomres.ar_currency cur ON cur.cur_id = res.sell_cur_id
                                INNER JOIN atcomres.ar_market mkt ON mkt.mkt_id = res.mkt_id
                                LEFT JOIN atcomres.ar_resservice ser_acc ON ser_acc.res_id = res.res_id
                                    AND ser_acc.ser_tp = 'ACC'
                                    AND
                                        CASE WHEN res.bkg_sts = 'CNX' THEN 'CNX'
                                             ELSE 'CON'
                                        END = ser_acc.ser_sts
                                LEFT JOIN atcomres.ar_sellstatic sell ON sell.sell_stc_id = ser_acc.ser_id
                                LEFT JOIN atcomres.ar_staticstock stc ON stc.stc_stk_id = NVL(sell.stc_stk_id,ser_acc.ser_id)                                
                                LEFT JOIN atcomres.ar_point acc_pt1 ON acc_pt1.pt_id = stc.cty1_pt_id
                                LEFT JOIN atcomres.ar_point acc_pt2 ON acc_pt2.pt_id = stc.cty2_pt_id
                                LEFT JOIN atcomres.ar_point acc_pt3 ON acc_pt3.pt_id = stc.cty3_pt_id
                                LEFT JOIN atcomres.ar_resservice ser_trs ON ser_trs.res_id = res.res_id 
                                    AND ser_trs.ser_tp = 'TRS' AND ser_trs.dir = 'OUT' AND
                                        CASE WHEN res.bkg_sts = 'CNX' THEN 'CNX'
                                             ELSE 'CON'
                                        END = ser_trs.ser_sts
                                LEFT JOIN atcomres.ar_point arr_pt ON arr_pt.pt_id = ser_trs.arr_pt_id
                                LEFT JOIN atcomres.ar_point dep_pt ON dep_pt.pt_id = ser_trs.dep_pt_id
                        WHERE
                            res.res_sts = 'CON'
                                AND
                            res.bkg_tp NOT IN ('ITM')
                                
                                AND
                            (:prom_cd is null OR SUBSTR(prom.cd, 0, 2) = :prom_cd)
                            %s
                            %s
                            %s
                            %s
                            %s
                            %s
                            %s
                            %s
                        ORDER BY
                            res.origin_dt DESC
                        FETCH FIRST %d ROWS ONLY",
            !empty($placeholders1) ? 'AND acc_pt1.pt_cd in (' . implode(',', $placeholders1) . ')' : '',
            !empty($placeholders2) ? 'AND acc_pt2.pt_cd in (' . implode(',', $placeholders2) . ')' : '',
            !empty($placeholders3) ? 'AND acc_pt3.pt_cd in (' . implode(',', $placeholders3) . ')' : '',
            !empty($bkgStatusFilters) ? "AND res.bkg_sts NOT IN ('" . implode("','", $bkgStatusFilters) . "')" : '',
            $firstStartDt ? " AND res.first_st_dt >= TO_DATE('" . $firstStartDt . "')" : "",
            $lastEndDt ? " AND res.last_end_dt <= TO_DATE('" . $lastEndDt ."')" : "",
            $stDt ? " AND TRUNC(res.origin_dt) >= TO_DATE('" . $stDt . "') ": "",
            $endDt ? " AND TRUNC(res.origin_dt) <= TO_DATE('" . $endDt . "') ": "",
            $limit
        );

        $stmt = $conn->prepare($sql);
        $stmt->bindValue('prom_cd', $promCd);
        foreach ($binds1 as $placeholder => $value) {
            $stmt->bindValue($placeholder, $value);
        }
        foreach ($binds2 as $placeholder => $value) {
            $stmt->bindValue($placeholder, $value);
        }
        foreach ($binds3 as $placeholder => $value) {
            $stmt->bindValue($placeholder, $value);
        }
        $stmt->execute();
        $results = $stmt->fetchAll();

        $limitReached = (count($results) == $limit) ? $limit : false;

        return $this->render('statistics/statistics.html.twig', [
            'exchanges' => $exchanges,
            'reservations' => $results,
            'search' => $request->query->all(),
            'limit_reached' => $limitReached,
        ]);

    }

    function quote($str) {
        return sprintf("'%s'", $str);
    }


    /**
     * @Route("/statistics/historic_sales", name="statistics_historic_sales")
     */
    public function statisticsHistoricSalesAction(Request $request)
    {
        $promCd = strtoupper($request->query->get('prom_cd', null));
        $stDt = new \Datetime($request->query->get('st_dt', ''));
        $endDt = new \Datetime($request->query->get('end_dt', ''));
        $geography = strtoupper($request->query->get('geography', null));
        $accomCd = strtoupper($request->query->get('accom_cd', null));
        $rmCd = strtoupper($request->query->get('rm_cd', null));
        $stay = $request->query->get('stay', null);
        $granularity = $request->query->get('granularity', 'MM');

        $reservations = [];
        $bookingDates = [];

        if ($promCd && ($geography || $accomCd)) {

            $countryCd = $locationCd = $resortCd = null;
            if (strlen($geography) == 2) {
                $countryCd = $geography;
            } elseif (strlen($geography) == 3) {
                $locationCd = $geography;
            } elseif (strlen($geography) == 5) {
                $resortCd = $geography;
            }

            $conn = $this->get('doctrine.dbal.atcore_connection');

            $sql = sprintf("SELECT
                              trunc(ser.st_dt, '%1\$s') as arrive,
                              trunc(res.origin_dt, 'MM') as booked,
                              ser.ser_len,
                              round(avg(coalesce(prc_stk.prc, 0))) as prc_stk_avg,
                              count(prc_stk.prc) as prc_stk_count,
                              round(avg(coalesce(prc_ysup.prc, 0))) as prc_ysup_avg,
                              round(coalesce(avg(prc_ysup.prc), 0)) as prc_ysup_avg2,
                              round(avg(coalesce(prc_tdis.prc, 0))) as prc_tdis_avg,
                              round(coalesce(avg(prc_tdis.prc), 0)) as prc_tdis_avg2
                            FROM
                              atcomres.ar_reservation res
                                inner join atcomres.ar_promotion prom
                                  on prom.prom_id = res.prom_id
                                inner join atcomres.ar_resservice ser
                                  on ser.res_id = res.res_id
                                  and ser.ser_tp = 'ACC'
                                  and ser.ser_sts = 'CON'
                                  inner join atcomres.ar_ressubservice subser
                                    on subser.res_ser_id = ser.res_ser_id
                                    inner join atcomres.ar_ressubservicepax subpax
                                      on subpax.res_sub_ser_id = subser.res_sub_ser_id
                                      inner join atcomres.ar_passenger pax
                                        on pax.pax_id = subpax.pax_id
                                        and pax.pax_tp = 'ADU'

                                        inner join
                                          (
                                            select
                                              prcpax.pax_id, prcpax.prc, prc.res_sub_ser_id
                                            from
                                              atcomres.ar_pricepax prcpax
                                              inner join atcomres.ar_price prc
                                                on prc.prc_id = prcpax.prc_id
                                                and prc.prc_sts = 'STK'
                                                inner join atcomres.ar_usercodes prccd
                                                  on prccd.user_cd_id = prc.prc_cd_id
                                                  and prccd.cd = 'AA'
                                            where prcpax.prc != 0
                                          ) prc_stk
                                          on prc_stk.pax_id = pax.pax_id
                                          and prc_stk.res_sub_ser_id = subser.res_sub_ser_id


                                        left join
                                          (
                                            select
                                              prcpax.pax_id, prcpax.prc, prc.res_sub_ser_id
                                            from
                                              atcomres.ar_pricepax prcpax
                                              inner join atcomres.ar_price prc
                                                on prc.prc_id = prcpax.prc_id
                                                and prc.prc_sts = 'YSUP'
                                          ) prc_ysup
                                          on prc_ysup.pax_id = pax.pax_id
                                          and prc_ysup.res_sub_ser_id = subser.res_sub_ser_id


                                        left join
                                          (
                                            select
                                              prcpax.pax_id, prcpax.prc, prc.res_sub_ser_id
                                            from
                                              atcomres.ar_pricepax prcpax
                                              inner join atcomres.ar_price prc
                                                on prc.prc_id = prcpax.prc_id
                                                and prc.prc_sts = 'TDIS'
                                          ) prc_tdis
                                          on prc_tdis.pax_id = pax.pax_id
                                          and prc_tdis.res_sub_ser_id = subser.res_sub_ser_id


                                    inner join atcomres.ar_sellunit sellu
                                      on sellu.sell_unit_id = subser.inv_id
                                      inner join atcomres.ar_room rm
                                        on rm.rm_id = sellu.rm_id
                                  inner join atcomres.ar_sellstatic sell
                                    on sell.sell_stc_id = ser.ser_id
                                    inner join atcomres.ar_staticstock stc
                                      on stc.stc_stk_id = sell.stc_stk_id
                                      inner join atcomres.ar_point pt1
                                        on pt1.pt_id = stc.cty1_pt_id
                                      inner join atcomres.ar_point pt2
                                        on pt2.pt_id = stc.cty2_pt_id
                                      inner join atcomres.ar_point pt3
                                        on pt3.pt_id = stc.cty3_pt_id
                            WHERE
                              ser.st_dt between :st_dt and :end_dt
                                and
                              prom.cd = :prom_cd
                                and
                              (:accom_cd is null or stc.cd = :accom_cd)
                                and
                              (:rm_cd is null or rm.cd = :rm_cd)
                                and
                              (:country_cd is null or pt1.pt_cd = :country_cd)
                                and
                              (:location_cd is null or pt2.pt_cd = :location_cd)
                                and
                              (:resort_cd is null or pt3.pt_cd = :resort_cd)
                                and
                              (:stay is null or ser.ser_len = :stay)
                            GROUP BY
                              trunc(ser.st_dt, '%1\$s'),
                              trunc(res.origin_dt, 'MM'),
                              ser.ser_len
                            ORDER BY
                              trunc(ser.st_dt, '%1\$s'),
                              ser.ser_len,
                              trunc(res.origin_dt, 'MM')",
                $granularity
            );

            $stmt = $conn->prepare($sql);
            $stmt->bindValue('st_dt', $stDt, 'date');
            $stmt->bindValue('end_dt', $endDt, 'date');
            $stmt->bindValue('prom_cd', $promCd);
            $stmt->bindValue('country_cd', $countryCd);
            $stmt->bindValue('location_cd', $locationCd);
            $stmt->bindValue('resort_cd', $resortCd);
            $stmt->bindValue('accom_cd', $accomCd ? $accomCd : null);
            $stmt->bindValue('rm_cd', $rmCd ? $rmCd : null);
            $stmt->bindValue('stay', $stay ? $stay : null);
            $stmt->execute();
            $results = $stmt->fetchAll();

            foreach ($results as $result) {
                $booked = new \Datetime($result['BOOKED']);
                $bkgDt = $booked->format('Y-m-d');
                if (!in_array($bkgDt, $bookingDates)) {
                    $bookingDates[] = $bkgDt;
                }

                $arrive = new \Datetime($result['ARRIVE']);
                $stDt = $arrive->format('Y-m-d');
                $stay = $result['SER_LEN'];

                if (!isset($reservations[$stDt])) {
                    $reservations[$stDt] = [];
                }

                if (!isset($reservations[$stDt][$stay])) {
                    $reservations[$stDt][$stay] = [];
                }

                $reservations[$stDt][$stay][$bkgDt] = [
                    'stk_avg' => $result['PRC_STK_AVG'],
                    'stk_count' => $result['PRC_STK_COUNT'],
                    'ysup_avg' => $result['PRC_YSUP_AVG'],
                    'ysup_real_avg' => $result['PRC_YSUP_AVG2'],
                    'tdis_avg' => $result['PRC_TDIS_AVG'],
                    'tdis_real_avg' => $result['PRC_TDIS_AVG2'],
                ];

            }

            rsort($bookingDates);
        }

        return $this->render('statistics/historic_sales.html.twig', [
            'reservations' => $reservations,
            'booking_dates' => $bookingDates,
            'search' => $request->query->all(),
        ]);

    }
}