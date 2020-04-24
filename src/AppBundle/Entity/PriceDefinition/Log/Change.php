<?php
namespace AppBundle\Entity\PriceDefinition\Log;

use Doctrine\ORM\Mapping as ORM;
use AppBundle\Entity\PriceDefinition\Log\Batch;

/**
 * @ORM\Entity
 * @ORM\Table(name="log_pricedefinition_change", indexes={@ORM\Index(name="key_data_idx", columns={"key_data"})})
 */
class Change
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * Many Changes have One Batch.
     * @ORM\ManyToOne(targetEntity="Batch", inversedBy="changes", fetch="EAGER")
     * @ORM\JoinColumn(name="batch_id", referencedColumnName="id")
     */
    private $batch;
    
    /**
     * @ORM\Column(type="string", length=70, name="key_data")
     */
    private $keyData;
    
    /**
     * @ORM\Column(type="string", length=20, name="rm_cd")
     */
    private $rmCd;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2, nullable=true, name="adu_sup")
     */
    private $aduSup;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2, nullable=true, name="chd1_sup")
     */
    private $chd1Sup;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2, nullable=true, name="chd2_sup")
     */
    private $chd2Sup;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2, nullable=true, name="adu_prc")
     */
    private $aduPrc;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2, nullable=true, name="chd1_prc")
     */
    private $chd1Prc;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2, nullable=true, name="chd2_prc")
     */
    private $chd2Prc;


    public function __construct($keyData = null, $rmCd = null, $aduSup = null, $chd1Sup = null, $chd2Sup = null, $aduPrc = null, $chd1Prc = null, $chd2Prc = null)
    {
        $this->keyData = $keyData;
        $this->rmCd = $rmCd;
        $this->aduSup = !is_null($aduSup) ? floatval($aduSup) : null;
        $this->chd1Sup = !is_null($chd1Sup) ? floatval($chd1Sup) : null;
        $this->chd2Sup = !is_null($chd2Sup) ? floatval($chd2Sup) : null;
        $this->aduPrc = !is_null($aduPrc) ? floatval($aduPrc) : null;
        $this->chd1Prc = !is_null($chd1Prc) ? floatval($chd1Prc) : null;
        $this->chd2Prc = !is_null($chd2Prc) ? floatval($chd2Prc) : null;
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
     * Set keyData
     *
     * @param string $keyData
     *
     * @return Change
     */
    public function setKeyData($keyData)
    {
        $this->keyData = $keyData;

        return $this;
    }

    /**
     * Get keyData
     *
     * @return string
     */
    public function getKeyData()
    {
        return $this->keyData;
    }

    /**
     * Set rmCd
     *
     * @param string $rmCd
     *
     * @return Change
     */
    public function setRmCd($rmCd)
    {
        $this->rmCd = $rmCd;

        return $this;
    }

    /**
     * Get rmCd
     *
     * @return string
     */
    public function getRmCd()
    {
        return $this->rmCd;
    }

    /**
     * Set aduSup
     *
     * @param string $aduSup
     *
     * @return Change
     */
    public function setAduSup($aduSup)
    {
        $this->aduSup = $aduSup;

        return $this;
    }

    /**
     * Get aduSup
     *
     * @return string
     */
    public function getAduSup()
    {
        return $this->aduSup;
    }

    /**
     * Set chd1Sup
     *
     * @param string $chd1Sup
     *
     * @return Change
     */
    public function setChd1Sup($chd1Sup)
    {
        $this->chd1Sup = $chd1Sup;

        return $this;
    }

    /**
     * Get chd1Sup
     *
     * @return string
     */
    public function getChd1Sup()
    {
        return $this->chd1Sup;
    }

    /**
     * Set chd2Sup
     *
     * @param string $chd2Sup
     *
     * @return Change
     */
    public function setChd2Sup($chd2Sup)
    {
        $this->chd2Sup = $chd2Sup;

        return $this;
    }

    /**
     * Get chd2Sup
     *
     * @return string
     */
    public function getChd2Sup()
    {
        return $this->chd2Sup;
    }


    /**
     * Set aduPrc
     *
     * @param string $aduPrc
     *
     * @return Change
     */
    public function setAduPrc($aduPrc)
    {
        $this->aduPrc = $aduPrc;

        return $this;
    }

    /**
     * Get aduPrc
     *
     * @return string
     */
    public function getAduPrc()
    {
        return $this->aduPrc;
    }

    /**
     * Set chd1Prc
     *
     * @param string $chd1Prc
     *
     * @return Change
     */
    public function setChd1Prc($chd1Prc)
    {
        $this->chd1Prc = $chd1Prc;

        return $this;
    }

    /**
     * Get chd1Prc
     *
     * @return string
     */
    public function getChd1Prc()
    {
        return $this->chd1Prc;
    }

    /**
     * Set chd2Prc
     *
     * @param string $chd2Prc
     *
     * @return Change
     */
    public function setChd2Prc($chd2Prc)
    {
        $this->chd2Prc = $chd2Prc;

        return $this;
    }

    /**
     * Get chd2Prc
     *
     * @return string
     */
    public function getChd2Prc()
    {
        return $this->chd2Prc;
    }

    /**
     * Set batch
     *
     * @param \AppBundle\Entity\PriceDefinition\Log\Batch $batch
     *
     * @return Change
     */
    public function setBatch(\AppBundle\Entity\PriceDefinition\Log\Batch $batch = null)
    {
        $this->batch = $batch;

        return $this;
    }

    /**
     * Get batch
     *
     * @return \AppBundle\Entity\PriceDefinition\Log\Batch
     */
    public function getBatch()
    {
        return $this->batch;
    }
    
    
    //IBTLPPMIPMI018170715007G--0000PMIBLL6BBLLPMIBLL6BPMIBLL
    //0123456789012345678901234567890123456789012345678901234
    //          1         2         3         4         5

    public function getStcStkCdFromKeyData()
    {
        return substr($this->keyData, 8, 6);
    }

    public function getArrPointFromKeyData()
    {
        return substr($this->keyData, 5, 3);
    }

    public function getSPPFromKeyData()
    {
        return substr($this->keyData, 1, 2);
    }

    public function getLPPFromKeyData()
    {
        return substr($this->keyData, 1, 4);
    }

    public function getDateFromKeyData()
    {
        return new \Datetime(substr($this->keyData, 14, 2) .'-' . substr($this->keyData, 16, 2) . '-' . substr($this->keyData, 18, 2));
    }
    
    public function getStayFromKeyData()
    {
        return intval(substr($this->keyData, 20, 3));
    }

    public function getTransportHeadCdFromKeyData()
    {
        return substr($this->keyData, 30, 8);
    }

}
