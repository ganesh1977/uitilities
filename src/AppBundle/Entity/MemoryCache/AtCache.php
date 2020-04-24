<?php
namespace AppBundle\Entity\MemoryCache;

use JMS\Serializer\Annotation as JMS;

class AtCache
{
    /**
     * @JMS\Type("string")
     * @JMS\XmlAttribute
     */
	public $Version;
    
    /**
     * @JMS\Type("string")
     * @JMS\XmlAttribute
     */
    public $Node;
    
    /**
     * @JMS\Type("string")
     * @JMS\XmlAttribute
     */
    public $Xsd;

    /**
     * @JMS\Type("AppBundle\Entity\MemoryCache\Result")
     */
    public $Result;
}