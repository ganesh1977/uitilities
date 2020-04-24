<?php
namespace AppBundle\Service;

use Datetime;
use Doctrine\DBAL\Connection;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

class Atcore
{
    protected $connection;

    public $stDt;
    public $endDt;
    public $promotionCd;
    public $promCd;
    public $accomCd;
    public $rmCd;
    public $depCd;
    public $arrCd;
    public $dir;
    public $nAdu;
    public $nChd;
    public $totalVsSellRules;
    public $stay;
    public $sstay;
    public $estay;
    public $cty1;
    public $cty2;
    public $cty3;
    public $board;
    public $rating;

    public $environment = 'prod';

    const DATE_FORMAT = 'd-M-Y';
    
    public function __construct(Connection $dbalConnection) {
        
        $this->connection = $dbalConnection;
        
		$this->client = new Client([
			// Base URI is used with relative requests
			'base_uri' => 'http://192.168.218.30/fcgi-bin/prod/atcache_g' . $this->environment . '/',
			// You can set any number of default request options.
			'timeout'  => 3.0,
//			'debug' => true
		]);
        
    }
    
    public function reset() {
        $this->stDt = null;
        $this->endDt = null;
        $this->promCd = null;
        $this->accomCd = null;
        $this->rmCd = null;
        $this->depCd = null;
        $this->arrCd = null;
        $this->dir = null;
        $this->nAdu = null;
        $this->nChd = null;
        $this->totalVsSellRules = null;
        $this->stay = null;
        $this->sstay = null;
        $this->estay = null;
        $this->cty1 = null;
        $this->cty2 = null;
        $this->cty3 = null;
        $this->board = null;
        $this->rating = null;
    }
    
    public function getDynamicPackagingPromotionIds() {
        $sql = "SELECT
                    prom_id
                FROM
                    ATCOMRES.AR_PROMOTION
                WHERE
                    cd LIKE '%DP'";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll();

        $promotionIds = [];
        foreach ($results as $result) {
            $promotionIds[] = $result['PROM_ID'];
        }

        return $promotionIds;
    }

    public function getUKPromotionIds() {
        $sql = "SELECT
                    prom_id
                FROM
                    ATCOMRES.AR_PROMOTION
                WHERE
                    cd LIKE 'UK%'";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll();

        $promotionIds = [];
        foreach ($results as $result) {
            $promotionIds[] = $result['PROM_ID'];
        }

        return $promotionIds;
    }
    
    public function getDynamicPackagingReservations($minutes = 30) {
        $promotionIds = $this->getDynamicPackagingPromotionIds();
        return $this->getReservations($promotionIds, $minutes);
    }

    public function getUKReservations($minutes = 30) {
        $promotionIds = $this->getUKPromotionIds();
        return $this->getReservations($promotionIds, $minutes);
    }

    protected function getReservations($promotionIds, $minutes)
    {
        $originDt = new \Datetime(date('c', time()-60*$minutes));

        $placeholders = $binds = [];
        $index = 0;
        foreach ($promotionIds as $promotionId) {
            $placeholders[] = ':prom' . $index;
            $binds['prom' . $index++] = $promotionId;
        }

        $sql = sprintf("SELECT
                            res.res_id, res.origin_dt, res.bkg_sts,
                            res.n_pax, pax.forename, pax.surname
                        FROM
                            ATCOMRES.AR_RESERVATION res
                                INNER JOIN ATCOMRES.AR_PASSENGER pax
                                  ON pax.res_id = res.res_id
                                  AND pax.lead_pax_fg = 'Y'
                        WHERE
                            prom_id IN (%s)
                                AND
                            origin_dt > :origin_dt",
            implode(',', $placeholders)
        );

        $stmt = $this->connection->prepare($sql);
        foreach ($binds as $placeholder => $value) {
            $stmt->bindValue($placeholder, $value);
        }
        $stmt->bindValue("origin_dt", $originDt, 'datetime');
        $stmt->execute();
        $results = $stmt->fetchAll();

        return $results;
    }
    
