<?php

namespace AppBundle\Entity\VRP\Element;

use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\XmlElement;

class Loc {

    /**
     * @Type("string")
     * @XmlElement(cdata=false, namespace="AtComRes/Common")
     */
    public $Loc_Cd;

    /**
     * @Type("string")
     * @XmlElement(cdata=false, namespace="AtComRes/Common")
     */
    public $Loc_Tp;

}