<?php

namespace AppBundle\Entity\VRP\Element;

use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\XmlList;

class Occs {

    /**
     * @Type("array<AppBundle\Entity\VRP\Element\Occ>")
     * @XmlList(inline = true, entry = "Occ", namespace="AtComRes/Common")
     */
    public $Occ = [];

}