    public function getAccommodationInventoryResults() {
        $sql = "SELECT
                    stc.stc_stk_id ACCOM_ID, stc.cd ACCOM_CD, stc.name ACCOM,
                    sellu.rm_id SELL_RM_ID, sellu_spp.sell_unit_id, sellu_spp.sell_stc_id,
                    rm.cd RM_CD, rm.name RM,
                    inv.inv_dt, inv.alt_rel_dt,
                    inv.min_stay, inv.max_stay,
                    inv.sub_alloc_fg,
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
                    NVL(invsub.alt_exc_sd_ob, NVL(inv.alt_exc_sd_ob, 0)) AS ALT_EXC_SD_OB,
                    (inv.alt_alloc - inv.alt_opt - inv.alt_bkd) AS ALT_MAIN_REMAIN,
                    stc_master.cd MASTER_ACCOM_CD, invsub.stk_sub_id,
                    rm_grp.cd RM_GP_CD
                FROM
                    ATCOMRES.AR_STATICSTOCK stc                        
                        INNER JOIN ATCOMRES.AR_SELLSTATIC sell_cci
                            ON sell_cci.stc_stk_id = stc.stc_stk_id
                            INNER JOIN ATCOMRES.AR_PROMOTION prom_cci
                                ON prom_cci.prom_id = sell_cci.prom_id
                            INNER JOIN ATCOMRES.AR_SELLUNIT sellu
                                ON sellu.sell_stc_id = sell_cci.sell_stc_id
                                INNER JOIN ATCOMRES.AR_ROOM rm
                                    ON rm.rm_id = sellu.rm_id
                                    INNER JOIN ATCOMRES.AR_STATICROOM srm
                                        ON srm.rm_id = rm.rm_id
                                        AND srm.stc_stk_id = stc.stc_stk_id
                                        INNER JOIN ATCOMRES.AR_USERCODES rm_grp
                                            ON rm_grp.user_cd_id = srm.rm_gp_id
                                INNER JOIN ATCOMRES.AR_INVENTORY inv
                                    ON inv.stk_sub_id = sellu.inv_unit_id
                                    LEFT JOIN ATCOMRES.AR_INVENTORYSUB invsub
                                        ON invsub.stk_sub_id = inv.stk_sub_id
                                        AND invsub.inv_dt = inv.inv_dt
                                        LEFT JOIN ATCOMRES.AR_USERCODES usr
                                            ON usr.user_cd_id = invsub.shr_id

                        INNER JOIN ATCOMRES.AR_SELLSTATIC sell_spp
                            ON sell_spp.stc_stk_id = stc.stc_stk_id
                            INNER JOIN ATCOMRES.AR_SELLUNIT sellu_spp
                                ON sellu_spp.sell_stc_id = sell_spp.sell_stc_id
                            INNER JOIN ATCOMRES.AR_PROMOTION prom_spp
                                ON prom_spp.prom_id = sell_spp.prom_id
                            LEFT JOIN ATCOMRES.AR_STATICSTOCK stc_master
                                ON stc_master.stc_stk_id = sell_spp.shr_inv_stc_stk_id
                        
                        INNER JOIN ATCOMRES.AR_STATICTRANSPORT stt
                          ON stt.stc_stk_id = stc.stc_stk_id
                            INNER JOIN ATCOMRES.AR_POINT pt
                              ON pt.pt_id = stt.pt_id
                WHERE
                    prom_cci.cd = 'CCI'
                        AND
                    prom_spp.cd = :prom_cd
                        AND
                    inv.inv_dt BETWEEN :st_dt AND :end_dt
                        AND
                    (:prom_cd is null OR usr.cd is null OR usr.cd = :prom_cd)
                        AND
                    (:accom_cd is null OR stc.cd = :accom_cd)
                        AND
                    (:rm_cd is null OR rm.cd = :rm_cd)
                        AND
                    (:arr_cd is null OR pt.pt_cd = :arr_cd)
                ORDER BY
                    inv.inv_dt";

        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('st_dt', $this->stDt, 'date');
        $stmt->bindValue('end_dt', $this->endDt, 'date');
        $stmt->bindValue('prom_cd', $this->promCd);
        $stmt->bindValue('accom_cd', $this->accomCd);
        $stmt->bindValue('rm_cd', $this->rmCd);
        $stmt->bindValue('arr_cd', $this->arrCd);
        $stmt->execute();
        $results = $stmt->fetchAll();
        
        return $results;
    }
    
    public function getFlightInventoryResults_info($promId,$day) {
        
        $d = explode(',',$promId);
        $prodid = "'".implode("','",$d)."'";
        
        $dayOfWeeks     =   '';
        $dayOfWeekd     =   '';
        
        if($day!='') {            
            $dayOfWeeks = "('".implode("','",$day)."')";
            $dayOfWeekd = "AND TO_CHAR(tis.cycle_dt, 'Dy') in $dayOfWeeks";
        } 
        
        $sql = "SELECT
                    tr.route_cd, tis.cycle_dt, tir.trans_route_id,
                    tis.alt SECTOR_ALT, tis.bkd SECTOR_BKD,
                    ti.alt, ti.bkd,
                    tsr.rule,
                    pt1.pt_cd DEP_CD,
                    pt2.pt_cd ARR_CD,
                    TO_CHAR(tis.cycle_dt, 'Dy') day
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
                    substr(tsr.rule, 0, 2) in (".$prodid.")
                        $dayOfWeekd
                     AND
                    tir.sale_sts = 'ON'
                        AND
                    tis.cycle_dt BETWEEN :st_dt AND :end_dt
                        AND
                    tr.dir_mth = :dir
                        AND
                    pt2.pt_cd = :arr_cd
                ORDER BY
                    tis.cycle_dt";

        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('st_dt', $this->stDt, 'date');
        $stmt->bindValue('end_dt', $this->endDt, 'date');
        $stmt->bindValue('dir', $this->dir);
        $stmt->bindValue('arr_cd', $this->arrCd);
        $stmt->execute();
        $results = $stmt->fetchAll();

        return $results;
    }
    
