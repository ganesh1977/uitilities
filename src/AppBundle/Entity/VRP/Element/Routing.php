<?php

namespace AppBundle\Entity\VRP\Element;

use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\XmlElement;
use JMS\Serializer\Annotation\XmlList;

class Routing {

    /**
     * @Type("integer")
     * @XmlElement(cdata=false, namespace="AtComRes/Common")
     */
    public $Routing_Id;

    /**
     * @Type("array<AppBundle\Entity\VRP\Element\Route>")
     * @XmlList(inline = true, entry = "Route", namespace="AtComRes/Common")
     */
    public $Route;

}