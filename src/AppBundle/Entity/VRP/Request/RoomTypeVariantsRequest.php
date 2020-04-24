<?php

namespace AppBundle\Entity\VRP\Request;

use JMS\Serializer\Annotation\XmlRoot;
use JMS\Serializer\Annotation\XmlNamespace;
use JMS\Serializer\Annotation\XmlElement;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Type;

/**
 * @XmlRoot("RoomTypeVariantsRequest")
 * @XmlNamespace(uri="AtComRes/RoomTypeVariantsRequest")
 * @XmlNamespace(uri="AtComRes/Common", prefix="p2")
 */
class RoomTypeVariantsRequest {

    /**
     * @Type("AppBundle\Entity\VRP\Element\CltInfo")
     * @XmlElement(namespace="AtComRes/Common")
     */
    public $CltInfo;

    /**
     * @Type("AppBundle\Entity\VRP\Element\Adm")
     * @XmlElement(namespace="AtComRes/Common")
     */
    public $Adm;

    /**
     * @Type("AppBundle\Entity\VRP\Element\Offer_Ctl")
     * @XmlElement(namespace="AtComRes/Common")
     */
    public $Offer_Ctl;

}