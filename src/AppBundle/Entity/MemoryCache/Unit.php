<?php
namespace AppBundle\Entity\MemoryCache;

use JMS\Serializer\Annotation as JMS;

class Unit
{
    /**
     * @JMS\Type("integer")
     * @JMS\XmlAttribute
     */
	public $Id;
    
    /**
     * @JMS\Type("integer")
     * @JMS\XmlAttribute
     */
	public $Qty;
    
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
     * @JMS\Type("string")
     * @JMS\XmlAttribute
     */
	public $Board;
    
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
	public $MinPax;
    
    /**
     * @JMS\Type("integer")
     * @JMS\XmlAttribute
     */
	public $StdPax;
    
    /**
     * @JMS\Type("integer")
     * @JMS\XmlAttribute
     */
	public $MaxPax;
    
    /**
     * @JMS\Type("integer")
     * @JMS\XmlAttribute
     */
	public $MinAdu;
    
    /**
     * @JMS\Type("integer")
     * @JMS\XmlAttribute
     */
	public $MaxAdu;
    
    /**
     * @JMS\Type("integer")
     * @JMS\XmlAttribute
     */
	public $MinChd;
    
    /**
     * @JMS\Type("integer")
     * @JMS\XmlAttribute
     */
	public $MaxChd;
    
    /**
     * @JMS\Type("integer")
     * @JMS\XmlAttribute
     */
	public $MaxInf;
    
    /**
     * @JMS\Type("integer")
     * @JMS\XmlAttribute
     */
	public $Avail;
    
    /**
     * @JMS\Type("integer")
     * @JMS\XmlAttribute
     */
	public $Suid;
       
    /**
     * @JMS\Type("array<AppBundle\Entity\MemoryCache\Pax>")
     * @JMS\XmlList(inline=true, entry="Pax")
     */
    public $Pax;

}