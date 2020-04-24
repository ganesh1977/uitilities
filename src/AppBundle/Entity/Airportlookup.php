<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="airport_lookups")
*/

class Airportlookup
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", length=10)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="airport_code", type="string", length=50)
     */
    private $airportCode;
    
    
    
    /**
     * @var string
     *
     * @ORM\Column(name="cost_pr_pax", type="string", length=10)
     */
    private $costPrPax;

    /**
     * @var string
     *
     * @ORM\Column(name="handling_fee_pr_pax", type="string", length=10)
     */
    private $handlingFeePrPax;
	
	
	/**
     * @var string
     *
     * @ORM\Column(name="empty_leg_average", type="string", length=10)
     */
    private $emptyLegAverage;
	
	
	/**
     * @var string
     *
     * @ORM\Column(name="empty_leg_per", type="string", length=10)
     */
    private $emptyLegPer;
	
	
	/**
     * @var string
     *
     * @ORM\Column(name="vat", type="string", length=10)
     */
    private $vat;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="integer", length=1)
     */
    private $status;

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
    
	
	 /**
     * Set percentage
     *
     * @param string $percentage
     *
     * @return airport_lookups_new
     */
    public function setAirportCode($airportCode)
    {
        $this->airportCode = $airportCode;

        return $this;
    }
	
	/**
     * Get airportCode
     *
     * @return string
     */
    public function getAirportCode()
    {
        return $this->airportCode;
    }
	
	 /**
     * Set costPrPax
     *
     * @param string $costPrPax
     *
     * @return airport_lookups_new
     */
    public function setCostPrPax($costPrPax)
    {
        $this->costPrPax = $costPrPax;

        return $this;
    }
	
	/**
     * Get costPrPax
     *
     * @return string
     */
    public function getCostPrPax()
    {
        return $this->costPrPax;
    }
	
	
	
	/**
     * Set handlingFeePrPax
     *
     * @param string $handlingFeePrPax
     *
     * @return airport_lookups_new
     */
    public function setHandlingFeePrPax($handlingFeePrPax)
    {
        $this->handlingFeePrPax = $handlingFeePrPax;

        return $this;
    }
	
	/**
     * Get handlingFeePrPax
     *
     * @return string
     */
    public function getHandlingFeePrPax()
    {
        return $this->handlingFeePrPax;
    }
	
		/**
     * Set emptyLegAverage
     *
     * @param string $emptyLegAverage
     *
     * @return airport_lookups_new
     */
    public function setEmptyLegAverage($emptyLegAverage)
    {
        $this->emptyLegAverage = $emptyLegAverage;

        return $this;
    }
	
	/**
     * Get emptyLegAverage
     *
     * @return string
     */
    public function getEmptyLegAverage()
    {
        return $this->emptyLegAverage;
    }
	
	
		/**
     * Set emptyLegPer
     *
     * @param string $emptyLegPer
     *
     * @return airport_lookups_new
     */
    public function setEmptyLegPer($emptyLegPer)
    {
        $this->emptyLegPer = $emptyLegPer;

        return $this;
    }
	
	/**
     * Get emptyLegAverage
     *
     * @return string
     */
    public function getEmptyLegPer()
    {
        return $this->emptyLegPer;
    }
	
	
		/**
     * Set vat
     *
     * @param string $vat
     *
     * @return airport_lookups_new
     */
    public function setVat($vat)
    {
        $this->vat = $vat;

        return $this;
    }
	
	/**
     * Get vat
     *
     * @return string
     */
    public function getVat()
    {
        return $this->vat;
    }
	
	
    /**
     * Set status
     * @param int $status
     *
     * @return airport_lookups_new
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    
}