    public function getFlightInventoryResults($day) {        
        $dayOfWeeks     =   '';
        $dayOfWeekd     =   '';
        if($day!='') {
            
            $dayOfWeeks = "('".implode("','",$day)."')";
            $dayOfWeekd = "AND TO_CHAR(tis.cycle_dt, 'Dy') in $dayOfWeeks";
        } 
        
        $sql = "SELECT
                    tr.route_cd, tis.cycle_dt, tir.trans_route_id,
                    tis.alt SECTOR_ALT, tis.bkd SECTOR_BKD,
                    ti.alt, ti.bkd,
                    tsr.rule,
                    pt1.pt_cd DEP_CD,
                    pt2.pt_cd ARR_CD,
                    TO_CHAR(tis.cycle_dt, 'Dy') day
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
                    tir.sale_sts = 'ON'
                        $dayOfWeekd
                        AND
                    tis.cycle_dt BETWEEN :st_dt AND :end_dt
                        AND
                    tr.dir_mth = :dir
                        AND
                    pt2.pt_cd = :arr_cd
                ORDER BY
                    tis.cycle_dt";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('st_dt', $this->stDt, 'date');
        $stmt->bindValue('end_dt', $this->endDt, 'date');
        $stmt->bindValue('dir', $this->dir);
        $stmt->bindValue('arr_cd', $this->arrCd);
        $stmt->execute();
        $results = $stmt->fetchAll();        
        return $results;
    }
    


    public function getFlightsAndSellRulesBySharer($promCd = null,$day = null) {   
        
        $results = $this->getFlightInventoryResults_info($promCd,$day);  
        $sellRules = [];
        $this->totalVsSellRules = [];
        
        foreach ($results as $result) {
            $rule = $result['RULE'];
            $sharer = substr($rule, 0, 2);
            $cycleDt = date('d-M-Y', strtotime($result['CYCLE_DT']));
            $routeCd = $result['ROUTE_CD'];
            
            
            // Total vs sell rule to see if there are mistakes in sell rules.
            if (!isset($this->totalVsSellRules[$cycleDt . $routeCd])) {
                $this->totalVsSellRules[$cycleDt . $routeCd] = [                    
                    'total_alt' => $result['SECTOR_ALT'],
                    'total_bkd' => $result['SECTOR_BKD'],
                    'sell_alt' => 0,
                    'sell_bkd' => 0,
                ];
            }
            $this->totalVsSellRules[$cycleDt . $routeCd]['sell_alt'] += $result['ALT'];
            $this->totalVsSellRules[$cycleDt . $routeCd]['sell_bkd'] += $result['BKD'];          
            
            // Sorted by sell rule.
            if($promCd!='BT,SR,LM,SO,HF,ST,UK')
            {
                if (!is_null($promCd) && $promCd != $sharer) {
                    continue;
                }
            }
            
            if (!isset($sellRules[$sharer])) {
                $sellRules[$sharer] = [];
            }
            
            if (!isset($sellRules[$sharer][$cycleDt])) {
                $sellrules[$sharer][$cycleDt] = [];
            }
            
            if (!isset($sellRules[$sharer][$cycleDt][$routeCd])) {
                $sellRules[$sharer][$cycleDt][$routeCd] = [
                    'alt' => 0,
                    'bkd' => 0,
                    'dep_cd' => $result['DEP_CD'],
                    'arr_cd' => $result['ARR_CD'],
                    'rules' => [],
                    'mismatch' => false,
                    'mismatch_info' => [],
                ];
            }
            
            if (!isset($sellRules[$sharer][$cycleDt][$routeCd]['rules'][$rule])) {
                $sellRules[$sharer][$cycleDt][$routeCd]['rules'][$rule] = [
                    'alt' => 0,
                    'bkd' => 0,
                ];
            }
            
            $sellRules[$sharer][$cycleDt][$routeCd]['alt'] += $result['ALT'];
            $sellRules[$sharer][$cycleDt][$routeCd]['bkd'] += $result['BKD'];
    
            $sellRules[$sharer][$cycleDt][$routeCd]['rules'][$rule]['alt'] += $result['ALT'];
            $sellRules[$sharer][$cycleDt][$routeCd]['rules'][$rule]['bkd'] += $result['BKD'];
        }
 
        foreach ($sellRules as $sharer => $cycleDts) {
            foreach ($cycleDts as $cycleDt => $routeCds) {
                foreach ($routeCds as $routeCd => $route) {
                    if ($this->totalVsSellRules[$cycleDt . $routeCd]['total_alt'] != $this->totalVsSellRules[$cycleDt . $routeCd]['sell_alt']) {
                        $sellRules[$sharer][$cycleDt][$routeCd]['mismatch'] = true;
                        $sellRules[$sharer][$cycleDt][$routeCd]['mismatch_info'] = [
                            'total_alt' => $this->totalVsSellRules[$cycleDt . $routeCd]['total_alt'],
                            'sell_alt' => $this->totalVsSellRules[$cycleDt . $routeCd]['sell_alt'],
                        ];
                    }
                }
            }
        }
        return $sellRules;
    }

