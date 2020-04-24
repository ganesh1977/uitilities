<?php

namespace AppBundle\Entity;

use JMS\Serializer\Annotation\XmlRoot;
use JMS\Serializer\Annotation\XmlNamespace;
use JMS\Serializer\Annotation\XmlElement;
use JMS\Serializer\Annotation\XmlList;
use JMS\Serializer\Annotation\Type;

/*
        <com:Request>
            <com:Control>
               <com:Xsd_Ver>1</com:Xsd_Ver>
            </com:Control>
            <com:Batch>0</com:Batch>
            <com:Yield_Supps>
               <com:Yield_Supp>
                  <com:Pkg_ID>KEY_DATA</com:Pkg_ID>
                  <com:Adu_Sup>INTEGER</com:Adu_Sup>
                  <com:Adu_Base_Prc>0</com:Adu_Base_Prc>
                  <com:Chd_Sup_1>INTEGER</com:Chd_Sup_1>
                  <com:Chd_Sup_2>INTEGER</com:Chd_Sup_2>
                  <com:Priority>0</com:Priority>
                  <com:Yield_Grp>0</com:Yield_Grp>
                  <com:Hide_Sale>N</com:Hide_Sale>
                  <com:User_Cd>UTILS</com:User_Cd>
                  <com:FC_Mth>?</com:FC_Mth>
               </com:Yield_Supp>
            </com:Yield_Supps>
         </com:Request>
*/

/**
 * @XmlRoot("Request")
 * @XmlNamespace(uri="AtComRes/Common")
 */
class YieldSupRequest {

    /**
     * @Type("AppBundle\Entity\YieldSup\Control")
     * @XmlElement(cdata=false)
     */
    public $Control;

    /**
     * @Type("integer")
     * @XmlElement(cdata=false)
     */
    public $Batch = 0;

    /**
     * @Type("AppBundle\Entity\YieldSup\Yield_Supps")
     * @XmlElement(cdata=false)
     */
    public $Yield_Supps;

}
