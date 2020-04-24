<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;





/**
 * @ORM\Entity
 * @ORM\Table(name="cost")
 */
class Cost
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
     * @ORM\Column(name="servicecategory", type="string", length=50)
     */
    private $servicecategory;
    
    
    
    /**
     * @var string
     *
     * @ORM\Column(name="servicecode", type="string", length=10)
     */
    private $servicecode;

    /**
     * @var string
     *
     * @ORM\Column(name="percentage", type="string", length=10)
     */
    private $percentage;

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
     * Get servicecode
     *
     * @return string
     */
    public function getServicecode()
    {
        return $this->servicecode;
    }
    
    
    /**
     * Get servicecategory
     *
     * @return string
     */
    public function getServicecategory()
    {
        return $this->servicecategory;
    }
    /**
     * Get percentage
     *
     * @return string
     */
    public function getPercentage()
    {
        return $this->percentage;
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
     * Set servicecode
     *
     * @param string $servicecategory
     *
     * @return Cost
     */
    public function setServicecode($servicecode)
    {
        $this->servicecode = $servicecode;

        return $this;
    }
    
    /**
     * Set servicecategory
     *
     * @param string $servicecategory
     *
     * @return Cost
     */
    public function setServicecategory($servicecategory)
    {
        $this->servicecategory = $servicecategory;

        return $this;
    }
    
    /**
     * Set percentage
     *
     * @param string $percentage
     *
     * @return Cost
     */
    public function setPercentage($percentage)
    {
        $this->percentage = $percentage;

        return $this;
    }
    /**
     * Set status
     * @param int $status
     *
     * @return Cost
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    
}

