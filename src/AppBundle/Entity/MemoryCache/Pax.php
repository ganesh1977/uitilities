<?php
namespace AppBundle\Entity\MemoryCache;

use JMS\Serializer\Annotation as JMS;

class Pax
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
	public $Age;

}