    public function getStopsalesByAccomResults() {
        $sql = "SELECT
                    ss.st_dt, ss.end_dt, ss.text,
                    rm.cd RM_CD, prom.cd PROM_CD
                FROM
                    ATCOMRES.AR_STATICSTOCKSTOPSALE ss
                        INNER JOIN ATCOMRES.AR_STATICSTOCK stc
                            ON stc.stc_stk_id = ss.tp_id
                        LEFT JOIN ATCOMRES.AR_STATICSTOCKSTOPSALEROOM ssr
                            ON ssr.stc_stk_stopsale_id = ss.stc_stk_stopsale_id
                                LEFT JOIN ATCOMRES.AR_ROOM rm
                                    ON rm.rm_id = ssr.rm_id
                        LEFT JOIN ATCOMRES.AR_PROMOTION prom
                            ON prom.prom_id = ss.prom_id
                WHERE
                    ss.tp = 'ACC'
                        AND
                    stc.cd = :accom_cd
                        AND
                    (rm.cd is null OR rm.cd = :rm_cd)
                        AND
                    (prom.cd is null OR prom.cd = :prom_cd)
                        AND
                    ss.end_dt >= :st_dt
                        AND
                    ss.st_dt <= :end_dt
                ORDER BY
                    ss.st_dt, ss.end_dt, prom.cd, rm.cd";

        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('accom_cd', $this->accomCd);
        $stmt->bindValue('rm_cd', $this->rmCd);
        $stmt->bindValue('prom_cd', $this->promCd);
        $stmt->bindValue('st_dt', $this->stDt, 'date');
        $stmt->bindValue('end_dt', $this->endDt, 'date');
        $stmt->execute();
        $results = $stmt->fetchAll();
        
        return $results;
    }

    public function getStopsalesByAirportResults() {

        if (empty($this->arrCd) || empty($this->promCd) || empty($this->stDt) || empty($this->endDt)) {
            throw new \Exception('Parameters not set for stopsale to be calculated!');
        }

        $sql = "SELECT
                    ss.stc_stk_stopsale_id,
                    ss.st_dt, ss.end_dt, ss.text,
                    stc.cd ACCOM_CD, rm.cd RM_CD, prom.cd PROM_CD
                FROM
                    ATCOMRES.AR_STATICSTOCKSTOPSALE ss
                        INNER JOIN ATCOMRES.AR_STATICSTOCK stc
                            ON stc.stc_stk_id = ss.tp_id
                                INNER JOIN ATCOMRES.AR_STATICTRANSPORT stt
                                    ON stt.stc_stk_id = stc.stc_stk_id
                                        INNER JOIN ATCOMRES.AR_POINT pt
                                            ON pt.pt_id = stt.pt_id
                        LEFT JOIN ATCOMRES.AR_STATICSTOCKSTOPSALEROOM ssr
                            ON ssr.stc_stk_stopsale_id = ss.stc_stk_stopsale_id
                                LEFT JOIN ATCOMRES.AR_ROOM rm
                                    ON rm.rm_id = ssr.rm_id
                        LEFT JOIN ATCOMRES.AR_PROMOTION prom
                            ON prom.prom_id = ss.prom_id
                WHERE
                    pt.pt_cd = :arr_cd
                        AND
                    ss.tp = 'ACC'
                        AND
                    (:prom_cd is null OR prom.cd is null OR prom.cd = :prom_cd)
                        AND
                    ss.end_dt >= :st_dt
                        AND
                    ss.st_dt <= :end_dt
                ORDER BY
                    ss.st_dt, ss.end_dt, prom.cd, rm.cd";

        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('arr_cd', $this->arrCd);
        $stmt->bindValue('prom_cd', $this->promCd);
        $stmt->bindValue('st_dt', $this->stDt, 'date');
        $stmt->bindValue('end_dt', $this->endDt, 'date');
        $stmt->execute();
        $results = $stmt->fetchAll();
        
        return $results;
    }
    
