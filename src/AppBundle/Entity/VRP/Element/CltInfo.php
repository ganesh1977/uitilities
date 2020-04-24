<?php

namespace AppBundle\Entity\VRP\Element;

use JMS\Serializer\Annotation\XmlElement;

class CltInfo {

    /**
     * @XmlElement(cdata=false, namespace="AtComRes/Common")
     */
    public $Locale;

    /**
     * @XmlElement(cdata=false, namespace="AtComRes/Common")
     */
    public $CltSysContext = 3;

    /**
     * @XmlElement(cdata=false, namespace="AtComRes/Common")
     */
    public $Agt_No;

    /**
     * @XmlElement(cdata=false, namespace="AtComRes/Common")
     */
    public $TermCode = 'WEB';

    /**
     * @XmlElement(cdata=false, namespace="AtComRes/Common")
     */
    public $User_Name = 'XROADBT';

    /**
     * @XmlElement(cdata=false, namespace="AtComRes/Common")
     */
    public $Chan = 'inhouse';

    /**
     * @XmlElement(cdata=false, namespace="AtComRes/Common")
     */
    public $Channel_Type = 'VRP';

    /**
     * @XmlElement(cdata=false, namespace="AtComRes/Common")
     */
    public $User_Role;

}