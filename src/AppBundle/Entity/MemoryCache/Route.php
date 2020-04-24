<?php
namespace AppBundle\Entity\MemoryCache;

use JMS\Serializer\Annotation as JMS;

class Route
{
    /**
     * @JMS\Type("integer")
     * @JMS\XmlAttribute
     */
	public $Id;
    
    /**
     * @JMS\Type("string")
     * @JMS\XmlAttribute
     */
	public $Code;
    
    /**
     * @JMS\Type("string")
     * @JMS\XmlAttribute
     */
	public $DepPt;
    
    /**
     * @JMS\Type("DateTime<'Y-m-d'>")
     * @JMS\XmlAttribute
     */
	public $DepDate;
    
    /**
     * @JMS\Type("string")
     * @JMS\XmlAttribute
     */
	public $DepTime;
    
    /**
     * @JMS\Type("string")
     * @JMS\XmlAttribute
     */
	public $ArrPt;
    
    /**
     * @JMS\Type("DateTime<'Y-m-d'>")
     * @JMS\XmlAttribute
     */
	public $ArrDate;
    
    /**
     * @JMS\Type("string")
     * @JMS\XmlAttribute
     */
	public $ArrTime;
    
    /**
     * @JMS\Type("string")
     * @JMS\XmlAttribute
     */
	public $Carrier;
    
    /**
     * @JMS\Type("string")
     * @JMS\XmlAttribute
     */
	public $FltNo;
    
    /**
     * @JMS\Type("integer")
     * @JMS\XmlAttribute
     */
	public $Price;
    
    /**
     * @JMS\Type("integer")
     * @JMS\XmlAttribute
     */
	public $AdPrice;
    
    /**
     * @JMS\Type("integer")
     * @JMS\XmlAttribute
     */
	public $Avail;
    
    /**
     * @JMS\Type("integer")
     * @JMS\XmlAttribute
     */
	public $TirId;
    
    /**
     * @JMS\Type("integer")
     * @JMS\XmlAttribute
     */
	public $BoxId;

}