    public function getMemoryCacheArguments($func = 801) {
        switch ($this->promCd) {
            case 'BT':
                $memCacheAgent = 'WEBAGT1';
                $market = 'DW1';
                break;
            case 'SR':
                $memCacheAgent = 'WEBAGT2';
                $market = 'SW2';
                break;
            case 'LM':
                $memCacheAgent = 'WEBAGT3';
                $market = 'FW3';
                break;
            case 'SO':
                $memCacheAgent = 'WEBAGT4';
                $market = 'NW4';
                break;
            case 'HF':
                $memCacheAgent = 'WEBAGT5';
                $market = 'IW5';
                break;
            case 'ST':
                $memCacheAgent = 'WEBAGT6';
                $market = 'STW6';
                break;
            default:
                $memCacheAgent = '';
                $market = '';
        }
    
        $urlArguments = [
            'func' => $func,
            'agent' => $memCacheAgent,
            'market' => $market,
            'sdate' => $this->stDt->format('Y-m-d'),
            'edate' => $this->endDt->format('Y-m-d'),
            'sstay' => $this->sstay ? $this->sstay : $this->stay,
            'estay' => $this->estay ? $this->estay : $this->stay,
            'pax_ad' => $this->nAdu,
            'pax_ch' => $this->nChd,
            'f_prom' => $this->promCd,
            'rooms' => 1,
            'r_1_no' => 1,
            'r_1_pax' => ($this->nAdu + $this->nChd),
        ];

        if ($this->accomCd) {
            $urlArguments['f_accom_cd'] = $this->accomCd;
            $urlArguments['f_accom_count'] = 1;
        }
        if ($this->depCd) {
            $urlArguments['f_dep'] = $this->depCd;
        }
        if ($this->arrCd) {
            $urlArguments['f_arr'] = $this->arrCd;
        }
        if ($this->board) {
            $urlArguments['f_board'] = $this->board;
        }
        if ($this->rating) {
            $urlArguments['f_rating'] = $this->rating;
        }
        if ($this->cty1) {
            $urlArguments['f_cty1'] = $this->cty1;
        }
        if ($this->cty2) {
            $urlArguments['f_cty2'] = $this->cty2;
        }
        if ($this->cty3) {
            $urlArguments['f_cty3'] = $this->cty3;
        }

    
		$j = 1;
		for ($i = 0; $i < $this->nAdu; $i++,$j++) {
            $urlArguments['r_1_p_' . $j . '_no'] = $j;
		}
		for ($i = 0;$i < $this->nChd; $i++,$j++) {
			$ch_age = 7;
            $urlArguments['r_1_p_' . $j . '_no'] = $j;
            $urlArguments['r_1_p_' . $j . '_age'] = $ch_age;
		}
        
        return $urlArguments;
    }
    
    public function getMemoryCacheArgumentsCampaign($func = 801) {
    
        $urlArguments = [
            'func' => $func,
            'f_prom' => $this->promCd . $this->promotionCd,
            'sdate' => $this->stDt->format('Y-m-d'),
            'edate' => $this->endDt->format('Y-m-d'),
            'sstay' => $this->stay,
            'estay' => $this->stay,
            //'f_dep' => $this->depCd,
            'f_arr' => $this->arrCd
        ];
        
        return $urlArguments;
    }
    
    public function getMemoryCacheLink($func = 801) {
        $memoryCacheLink = 'http://192.168.218.30/fcgi-bin/test/atcache_g?' . http_build_query($this->getMemoryCacheArguments($func));
        return $memoryCacheLink;
    }
    
    public function getMemoryCacheLinkCampaign($func = 801) {
        $memoryCacheLink = 'http://192.168.218.30/fcgi-bin/test/atcache_g?' . http_build_query($this->getMemoryCacheArgumentsCampaign($func));
        return $memoryCacheLink;
    }
    
