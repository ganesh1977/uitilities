<?php
namespace AppBundle\Entity\MemoryCache;

use JMS\Serializer\Annotation as JMS;

class Offer
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
	public $Disc;
    
    /**
     * @JMS\Type("integer")
     * @JMS\XmlAttribute
     */
	public $DisY;
    
    /**
     * @JMS\Type("string")
     * @JMS\XmlAttribute
     */
	public $Type;
    
    /**
     * @JMS\Type("AppBundle\Entity\MemoryCache\Accom")
     */
    public $Accom;

    /**
     * @JMS\Type("AppBundle\Entity\MemoryCache\Transport")
     */
    public $Transport;

}