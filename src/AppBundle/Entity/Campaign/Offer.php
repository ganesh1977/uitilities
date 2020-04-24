<?php
namespace AppBundle\Entity\Campaign;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use AppBundle\Entity\Campaign\Campaign;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="campaign_offers")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\OfferRepository")
 */
class Offer
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * Many Offers have one Campaign.
     * @ORM\ManyToOne(targetEntity="Campaign", inversedBy="offers")
     * @ORM\JoinColumn(name="campaign", referencedColumnName="id")
     */
    private $campaign;
    
    /**
     * @ORM\Column(type="date", nullable=false)
     */
    protected $st_dt;

    /**
     * @ORM\Column(type="date", nullable=false)
     */
    protected $end_dt;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $stay;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $dep_cd;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $arr_cd;
    
    /**
     * @ORM\Column(type="string", length=16, nullable=true)
     */
    protected $carr_cd;

    /**
     * @ORM\Column(type="string", length=16, nullable=false)
     */
    protected $stc_stk_cd;

    /**
     * @ORM\Column(type="string", length=16, nullable=true)
     */
    protected $rm_cd;

    /**
     * @ORM\Column(type="string", length=16, nullable=true)
     */
    protected $bb;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $sort;

    /**
     * @ORM\Column(type="datetime",nullable=true)
     */
    private $created_at;

    /**
     * @ORM\Column(type="datetime",nullable=true)
     */
    private $updated_at;

    /**
     * @ORM\Column(type="datetime",nullable=true)
     */
    private $deleted_at;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set stDt
     *
     * @param \DateTime $stDt
     *
     * @return Offer
     */
    public function setStDt($stDt)
    {
        $this->st_dt = $stDt;

        return $this;
    }

    /**
     * Get stDt
     *
     * @return \DateTime
     */
    public function getStDt()
    {
        return $this->st_dt;
    }


    /**
     * @return mixed
     */
    public function getEndDt()
    {
        return $this->end_dt;
    }


    /**
     * @param mixed $end_dt
     */
    public function setEndDt($end_dt)
    {
        $this->end_dt = $end_dt;
    }


    /**
     * Set stay
     *
     * @param integer $stay
     *
     * @return Offer
     */
    public function setStay($stay)
    {
        $this->stay = $stay;

        return $this;
    }

    /**
     * Get stay
     *
     * @return integer
     */
    public function getStay()
    {
        return $this->stay;
    }

    /**
     * Set depCd
     *
     * @param string $depCd
     *
     * @return Offer
     */
    public function setDepCd($depCd)
    {
        $this->dep_cd = $depCd;

        return $this;
    }

    /**
     * Get depCd
     *
     * @return string
     */
    public function getDepCd()
    {
        return $this->dep_cd;
    }

    /**
     * @return mixed
     */
    public function getArrCd()
    {
        return $this->arr_cd;
    }

    /**
     * @param mixed $arr_cd
     */
    public function setArrCd($arr_cd)
    {
        $this->arr_cd = $arr_cd;
    }
    
    /**
     * Set carrCd
     *
     * @param string $stcStkCd
     *
     * @return Offer
     */
    public function setCarrCd($carrCd)
    {
        $this->carr_cd = $carrCd;

        return $this;
    }

    /**
     * Get carrCd
     *
     * @return string
     */
    public function getCarrCd()
    {
        return $this->carr_cd;
    }

    /**
     * Set stcStkCd
     *
     * @param string $stcStkCd
     *
     * @return Offer
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
     * Set rmCd
     *
     * @param string $rmCd
     *
     * @return Offer
     */
    public function setRmCd($rmCd)
    {
        $this->rm_cd = $rmCd;

        return $this;
    }

    /**
     * Get rmCd
     *
     * @return string
     */
    public function getRmCd()
    {
        return $this->rm_cd;
    }

    /**
     * Set campaign
     *
     * @param \AppBundle\Entity\Campaign\Campaign $campaign
     *
     * @return Offer
     */
    public function setCampaign(Campaign $campaign = null)
    {
        $this->campaign = $campaign;

        return $this;
    }

    /**
     * Get campaign
     *
     * @return \AppBundle\Entity\Campaign\Campaign
     */
    public function getCampaign()
    {
        return $this->campaign;
    }

    /**
     * Set bb
     *
     * @param string $bb
     *
     * @return Offer
     */
    public function setBb($bb)
    {
        $this->bb = $bb;

        return $this;
    }

    /**
     * Get bb
     *
     * @return string
     */
    public function getBb()
    {
        return $this->bb;
    }

    /**
     * Set sort
     *
     * @param integer $sort
     *
     * @return Offer
     */
    public function setSort($sort)
    {
        $this->sort = $sort;

        return $this;
    }

    /**
     * Get sort
     *
     * @return integer
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * @param mixed $created_at
     */
    public function setCreatedAt($created_at)
    {
        $this->created_at = $created_at;
    }

    /**
     * @return mixed
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    /**
     * @param mixed $updated_at
     */
    public function setUpdatedAt($updated_at)
    {
        $this->updated_at = $updated_at;
    }

    /**
     * @return mixed
     */
    public function getDeletedAt()
    {
        return $this->deleted_at;
    }

    /**
     * @param mixed $deleted_at
     */
    public function setDeletedAt($deleted_at)
    {
        $this->deleted_at = $deleted_at;
    }

}
