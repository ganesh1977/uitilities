<?php

namespace AppBundle\Entity\YieldSup;

use JMS\Serializer\Annotation\XmlElement;
use JMS\Serializer\Annotation\Type;

class Control {

    /**
     * @Type("integer")
     * @XmlElement(cdata=false)
     */
    public $Xsd_Ver = 1;
}
