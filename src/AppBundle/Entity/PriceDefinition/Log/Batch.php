<?php
namespace AppBundle\Entity\PriceDefinition\Log;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use AppBundle\Entity\PriceDefinition\Log\Change;
use Primera\UserBundle\Entity\User;

/**
 * @ORM\Entity
 * @ORM\Table(name="log_pricedefinition_batch")
 */
class Batch
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * Many Batch has One User.
     * @ORM\ManyToOne(targetEntity="Primera\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user;

    /**
     * One Batch has Many Changes.
     * @ORM\OneToMany(targetEntity="Change", mappedBy="batch", fetch="EAGER")
     */
    private $changes;

    /**
     * @ORM\Column(type="datetime", name="update_dt_tm")
     */
    private $updateDtTm;

    /**
     * Constructor
     */
    public function __construct($user = null)
    {
        $this->changes = new ArrayCollection();
        
        $this->user = $user;
        $this->updateDtTm = new \DateTime('now');
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
     * Set updateDtTm
     *
     * @param \DateTime $updateDtTm
     *
     * @return Batch
     */
    public function setUpdateDtTm($updateDtTm)
    {
        $this->updateDtTm = $updateDtTm;

        return $this;
    }

    /**
     * Get updateDtTm
     *
     * @return \DateTime
     */
    public function getUpdateDtTm()
    {
        return $this->updateDtTm;
    }

    /**
     * Set user
     *
     * @param User $user
     *
     * @return Batch
     */
    public function setUser(User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Add change
     *
     * @param Change $change
     *
     * @return Batch
     */
    public function addChange(Change $change)
    {
        $this->changes[] = $change;

        return $this;
    }

    /**
     * Remove change
     *
     * @param Change $change
     */
    public function removeChange(Change $change)
    {
        $this->changes->removeElement($change);
    }

    /**
     * Get changes
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChanges()
    {
        return $this->changes;
    }
}
