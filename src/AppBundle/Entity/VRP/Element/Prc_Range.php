<?php

namespace AppBundle\Entity\VRP\Element;

use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\XmlAttribute;
use JMS\Serializer\Annotation\XmlElement;

class Prc_Range {

    /**
     * @Type("string")
     * @XmlAttribute
     */
    public $LimitType;

    /**
     * @Type("integer")
     * @XmlElement(cdata=false, namespace="AtComRes/Common")
     */
    public $Value;
    
    /**
     * @Type("string")
     * @XmlElement(cdata=false, namespace="AtComRes/Common")
     */
    public $CurISO;

}