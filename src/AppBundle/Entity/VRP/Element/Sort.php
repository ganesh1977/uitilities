<?php

namespace AppBundle\Entity\VRP\Element;

use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\XmlElement;

class Sort {

    /**
     * @Type("string")
     * @XmlElement(cdata=false, namespace="AtComRes/Common")
     */
    public $Sort_Dir = 'ascending';

    /**
     * @Type("string")
     * @XmlElement(cdata=false, namespace="AtComRes/Common")
     */
    public $Order = 'PRICE';

}