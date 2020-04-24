<?php

namespace AppBundle\Entity\VRP\Element;

use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\XmlElement;
use JMS\Serializer\Annotation\XmlList;

class Offer_Ctl {

    /**
     * @Type("integer")
     * @XmlElement(cdata=false, namespace="AtComRes/Common")
     */
    public $Page = 1;

    /**
     * @Type("integer")
     * @XmlElement(cdata=false, namespace="AtComRes/Common")
     */
    public $NumOfResPerPage = 25;

    /**
     * @Type("array<AppBundle\Entity\VRP\Element\Sort>")
     * @XmlList(inline = true, entry = "Sort", namespace="AtComRes/Common")
     */
    public $Sort = [];

}