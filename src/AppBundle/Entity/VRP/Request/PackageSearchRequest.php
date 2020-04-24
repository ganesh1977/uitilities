<?php

namespace AppBundle\Entity\VRP\Request;

use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\XmlElement;
use JMS\Serializer\Annotation\XmlList;
use JMS\Serializer\Annotation\XmlNamespace;
use JMS\Serializer\Annotation\XmlRoot;

/**
 * @XmlRoot("p1:PackageSearchRequest")
 * @XmlNamespace(uri="AtComRes/PackageSearchRequest", prefix="p1")
 * @XmlNamespace(uri="AtComRes/Common", prefix="p2")
 */
class PackageSearchRequest {

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

    /**
     * @Type("DateTime<'Y-m-d'>")
     * @XmlElement(cdata=false, namespace="AtComRes/Common")
     */
    public $St_Dt;

    /**
     * @Type("integer")
     * @XmlElement(cdata=false, namespace="AtComRes/Common")
     */
    public $St_Dt_Plus_Days;

    /**
     * @Type("integer")
     * @XmlElement(cdata=false, namespace="AtComRes/Common")
     */
    public $St_Dt_Minus_Days;

    /**
     * @Type("DateTime<'Y-m-d'>")
     * @XmlElement(cdata=false, namespace="AtComRes/Common")
     */
    public $End_Dt;

    /**
     * @Type("integer")
     * @XmlElement(cdata=false, namespace="AtComRes/Common")
     */
    public $Stay_Plus_Days;

    /**
     * @Type("integer")
     * @XmlElement(cdata=false, namespace="AtComRes/Common")
     */
    public $Stay_Minus_Days;

    /**
     * @Type("array<AppBundle\Entity\VRP\Element\Prc_Range>")
     * @XmlList(inline = true, entry = "Prc_Range", namespace="AtComRes/Common")
     */
    public $Prc_Range = [];

    /**
     * @Type("array<AppBundle\Entity\VRP\Element\Prom>")
     * @XmlList(inline = true, entry = "Prom", namespace="AtComRes/Common")
     */
    public $Prom = [];

    /**
     * @Type("array<AppBundle\Entity\VRP\Element\Prom_Grp>")
     * @XmlList(inline = true, entry = "Prom_Grp", namespace="AtComRes/Common")
     */
    public $Prom_Grp = [];
    
    /**
     * @Type("string")
     * @XmlElement(cdata=false, namespace="AtComRes/Common")
     */
    public $BB_Cd;

    /**
     * @Type("array<AppBundle\Entity\VRP\Element\Loc>")
     * @XmlList(inline = true, entry = "Loc", namespace="AtComRes/Common")
     */
    public $Loc;

    /**
     * @Type("AppBundle\Entity\VRP\Element\Route_List")
     * @XmlElement(cdata=false, namespace="AtComRes/Common")
     */
    public $Route_List;

    /**
     * @Type("AppBundle\Entity\VRP\Element\Occs")
     * @XmlElement(cdata=false, namespace="AtComRes/Common")
     */
    public $Occs;
}