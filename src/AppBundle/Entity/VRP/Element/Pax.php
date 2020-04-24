<?php

namespace AppBundle\Entity\VRP\Element;

use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\XmlAttribute;

class Pax {

    /**
     * @Type("integer")
     * @XmlAttribute
     */
    public $Age;

    /**
     * @Type("integer")
     * @XmlAttribute
     */
    public $Index;

}