<?php

namespace AppBundle\Service\GeckoBoard;

use Kwk\Geckoboard\Dataset\DataSetRowInterface;

class SalesAverageDatarow implements DataSetRowInterface
{
    public $originDt;
    public $brand;
    public $paxReservation;
    public $profitPax;
    public $profitReservation;
    
    /**
     * {@inheritDoc}
     */
    public function getData()
    {
        return [
            'origin_dt' => $this->originDt->format('Y-m-d'),
            'brand' => $this->brand,
            'pax_reservation' => round($this->paxReservation),
            'profit_pax' => round($this->profitPax),
            'profit_reservation' => round($this->profitReservation),
        ];
    }
}