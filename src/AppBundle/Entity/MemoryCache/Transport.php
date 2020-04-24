<?php
namespace AppBundle\Entity\MemoryCache;

use JMS\Serializer\Annotation as JMS;

class Transport
{
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
	public $PrcLvl;
    
    /**
     * @JMS\Type("integer")
     * @JMS\XmlAttribute
     */
	public $Avail;
       
    /**
     * @JMS\Type("array<AppBundle\Entity\MemoryCache\Route>")
     * @JMS\XmlList(inline=true, entry="Route")
     */
    public $Route;

}