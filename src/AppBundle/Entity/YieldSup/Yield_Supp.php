<?php

namespace AppBundle\Entity\YieldSup;

use JMS\Serializer\Annotation\XmlElement;
use JMS\Serializer\Annotation\Type;

class Yield_Supp {

    /**
     * @Type("string")
     * @XmlElement(cdata=false)
     */
    public $Pkg_ID;

    /**
     * @Type("integer")
     * @XmlElement(cdata=false)
     */
    public $Adu_Sup = 0;

    /**
     * @Type("integer")
     * @XmlElement(cdata=false)
     */
    public $Adu_Base_Prc = 0;

    /**
     * @Type("integer")
     * @XmlElement(cdata=false)
     */
    public $Chd_Sup_1 = 0;

    /**
     * @Type("integer")
     * @XmlElement(cdata=false)
     */
    public $Chd_Sup_2 = 0;

    /**
     * @Type("integer")
     * @XmlElement(cdata=false)
     */
    public $Priority = 1;

    /**
     * @Type("integer")
     * @XmlElement(cdata=false)
     */
    public $Yield_Grp = 1;

    /**
     * @Type("string")
     * @XmlElement(cdata=false)
     */
    public $Hide_Sale = 'N';

    /**
     * @Type("string")
     * @XmlElement(cdata=false)
     */
    public $User_Cd = 'UTILS';

}