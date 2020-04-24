<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="geodata")
 */
class Geodata
{
    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=6)
     */
    private $stc_stk_cd;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $address;

    /**
     * @ORM\Column(type="decimal", scale=7, nullable=true)
     */
    private $latitude;

    /**
     * @ORM\Column(type="decimal", scale=7, nullable=true)
     */
    private $longitude;
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $place_id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $update_dt_tm;


    /**
     * Set stcStkCd
     *
     * @param string $stcStkCd
     *
     * @return Geodata
     */
    public function setStcStkCd($stcStkCd)
    {
        $this->stc_stk_cd = $stcStkCd;

        return $this;
    }

    /**
     * Get stcStkCd
     *
     * @return string
     */
    public function getStcStkCd()
    {
        return $this->stc_stk_cd;
    }

    /**
     * Set address
     *
     * @param string $address
     *
     * @return Geodata
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set latitude
     *
     * @param string $latitude
     *
     * @return Geodata
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;

        return $this;
    }

    /**
     * Get latitude
     *
     * @return string
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * Set longitude
     *
     * @param string $longitude
     *
     * @return Geodata
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;

        return $this;
    }

    /**
     * Get longitude
     *
     * @return string
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * Set updateDtTm
     *
     * @param \DateTime $updateDtTm
     *
     * @return Geodata
     */
    public function setUpdateDtTm($updateDtTm)
    {
        $this->update_dt_tm = $updateDtTm;

        return $this;
    }

    /**
     * Get updateDtTm
     *
     * @return \DateTime
     */
    public function getUpdateDtTm()
    {
        return $this->update_dt_tm;
    }

    /**
     * Set placeId
     *
     * @param string $placeId
     *
     * @return Geodata
     */
    public function setPlaceId($placeId)
    {
        $this->place_id = $placeId;

        return $this;
    }

    /**
     * Get placeId
     *
     * @return string
     */
    public function getPlaceId()
    {
        return $this->place_id;
    }
}
