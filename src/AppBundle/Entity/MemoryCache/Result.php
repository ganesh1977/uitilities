<?php
namespace AppBundle\Entity\MemoryCache;

use JMS\Serializer\Annotation as JMS;

class Result
{
    /**
     * @JMS\Type("integer")
     * @JMS\XmlAttribute
     */
	public $Count;
    
    /**
     * @JMS\Type("integer")
     * @JMS\XmlAttribute
     */
    public $Found;
    
    /**
     * @JMS\Type("AppBundle\Entity\MemoryCache\Offers")
     */
    public $Offers;
}