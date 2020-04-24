<?php

namespace AppBundle\Entity\VRP\Element;

use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\XmlAttribute;
use JMS\Serializer\Annotation\XmlList;

class Occ {

    /**
     * @Type("integer")
     * @XmlAttribute
     */
    public $Rm_No;

    /**
     * @Type("array<AppBundle\Entity\VRP\Element\Pax>")
     * @XmlList(inline = true, entry = "Pax", namespace="AtComRes/Common")
     */
    public $Pax;

}