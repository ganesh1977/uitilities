<?php

namespace AppBundle\Entity\VRP\Element;

use JMS\Serializer\Annotation\XmlAttribute;
use JMS\Serializer\Annotation\XmlValue;

class Trk {

    /**
     * @XmlAttribute
     */
    public $From = 'atcomweb';

    /**
     * @XmlAttribute
     */
    public $To = 'atcomres';

    /**
     * @XmlValue(cdata=false)
     */
    public $Trk;

    public function __construct($text = null)
    {
        $this->Trk = $text;
    }
}