    public function memoryCacheRequest($func = 801) {
        $query_params = $this->getMemoryCacheArguments($func);
        
		$response = $this->client->request('GET', 'search', [
			'query' => $query_params,
			'verify' => false
		]);
			
		return $response->getBody()->getContents(); // XML
    }
    
    
    public function getAirportIdsServicingAccom() {
        $sql = "SELECT
                    st.pt_id
                FROM
                    ATCOMRES.AR_STATICSTOCK stc
                        INNER JOIN ATCOMRES.AR_STATICTRANSPORT st
                            ON st.stc_stk_id = stc.stc_stk_id
                WHERE
                    stc.cd = :accom_cd";

        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('accom_cd', $this->accomCd);
        $stmt->execute();
        $results = $stmt->fetchAll();
        
        return $results;
    }
    
    
    function getCachedFlightsForAccom() {
        
        $results = $this->getAirportIdsServicingAccom();

        $placeholders = $binds = [];
        $index = 0;
        foreach ($results as $result) {
            $placeholders[] = ':arr_id' . $index;
            $binds['arr_id' . $index++] = $result['PT_ID'];
        }

        $sql = sprintf("SELECT
                            ctp.out_remain,
                            ctp.out_sts,
                            ctp.out_route_num,
                            ctp.out_trans_head_cd,
                            ctp.out_trans_sell_rule_id,
                            ctp.out_cur_srch_box_id,
                            ti_out.alt OUT_ALT, ti_out.opt+ti_out.bkd OUT_OB,
                            ctp.in_remain,
                            ctp.in_sts,
                            ctp.in_route_num,
                            ctp.in_trans_head_cd,
                            ctp.in_trans_sell_rule_id,
                            ctp.in_cur_srch_box_id,
                            ti_in.alt IN_ALT, ti_in.opt+ti_in.bkd IN_OB
                        FROM
                            ATCOMRES.AR_CACHETRANSPAIR ctp
                                INNER JOIN ATCOMRES.AR_POINT pt1
                                    ON pt1.pt_id = ctp.out_dep_pt_id
                                    AND pt1.pt_id = ctp.in_arr_pt_id
                                INNER JOIN ATCOMRES.AR_PROMOTION prom
                                    ON prom.prom_id = ctp.prom_id
                                INNER JOIN ATCOMRES.AR_TRANSINVENTORY ti_out
                                    ON ti_out.box_id = ctp.out_cur_srch_box_id
                                INNER JOIN ATCOMRES.AR_TRANSINVENTORY ti_in
                                    ON ti_in.box_id = ctp.in_cur_srch_box_id
                        WHERE
                            ctp.cycle_dt = :st_dt
                                AND
                            ctp.stay = :stay
                                AND
                            (:dep_cd is null OR pt1.pt_cd = :dep_cd)
                                AND
                            ctp.out_arr_pt_id IN (%s)
                                AND
                            prom.cd = :prom_cd
                                AND
                            ctp.prc_mth = 'INC'",
            implode(',', $placeholders)
        );

        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('st_dt', $this->stDt, 'date');
        $stmt->bindValue('prom_cd', $this->promCd . 'LP');
        $stmt->bindValue('stay', $this->stay);
        $stmt->bindValue('dep_cd', $this->depCd);
        foreach ($binds as $placeholder => $value) {
            $stmt->bindValue($placeholder, $value);
        }
        $stmt->execute();
        $results = $stmt->fetchAll();
        
        return $results;
    }
    
    
    
    public function getStaticAccommodation()
    {
        $sql = "SELECT
                    stc.*
                FROM
                    atcomres.ar_staticstock stc
                WHERE
                    stc.cd = :accom_cd";

        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('accom_cd', $this->accomCd);
        $stmt->execute();
        $results = $stmt->fetchAll();
        
        return $results;
    }

    public function getAccommodationPrice($rmGpId)
    {
        $accomPrice = [
            'found' => false,
            'mod_dt_tm' => null,
            'details' => [],
        ];
        $accomPrcDetIds = [];

        $sql = "SELECT
                            ap.sts, ap.bb_cd,
                            apd.accom_prc_det_id,
                            apd.st_dt, apd.end_dt, apd.mod_dt_tm,
                            apd.base_prc, apd.chd_age1, apd.chd_age2,
                            apd.chd1_age1_prc, apd.chd1_age2_prc,
                            apd.chd2_age1_prc, apd.chd2_age2_prc
                        FROM
                            atcomres.ar_accomprice ap
                                INNER JOIN atcomres.ar_accompricedetail apd
                                    ON apd.accom_prc_id = ap.accom_prc_id
                        WHERE
                            ap.bkg_tp = 'INC'
                                AND
                            ap.prom_cd = :prom_cd
                                AND
                            ap.stc_stk_cd = :accom_cd
                                AND
                            ap.unit_cd = :unit_cd
                                AND
                            ap.stay = :stay
                                AND
                            apd.st_dt >= :st_dt
                                AND
                            apd.end_dt <= :st_dt
                        ORDER BY
                            apd.mod_dt_tm DESC";

        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('prom_cd', $this->promCd . 'LP');
        $stmt->bindValue('accom_cd', $this->accomCd);
        $stmt->bindValue('stay', $this->stay);
        $stmt->bindValue('st_dt', $this->stDt, 'date');
        $stmt->bindValue('unit_cd', $rmGpId);
        $stmt->execute();
        $results = $stmt->fetchAll();

        foreach ($results as $result) {
            if (!$accomPrice['found'] && $result['STS'] = 'INS') {
                $accomPrice['found'] = true;
                $accomPrice['mod_dt_tm'] = $result['MOD_DT_TM'];
            }
            $accomPrcDetIds[] = $result['ACCOM_PRC_DET_ID'];
            $accomPrice['details'][] = $result;
        }

        return [
            $accomPrice,
            $accomPrcDetIds
        ];
    }

