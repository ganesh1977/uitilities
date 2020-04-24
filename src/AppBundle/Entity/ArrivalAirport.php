<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="airport_lookups")
 */
class ArrivalAirport
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
     * @ORM\Column(name="arrival_airport", type="string", length=50)
     */
    private $arrivalAirport;

    /**
     * @var string
     *
     * @ORM\Column(name="arrival_average", type="string", length=10)
     */
    private $arrivalAverage;

    /**
     * @var string
     *
     * @ORM\Column(name="pr_pax", type="string", length=10)
     */
    private $prPax;
     /**
     * @var float
     *
     * @ORM\Column(name="tweak_lef", type="float", length=10)
     */
    private $tweakLef;
     /**
     * @var float
     *
     * @ORM\Column(name="tweak_average", type="string", length=10)
     */
     private $tweakAverage;
      
     /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=10)
     */
     private $status;
     
     
    public function getId()
    {
        return $this->id;
    }
    /**
     * Get arrival_airport
     *
     * @return string
     */
    public function getarrivalAirport()
    {
        return $this->arrivalAirport;
    }
    /**
     * Get arrival_average
     *
     * @return string
     */
    public function getArrivalAverage()
    {
        return $this->arrivalAverage;
    }
    /**
     * Get pr_pax
     *
     * @return string
     */
    public function getPrPax()
    {
        return $this->prPax;
    }
    /**
     * Get tweak_lef
     *
     * @return float
     */
    public function getTweakLef()
    {
        return $this->tweakLef;
    }
    
    
    /**
     * Get tweak_average
     *
     * @return float
     */
    public function getTweakAverage()
    {
        return $this->tweakAverage;
    }
    
    
    
     /**
     * Get destination_cost
     *
     * @return int
     */
    public function getdestinationCost()
    {
        return $this->destinationCost;
    } 
    
     /**
     * Get status
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }
    
    /**
     * Get arrival_airport
     *
     * @return string
     */
    public function setArrivalAirport($arrivalAirport)
    {        
        $this->arrivalAirport = $arrivalAirport;
        return $this;
    }
    /**
     * Get arrival_average
     *
     * @return string
     */
    public function setArrivalAverage($arrivalAverage)
    {
        $this->arrivalAverage= $arrivalAverage;
        return $this;
    }
    /**
     * Get pr_pax
     *
     * @return string
     */
    public function setPrPax($prPax)
    {
        $this->prPax = $prPax;
        return $this;
    }
    /**
     * Get tweak_lef
     *
     * @return float
     */
    public function setTweakLef($tweakLef)
    {        
        $this->tweakLef = $tweakLef;

        return $this;
    }
    
    
    /**
     * Get tweak_average
     *
     * @return float
     */
    public function setTweakAverage($tweakAverage)
    {        
        $this->tweakAverage = $tweakAverage;

        return $this;
    }
    
    
    
     /**
     * Get destination_cost
     *
     * @return int
     */
    public function setdestinationCost($destinationCost)
    {        
        $this->destinationCost = $destinationCost;
        return $this;        
    } 
    
     /**
     * Set status
     * @param int $status
     *
     * @return DEstination
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }     
}

