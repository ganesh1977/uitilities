<?php
namespace AppBundle\Entity\MemoryCache;

use JMS\Serializer\Annotation as JMS;

class Accom
{
    /**
     * @JMS\Type("DateTime<'Y-m-d'>")
     * @JMS\XmlAttribute
     */
	public $Date;
    
    /**
     * @JMS\Type("integer")
     * @JMS\XmlAttribute
     */
	public $Stay;
    
    /**
     * @JMS\Type("string")
     * @JMS\XmlAttribute
     */
	public $Code;
    
    /**
     * @JMS\Type("string")
     * @JMS\XmlAttribute
     */
	public $Name;
    
    /**
     * @JMS\Type("integer")
     * @JMS\XmlAttribute
     */
	public $Rating;
    
    /**
     * @JMS\Type("integer")
     * @JMS\XmlAttribute
     */
	public $Pri;
    
    /**
     * @JMS\Type("integer")
     * @JMS\XmlAttribute
     */
	public $Prom;
 
    /**
     * @JMS\Type("string")
     * @JMS\XmlAttribute
     */
	public $PromCd;
    
    /**
     * @JMS\Type("integer")
     * @JMS\XmlAttribute
     */
	public $PageNo;
    
    /**
     * @JMS\Type("integer")
     * @JMS\XmlAttribute
     */
	public $MinChAge;
    
    /**
     * @JMS\Type("integer")
     * @JMS\XmlAttribute
     */
	public $MaxChAge;
    
    /**
     * @JMS\Type("string")
     * @JMS\XmlAttribute
     */
	public $Cty1;
    
    /**
     * @JMS\Type("string")
     * @JMS\XmlAttribute
     */
	public $Cty2;
    
    /**
     * @JMS\Type("string")
     * @JMS\XmlAttribute
     */
	public $Cty3;
    
    /**
     * @JMS\Type("string")
     * @JMS\XmlAttribute
     */
	public $Region;
    
    /**
     * @JMS\Type("integer")
     * @JMS\XmlAttribute
     */
	public $Ssid;
       
    /**
     * @JMS\Type("array<AppBundle\Entity\MemoryCache\Unit>")
     * @JMS\XmlList(inline=true, entry="Unit")
     */
    public $Unit;

}