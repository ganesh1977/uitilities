<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="destination")
 */
class Destination
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
    private $airportcode;

    /**
     * @var string
     *
     * @ORM\Column(name="resort", type="string", length=10)
     */
    private $resort;

    /**
     * @var string
     *
     * @ORM\Column(name="destination_code", type="string", length=10)
     */
    private $destinationcode;
     /**
     * @var float
     *
     * @ORM\Column(name="autogen_val", type="float", length=10)
     */
    private $autogenval;
	
	 /**
     * @var int
     *
     * @ORM\Column(name="destination_cost", type="string", length=10)
     */
    private $destinationCost;
	
     /**
     * @var int
     *
     * @ORM\Column(name="status", type="string", length=10)
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
     * Get airportcode
     *
     * @return string
     */
    public function getAirportcode()
    {
        return $this->airportcode;
    }
    /**
     * Get destinationcode
     *
     * @return string
     */
    public function getDestinationcode()
    {
        return $this->destinationcode;
    }
    /**
     * Get resort
     *
     * @return string
     */
    public function getResort()
    {
        return $this->resort;
    }
    /**
     * Get autogenval
     *
     * @return float
     */
    public function getAutogenval()
    {
        return $this->autogenval;
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
     * Set autogenval
     *
     * @param float $autogenval
     *
     * @return Destination
     */
    public function setAutogenval($autogenval)
    {
        $this->autogenval = $autogenval;

        return $this;
    }
    /**
     * Set destinationcode
     *
     * @param string $destinationcode
     *
     * @return Destination
     */
    public function setDestinationcode($destinationcode)
    {
        $this->destinationcode = $destinationcode;

        return $this;
    }
    /**
     * Set airportcode
     *
     * @param string $airportcode
     *
     * @return Destination
     */
    public function setAirportcode($airportcode)
    {
        $this->airportcode = $airportcode;

        return $this;
    }
    /**
     * Set resort
     *
     * @param string $resort
     *
     * @return Destination
     */
    public function setResort($resort)
    {
        $this->resort = $resort;

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
	
	/**
     * set destination_cost
     *
     * @return int
     */
	
	public function setDestinationCost($destinationCost)
	{
		$this->destinationCost = $destinationCost;

        return $this;
	}
	
	/**
     * Get destination_cost
     *
     * @return int
     */
	public function getDestinationCost()
	{
		 return $this->destinationCost;
	}
}