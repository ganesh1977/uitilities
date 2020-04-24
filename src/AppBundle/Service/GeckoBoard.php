<?php
namespace AppBundle\Service;

use Doctrine\DBAL\Connection;
use GuzzleHttp\Client;
use Kwk\Geckoboard\Dataset\Client as GeckoClient;

use AppBundle\Service\GeckoBoard\SalesDataset;
use AppBundle\Service\GeckoBoard\SalesDatarow;
use AppBundle\Service\GeckoBoard\SalesAverageDataset;
use AppBundle\Service\GeckoBoard\SalesAverageDatarow;

class GeckoBoard
{
    private $connection;
    private $client;
    
    public function __construct(Connection $dbalConnection) {
        $this->connection = $dbalConnection;
        
        $httpClient = new Client(['base_uri' => 'https://api.geckoboard.com']);
        $this->client = new GeckoClient($httpClient, '28427d773f8a303d1891fd66d9080b63');
    }
    
    private function createDataset($dataSet)
    {
        return $this->client->create($dataSet);
    }

    private function deleteDataset($dataSet)
    {
        return $this->client->delete($dataSet);
    }
    
    private function appendDataset($dataSet, $rows)
    {
        return $this->client->append($dataSet, $rows);
    }


    /**
     * SALES
     */
    
    public function createSalesDataset() {
        $dataSet = new SalesDataset();
        return $this->createDataset($dataSet);
    }

    public function deleteSalesDataset() {
        $dataSet = new SalesDataset();
        return $this->deleteDataset($dataSet);
    }

    public function appendSalesDataset($daysBack = 0) {
        $dataSet = new SalesDataset();
        $rows = [];

        $stDt = new \Datetime(date('Y-m-d', time()-60*60*24*$daysBack));
        
        $sql = "SELECT
                    TO_CHAR(res.origin_dt, 'YYYY-MM-DD') ORIGIN_DT,
                    prom.cd PROM_CD,
                    mkt.cd MKT_CD,
                    SUM(res.n_pax) PAX,
                    COUNT(res.res_id) RESERVATIONS,
                    SUM(res.sell_prc) REVENUE,
                    SUM(res.prof_ex_vat) PROFIT,
                    AVG(ertsub.exch_rt) EXCHANGE_RT
                FROM
                    ATCOMRES.AR_RESERVATION res
                        INNER JOIN ATCOMRES.AR_PROMOTION prom ON prom.prom_id = res.prom_id
                        INNER JOIN ATCOMRES.AR_MARKET mkt ON mkt.mkt_id = res.mkt_id
                            INNER JOIN ATCOMRES.AR_BOOKINGTERMS bt
                                ON bt.mkt_cat_id = mkt.mkt_cat
                                AND bt.cd = prom.terms_cd
                                INNER JOIN (
                                    SELECT
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
                                ) ertsub
                                    ON ertsub.exch_table_id = bt.sell_exch_table_id
                                    AND ertsub.cur_id = res.sell_cur_id

                WHERE
                    res.origin_dt > :st_dt
                        AND
                    res.bkg_sts IN ('BKG','OPT')
                GROUP BY
                    TO_CHAR(res.origin_dt, 'YYYY-MM-DD'),
                    prom.cd,
                    mkt.cd";

        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('st_dt', $stDt, 'date');
        $stmt->execute();
        $results = $stmt->fetchAll();
        
        $i = $j = 0;
        
        foreach ($results as $result) {
            $i++;
            $j++;
            
            $row = new SalesDatarow($result);
            
            // Promotion
            if (substr($result['PROM_CD'], 0, 2) == 'BT' || $result['PROM_CD'] == 'ITM1') {
                $row->brand = 'Bravo Tours';
            } elseif (substr($result['PROM_CD'], 0, 2) == 'SR' || $result['PROM_CD'] == 'ITM2') {
                $row->brand = 'Solresor';
            } elseif (substr($result['PROM_CD'], 0, 2) == 'LM' || $result['PROM_CD'] == 'ITM3') {
                $row->brand = 'Matkavekka';
            } elseif (substr($result['PROM_CD'], 0, 2) == 'SO' || $result['PROM_CD'] == 'ITM4') {
                $row->brand = 'Solia';
            } elseif (substr($result['PROM_CD'], 0, 2) == 'HF' || $result['PROM_CD'] == 'ITM5') {
                $row->brand = 'Heimsferdir';
            } elseif (substr($result['PROM_CD'], 0, 2) == 'ST' || $result['PROM_CD'] == 'ITM6') {
                $row->brand = 'Sun Tours';
            } elseif (substr($result['PROM_CD'], 0, 2) == 'UK' || $result['PROM_CD'] == 'ITM8') {
                $row->brand = 'Primera Holidays UK';
            } else {
                $row->brand = 'Not set';
            }


            // Market
            if (in_array($result['MKT_CD'], ['G1BT', 'G2SR', 'G3LM', 'G4SO', 'G5HF', 'G6ST', 'G8UK'])) {
                $row->mkt = 'Groups';
            } elseif (in_array($result['MKT_CD'], ['DIH1', 'SIH2', 'FIH3', 'NIH4', 'IIH5', 'STI6', 'UKH8'])) {
                $row->mkt = 'In-house';
            } elseif (in_array($result['MKT_CD'], ['DW1', 'SW2', 'FW3', 'NW4', 'IW5', 'STW6', 'UKW8'])) {
                $row->mkt = 'Web';
            } else {
                echo PHP_EOL . '-- MKT: '. $result['MKT_CD'] . ' / ';
                var_dump($result['MKT_CD']);
                echo PHP_EOL;
            }
            
            // Revenue EUR
            $row->revenueEur = round($result['REVENUE'] / $result['EXCHANGE_RT'] * 100);
            
            // Profit EUR
            $row->profitEur = round($result['PROFIT'] / $result['EXCHANGE_RT'] * 100);
            
            $rows[] = $row;
            
            if (count($results) > $j && $i >= 500) {
                $i = 0;
                $response = $this->appendDataset($dataSet, $rows);
                if ($response->getStatusCode() != 200) {
                    print $response->getBody();
                } else {
                    print 'Append command sent...' . "\n";
                }
                $rows = [];
            }
        }
        
        return $this->appendDataset($dataSet, $rows);
    }
    
    

