<?php

namespace AppBundle\Entity\VRP\Element;

use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\XmlAttribute;
use JMS\Serializer\Annotation\XmlElement;

class Adm {

    /**
     * @XmlAttribute
     */
    public $Xsd_Ver = '4.0.0';

    /**
     * @XmlAttribute
     */
    public $Debug;

    /**
     * @XmlElement(cdata=false, namespace="AtComRes/Common")
     */
    public $RegId;

    /**
     * @XmlElement(cdata=false, namespace="AtComRes/Common")
     */
    public $Tm;

    /**
     * @Type("AppBundle\Entity\VRP\Element\Trk")
     * @XmlElement(cdata=false, namespace="AtComRes/Common")
     */
    public $Trk;

    public function __contruct()
    {
        $this->Tm = date('c');
    }
}