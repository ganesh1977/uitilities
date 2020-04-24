<?php

namespace AppBundle\Service\GeckoBoard;

use Kwk\Geckoboard\Dataset\DataSetRowInterface;

class PASalesDatarow implements DataSetRowInterface
{
    public $route;
    public $market;
    public $bookingDt;
    public $pax;
    public $netRev;
    public $totalRev;
    public $netYield;
    public $totalYield;
    
    public function __construct()
    {
    }
    
    
    /**
     * {@inheritDoc}
     */
    public function getData()
    {
        return [
            'booking_dt' => $this->bookingDt,
            'route' => $this->route,
            'market' => $this->market,
            'pax' => $this->pax,
            'net_rev' => round($this->netRev),
            'total_rev' => round($this->totalRev),
            'net_yield' => round($this->netYield),
            'total_yield' => round($this->totalYield),
        ];
    }
}