    /**
     * SALES AVERAGE
     */
    
    public function createSalesAverageDataset() {
        $dataSet = new SalesAverageDataset();
        return $this->createDataset($dataSet);
    }

    public function deleteSalesAverageDataset() {
        $dataSet = new SalesAverageDataset();
        return $this->deleteDataset($dataSet);
    }

    public function appendSalesAverageDataset($daysBack = 0) {
        $dataSet = new SalesAverageDataset();
        $rows = [];

        $stDt = new \Datetime(date('Y-m-d', time()-60*60*24*$daysBack));
        
        $sql = "SELECT
                    TRUNC(res.origin_dt) ORIGIN_DT,
                    SUBSTR(prom.cd, 0, 2) PROM_CD,
                    SUM(res.n_pax) PAX,
                    COUNT(res.res_id) RESERVATIONS,
                    SUM(res.prof_ex_vat) PROFIT,
                    AVG(ertsub.exch_rt) EXCHANGE_RT
                FROM
                    ATCOMRES.AR_RESERVATION res
                        INNER JOIN ATCOMRES.AR_PROMOTION prom ON prom.prom_id = res.prom_id
                        INNER JOIN ATCOMRES.AR_MARKET mkt ON mkt.mkt_id = res.mkt_id
                            INNER JOIN ATCOMRES.AR_BOOKINGTERMS bt
                                ON bt.mkt_cat_id = mkt.mkt_cat
                                AND bt.cd = prom.terms_cd
                                INNER JOIN (
                                    SELECT
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
                                ) ertsub
                                    ON ertsub.exch_table_id = bt.sell_exch_table_id
                                    AND ertsub.cur_id = res.sell_cur_id

                WHERE
                    TRUNC(res.origin_dt) >= :st_dt
                        AND
                    res.bkg_sts IN ('BKG','OPT')
                GROUP BY
                    TRUNC(res.origin_dt),
                    SUBSTR(prom.cd, 0, 2)";

        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('st_dt', $stDt, 'date');
        $stmt->execute();
        $results = $stmt->fetchAll();
        
        $i = $j = 0;
        
        foreach ($results as $result) {

            $i++;
            $j++;

            if (in_array($result['PROM_CD'], ['BT', 'SR', 'LM', 'SO', 'HF', 'ST', 'UK'])) {
            
                $row = new SalesAverageDatarow();
                $row->originDt = new \Datetime($result['ORIGIN_DT']);
            
                // Promotion
                switch ($result['PROM_CD'])
                {
                    case 'BT':
                        $row->brand = 'Bravo Tours';
                        break;
                    case 'SR':
                        $row->brand = 'Solresor';
                        break;
                    case 'LM':
                    case 'MV':
                        $row->brand = 'Matkavekka';
                        break;
                    case 'SO':
                        $row->brand = 'Solia';
                        break;
                    case 'HF':
                        $row->brand = 'Heimsferdir';
                        break;
                    case 'ST':
                        $row->brand = 'Sun Tours';
                        break;
                    case 'UK':
                        $row->brand = 'Primera Holidays UK';
                        break;
                    default:
                        $row->brand = 'Not set';
                }
            
                $profit = $result['PROFIT'] / $result['EXCHANGE_RT'] * 100;
                
                $row->paxReservation = $result['PAX'] / $result['RESERVATIONS'];
                $row->profitPax = $profit / $result['PAX'];
                $row->profitReservation = $profit / $result['RESERVATIONS'];
            
                $rows[] = $row;
            }

            if (count($results) > $j && $i >= 500) {
                $i = 0;
                $response = $this->appendDataset($dataSet, $rows);
                if ($response->getStatusCode() != 200) {
                    print $response->getBody();
                } else {
                    print 'Append command sent...' . "\n";
                }
                $rows = [];
            }

        }
        
        return $this->appendDataset($dataSet, $rows);
    }
}

