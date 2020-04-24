<?php

namespace AppBundle\Entity\VRP\Element;

use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\XmlAttribute;
use JMS\Serializer\Annotation\XmlElement;

class Route {

    /**
     * @Type("string")
     * @XmlAttribute
     */
    public $Rt_Dir;

    /**
     * @Type("string")
     * @XmlElement(cdata=false, namespace="AtComRes/Common")
     */
    public $Dep_Air_Cd;

    /**
     * @Type("string")
     * @XmlElement(cdata=false, namespace="AtComRes/Common")
     */
    public $Arr_Air_Cd;

}