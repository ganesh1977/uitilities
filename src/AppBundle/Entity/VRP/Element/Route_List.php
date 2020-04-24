<?php

namespace AppBundle\Entity\VRP\Element;

use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\XmlList;

class Route_List {

    /**
     * @Type("array<AppBundle\Entity\VRP\Element\Routing>")
     * @XmlList(inline = true, entry = "Routing", namespace="AtComRes/Common")
     */
    public $Routing = [];

}