    public function getPriceDefinition($accomPrcDetIds)
    {
        $placeholders = $binds = [];
        $index = 0;
        foreach ($accomPrcDetIds as $accomPrcDetId) {
            $placeholders[] = ':prcDet' . $index;
            $binds['prcDet' . $index++] = $accomPrcDetId;
        }

        $sql = sprintf("
            SELECT
                pd.out_dep_pt, pd.mod_dt_tm,
                pd.acc_adu_prc, pd.del_fg
            FROM
                atcomres.ar_pricedefinition pd
            WHERE
                pd.accom_prc_det_id IN (%s)
            AND
                (:dep_cd is null or pd.out_dep_pt = :dep_cd)
        ",
            implode(',', $placeholders)
        );

        $stmt = $this->connection->prepare($sql);
        foreach ($binds as $placeholder => $value) {
            $stmt->bindValue($placeholder, $value);
        }
        $stmt->bindValue('dep_cd', $this->depCd);
        $stmt->execute();

        $results = $stmt->fetchAll();
        return $results;
    }

    public function getHotelsByAirport()
    {
        $sql = "
            SELECT DISTINCT
                stc.cd,
                stc.name
            FROM
                atcomres.ar_staticstock stc
                INNER JOIN atcomres.ar_statictransport stc_trps ON stc_trps.stc_stk_id = stc.stc_stk_id
                INNER JOIN atcomres.ar_sellstatic ss ON ss.stc_stk_id = stc.stc_stk_id
                INNER JOIN atcomres.ar_point pt ON pt.pt_id = stc_trps.pt_id AND pt.pt_cd = :arr_cd
            ORDER BY
                stc.cd ASC
        ";

        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('arr_cd', $this->arrCd);
        $stmt->execute();

        $results = $stmt->fetchAll();
        $hotels = [];

        foreach ($results as $_hotel) {
            $hotels[] = (object) [
                'id' => $_hotel['CD'],
                'text' => $_hotel['CD'] .' - '. $_hotel['NAME']
            ];
        }

        return $hotels;
    }
    
    public function getHotelsFromMemCache()
    {
        $memoryCacheLink = $this->getMemoryCacheLinkCampaign();

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

            $offers = $crawler->filterXPath('//Offers/Offer')->each(function (Crawler $offer, $i) {
                return [
                    'accom_cd' => $offer->filterXPath('//Accom')->attr('Code'),
                    'accom_name' => $offer->filterXPath('//Accom')->attr('Name')
                ];
            });
            
            $hotels = [];
            
            foreach ($offers as $offer) {
                foreach($hotels as $hotel){
                    if(in_array($offer['accom_cd'], $hotel)){
                        continue 2;
                    }
                }
                $hotels[] = (array) [
                    'id' => $offer['accom_cd'],
                    'text' => $offer['accom_cd'] .' - '. $offer['accom_name']
                ];
            }
            sort($hotels);

        } catch (RequestException $e) {
            $mcException = [
                'request' => Psr7\str($e->getRequest()),
                'response' => 'Timeout',
            ];

            if ($e->hasResponse()) {
                $mcException['response'] = Psr7\str($e->getResponse());
            }
        }
        
