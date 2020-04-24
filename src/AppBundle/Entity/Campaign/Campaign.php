<?php
namespace AppBundle\Entity\Campaign;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use AppBundle\Entity\Campaign\Offer;

/**
 * @ORM\Entity
 * @ORM\Table(name="campaigns")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\CampaignRepository")
 */
class Campaign
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=8, nullable=false)
     */
    private $prom_cd;
    
    /**
     * @ORM\Column(type="string", length=8, nullable=false)
     */
    private $promotion_cd;

    /**
     * @ORM\Column(type="string", length=16, nullable=false)
     */
    private $cd;

    /**
     * @ORM\Column(type="datetimetz", nullable=false)
     */
    private $st_dt;

    /**
     * @ORM\Column(type="datetimetz", nullable=false)
     */
    private $end_dt;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $description;

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
     * One Campaign has many Offers.
     * @ORM\OneToMany(targetEntity="Offer", mappedBy="campaign")
     * @ORM\OrderBy({"sort" = "ASC"})
     */
    private $offers;

    /**
     * @ORM\Column(type="boolean",nullable=true)
     */
    private $overrule_sort;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->offers = new ArrayCollection();
    }



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
     * Set cd
     *
     * @param string $cd
     *
     * @return Campaign
     */
    public function setCd($cd)
    {
        $this->cd = $cd;

        return $this;
    }

    /**
     * Get cd
     *
     * @return string
     */
    public function getCd()
    {
        return $this->cd;
    }

    /**
     * Set stDt
     *
     * @param \DateTime $stDt
     *
     * @return Campaign
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
     * Set endDt
     *
     * @param \DateTime $endDt
     *
     * @return Campaign
     */
    public function setEndDt($endDt)
    {
        $this->end_dt = $endDt;

        return $this;
    }

    /**
     * Get endDt
     *
     * @return \DateTime
     */
    public function getEndDt()
    {
        return $this->end_dt;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return Campaign
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Add offer
     *
     * @param \AppBundle\Entity\Campaign\Offer $offer
     *
     * @return Campaign
     */
    public function addOffer(Offer $offer)
    {
        $this->offers[] = $offer;

        return $this;
    }

    /**
     * Remove offer
     *
     * @param \AppBundle\Entity\Campaign\Offer $offer
     */
    public function removeOffer(Offer $offer)
    {
        $this->offers->removeElement($offer);
    }

    /**
     * Get offers
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getOffers()
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->isNull('deleted_at'));

        return $this->offers->matching($criteria);
    }

    /**
     * Set promCd
     *
     * @param string $promCd
     *
     * @return Campaign
     */
    public function setPromCd($promCd)
    {
        $this->prom_cd = $promCd;

        return $this;
    }

    /**
     * Get promCd
     *
     * @return string
     */
    public function getPromCd()
    {
        return $this->prom_cd;
    }
    
    /**
     * Set promotionCd
     *
     * @param string $promotionCd
     *
     * @return Campaign
     */
    public function setPromotionCd($promotionCd)
    {
        $this->promotion_cd = $promotionCd;
        return $this;
    }
    
    /**
     * Get extPromCd
     *
     * @return string
     */
    public function getPromotionCd()
    {
        return $this->promotion_cd;
    }

    /**
     * @return \DateTime
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
     * @return \DateTime
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
     * @return \DateTime
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

    /**
     * @return \DateTime
     */
    public function getOverruleSort()
    {
        return $this->overrule_sort;
    }

    /**
     * @param mixed $deleted_at
     */
    public function setOverruleSort($overrule_sort)
    {
        $this->overrule_sort = $overrule_sort;
    }

    
    /**
     * Helper function to get current highest sort amongst the associated offers
     */
    public function getHighestSort()
    {
        $sort = 0;
        foreach ($this->offers as $offer) {
            $sort = max($sort, $offer->getSort());
        }
        return $sort;
    }
}
