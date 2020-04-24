<?php
namespace AppBundle\Entity\MemoryCache;

use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation as JMS;

class Offers
{
    /**
     * @JMS\Type("integer")
     * @JMS\XmlAttribute
     */
	public $Count;
    
    /**
     * @JMS\Type("array<AppBundle\Entity\MemoryCache\Offer>")
     * @JMS\XmlList(inline=true, entry="Offer")
     */
    public $Offer;
    
}