        return $hotels;
    }
    
    public function getCarriersByAirport() {
        $sql = "
            SELECT DISTINCT
                ofn.cd
                ,ofn.name
            FROM
                atcomres.ar_transinvsector tis
                INNER JOIN atcomres.ar_point dep_pt ON dep_pt.pt_id = tis.dep_pt_id
                INNER JOIN atcomres.ar_point arr_pt ON arr_pt.pt_id = tis.arr_pt_id
                INNER JOIN atcomres.ar_officename ofn ON ofn.off_name_id = tis.carrier_id
            WHERE 
                (:dep_cd IS NULL OR dep_pt.pt_cd = :dep_cd)
                AND 
                (:arr_cd IS NULL OR arr_pt.pt_cd = :arr_cd)
            ORDER BY
                ofn.cd ASC
        ";

        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('dep_cd', $this->depCd);
        $stmt->bindValue('arr_cd', $this->arrCd);
        $stmt->execute();

        $results = $stmt->fetchAll();
        $carriers = [];
        $carriers[] = [
                'id' => 'All',
                'text' => 'All'
            ];
        
        foreach ($results as $_carrier) {
            $carriers[] = [
                'id' => $_carrier['CD'],
                'text' => $_carrier['CD'] .' - '. $_carrier['NAME']
            ];
        }

        return $carriers;
    }
    
    public function getCarriersFromMemCache()
    {
        $memoryCacheLink = $this->getMemoryCacheLinkCampaign();

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

            $offers = $crawler->filterXPath('//Offers/Offer')->each(function (Crawler $offer, $i) {
                return [
                    'carrier_cd' => $offer->filterXPath('//Transport/Route')->attr('Carrier')
                ];
            });
            
            $carriers = [];
            
            foreach ($offers as $offer) {
                foreach($carriers as $carrier){
                    if(in_array($offer['carrier_cd'], $carrier)){
                        continue 2;
                    }
                }
                $carriers[] = [
                    'id' => $offer['carrier_cd'],
                    'text' => $offer['carrier_cd']
                ];
            }
            sort($carriers);
            array_unshift($carriers, ['id' => 'All','text' => 'All']); //add 'All' at the beginning of array

        } catch (RequestException $e) {
            $mcException = [
                'request' => Psr7\str($e->getRequest()),
                'response' => 'Timeout',
            ];

            if ($e->hasResponse()) {
                $mcException['response'] = Psr7\str($e->getResponse());
            }
        }
        
        return $carriers;
    }

    /**
     * Fetches available rooms by accommodation and dates.
     * @return array
     */
    public function getAvailableRoomTypes()
    {
        $inventory = $this->getAccommodationInventoryResults();

        // If no results are returned we must return an empty result to the user.
        if (empty($inventory)) {
            return [];
        }

        // Get stop sale
        $stopSaleByAirport = $this->getStopsalesByAirportResults();
        $stopSale = $this->formatStopSaleByAirport($stopSaleByAirport);


        // Add each room to the $availableRooms variable if they're valid.
        $availableRooms = [];
        foreach ($inventory as $room) {
            $room = (object) $room;
            $invDate = (new DateTime($room->INV_DT))->format(self::DATE_FORMAT);
            $accomCd = $room->ACCOM_CD;
            $roomCd = $room->RM_CD;

            // Create basic data object. We only need a few details to the view.
            if (!isset($availableRooms[$roomCd])) {
                $availableRooms[$roomCd] = [
                    'values' => [],
                    'units' => 999,
                    'room_cd' => $roomCd,
                    'text' => $roomCd .' - '. $room->RM
                ];
            }


            /*
             * This is the simple calculation. It could be wrong.
             * Perhaps it should use the same calculation as BedControl.
             * !! Exclusivity is not included in this calculation !!
             *
             * X = Alloc - BKD
             */
            $unitsAvailable = $room->ALT_ALLOC - $room->ALT_OB;


            // @TODO Unsure what this actually means, ask someone.
            if ($room->ST_DAY_STS != 'OP') {
                $unitsAvailable = 0;
            }


            // Adjust the units available if the main remain variable is lower.
            $unitsAvailable = (int) min($room->ALT_MAIN_REMAIN, $unitsAvailable);

            // If the room/inventory doesn't have a inventory sub ID ignore this room.
            if (is_null($room->STK_SUB_ID)) {
                continue;
            }

            // If the flight has been released ignore this room.
            if (strtotime($room->ALT_REL_DT) < time()) {
                continue;
            }

            // If there's registered a stop sale on this hotel continue to next iteration.
            if (isset($stopSale[$accomCd]['ALL'][$invDate])) {
                continue;
            }

            // If there's registered a stop sale on this room continue to next iteration.
            if (isset($stopSale[$accomCd][$roomCd][$invDate])) {
                continue;
            }

            // Take the lowest value
            $availableRooms[$roomCd]['units'] = (int) min($availableRooms[$roomCd]['units'], $unitsAvailable);
            $availableRooms[$roomCd]['values'][] = $unitsAvailable;
        }

        return $availableRooms;
    }

    public function formatStopSaleByAirport($stopsalesByAirport)
    {
        $periodInterval = new \DateInterval( "P1D" ); // 1-day, though can be more sophisticated rule
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

        return $stopsales;
    }
}
