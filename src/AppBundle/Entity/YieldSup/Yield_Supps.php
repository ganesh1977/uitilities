<?php

namespace AppBundle\Entity\YieldSup;

use JMS\Serializer\Annotation\XmlList;
use JMS\Serializer\Annotation\Type;

class Yield_Supps {
    
    /**
     * @Type("array<AppBundle\Entity\YieldSup\Yield_Supp>")
     * @XmlList(inline = true, entry = "Yield_Supp")
     */
    public $Yield_Supp = [];
}
