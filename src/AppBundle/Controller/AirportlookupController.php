<?php

namespace AppBundle\Controller;

use AppBundle\Service\Atcore;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\Airportlookup;

//use Primera\AtcomResBundle\Entity\WebService as WS;

class AirportlookupController extends Controller
{
    /**
     * @Route("/airportlookup_new", name="airport_lookup_index")
     */
    public function indexAction()
    {   
       $output=array();
       $results = $this->getDoctrine()
                ->getRepository('AppBundle:Airportlookup')
                ->findBy(array('status' => 1) );	
       
        foreach($results as $row){   
            $output[] = array(
                    'id'    => $row->getid(),
                    'airport_code'      => strtoupper($row->getAirportCode()),
                    'cost_pax'          => $row->getCostPrPax(),
                    'handling_fee'      => $row->getHandlingFeePrPax(),
                    'empty_leg_average' => $row->getEmptyLegAverage(),
                    'empty_leg_per'     => $row->getEmptyLegPer(),
                    'get_vat'           => $row->getVat()					
            );
        }	
        
        header("Content-Type: application/json");
        $a= json_encode($output);
        
        return $this->render('/airportlookup/index.html.twig', array('output' => $output));
    }  
    
    /**
     * @Route("/airportlookup/delete", name="deletea")
     */
    public function deletesAction(Request $request)
    {   
            $id = $_POST['id'];
            try{
                $arrival_id = $this->getDoctrine()
                              ->getRepository('AppBundle:Airportlookup')
                              ->find($id);            

                if (empty($arrival_id)) {
                    $this->addFlash('error', 'Arrival id not found');
                    return $this->redirectToRoute('airport_lookup_index');
                }
            }
            catch(Exception $e)
            {
                throw new Exception("Not Deleted the record");
            }
            $arrival_id->setStatus(0);
            $em = $this->getDoctrine()->getManager();
            $em->persist($arrival_id);
            $em->flush();
            $status = 1;            
            return new Response($status);
    }
    
    
     /**
     * @Route("/airportlookup/edit", name="edita")
     */
    public function editAction(Request $request)
    {   
            $id = $_POST['id'];            
            try{
                $arrival_id = $this->getDoctrine()
                          ->getRepository('AppBundle:Airportlookup')
                          ->find($id);            

                if (empty($arrival_id)) {
                    $this->addFlash('error', 'Cost not found');
                    return $this->redirectToRoute('airport_lookup_index');
                }
            }
            catch(Exception $e)
            {
                throw new Exception("Not Deleted the record");
            }            
                        
            $id         = $arrival_id->getId(); 
            $AirCode    = strtoupper($arrival_id->getAirportCode()); 
            $CostPrPax  = $arrival_id->getCostPrPax(); 
            $HandFeePax = $arrival_id->getHandlingFeePrPax(); 
            $emptyLAvg  = $arrival_id->getEmptyLegAverage();
            $emptyLper  = $arrival_id->getEmptyLegPer(); 
            $getVat = $arrival_id->getVat(); 
            
            $EditInfo = array("id"=>$id,"acode"=>$AirCode,"cpx"=>$CostPrPax,"HFP"=>$HandFeePax,"ELA"=>$emptyLAvg,"ELP"=>$emptyLper,"gvat"=>$getVat);
            
            return new JsonResponse([
            'success' => $EditInfo
        ]);
    }
    
    /**
     * @Route("/airportlookup/edit_submit", name="edit_submit")
     */
    public function editSubmitAction(Request $request)
    {   
            $id         = $_POST['id'];  
            $acode      = strtoupper($_POST['acode']);  
            $cprPax     = $_POST['cprPax']!=''?$_POST['cprPax']:'0';  
            $HFprPax    = $_POST['HFprPax']!=''?$_POST['HFprPax']:'0';  
            $ElAve      = $_POST['ElAve']!=''?$_POST['ElAve']:'0';  
            $Elp        = $_POST['Elp']!=''?$_POST['Elp']:'0';  
            $vat        = $_POST['vat']!=''?$_POST['vat']:'0';  
 
            $Airport = $this->getDoctrine()
                    ->getRepository('AppBundle:Airportlookup')
                    ->find($id);

            if (empty($Airport)) {
                $this->addFlash('error', 'Arrival is not found');
            }            
            
            $Airport->setAirportCode($acode);
            $Airport->setCostPrPax($cprPax);
            $Airport->setHandlingFeePrPax($HFprPax);            
            $Airport->setEmptyLegAverage($ElAve);
            $Airport->setEmptyLegPer($Elp);                    
            $Airport->setVat($vat);           
            
            $em = $this->getDoctrine()->getManager();
            $em->persist($Airport);
            $em->flush();
            
            $status = 1;            
            return new Response($status);
    }    
    
    /**
     * @Route("/airportlookup/airport_New_Rec", name="airport_add_new")
     */
    public function  CreateAirportAction()
    {   
        try{
            $Airport = new \AppBundle\Entity\Airportlookup();            
            $Airport->setAirportCode($_POST['acodes']);            
            $cprPax = ($_POST['cprPaxES'] !== '')?$_POST['cprPaxES']:'0';
            $Airport->setCostPrPax($cprPax);
            $HFprPax = ($_POST['HFprPax'] !== '')?$_POST['HFprPax']:'0';
            $Airport->setHandlingFeePrPax($HFprPax);              
            $ElAve = ($_POST['ElAve'] !== '')?$_POST['ElAve']:'0';
            $Airport->setEmptyLegAverage($ElAve);
            $Elp = ($_POST['Elp'] !== '')?$_POST['Elp']:'0';
            $Airport->setEmptyLegPer($Elp);                    
            $vat = ($_POST['vat'] !== '')?$_POST['vat']:'0';
            $Airport->setVat($vat);           
            $Airport->setStatus(1);            
   
            $em = $this->getDoctrine()->getManager();
            $em->persist($Airport);
            $em->flush();
            
            $status = 1;            
            return new Response($status);
        }
        catch(Exception $e)
        {
            throw new Exception("Records is not posted.");
        }
    }
    
    
    /**
     * @Route("/airportlookup/airport_New_Recr", name="airport_add_newR")
     */
    public function  CreateSAirportAction()
    {   
            $status = $_POST['acodes'];           
            return new Response($status);
        
    }
}