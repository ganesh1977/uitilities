<?php

namespace AppBundle\Service\GeckoBoard;

use Kwk\Geckoboard\Dataset\DataSetRowInterface;

class SalesDatarow implements DataSetRowInterface
{
    public $originDt;
    public $promCd;
    public $brand;
    public $mktCd;
    public $mkt;
    public $reservations;
    public $pax;
    public $revenueEur;
    public $profitEur;
    
    public function __construct($result = null)
    {
        if ($result) {
            $this->originDt = $result['ORIGIN_DT'];
            $this->promCd = $result['PROM_CD'];
            $this->mktCd = $result['MKT_CD'];
            $this->reservations = $result['RESERVATIONS'];
            $this->pax = $result['PAX'];
        }
    }
    
    
    /**
     * {@inheritDoc}
     */
    public function getData()
    {
        return [
            'origin_dt' => $this->originDt,
            'prom_cd' => $this->promCd,
            'brand' => $this->brand,
            'mkt_cd' => $this->mktCd,
            'mkt' => $this->mkt,
            'reservations' => round($this->reservations),
            'pax' => round($this->pax),
            'revenue_eur' => round($this->revenueEur),
            'profit_eur' => round($this->profitEur),
            'profit_pax_eur' => round($this->profitEur / $this->pax),
        ];
    }
}