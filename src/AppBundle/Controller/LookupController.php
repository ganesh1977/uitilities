<?php

namespace AppBundle\Controller;

use AppBundle\Service\Atcore;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\Supplier;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

//use Primera\AtcomResBundle\Entity\WebService as WS;

class LookupController extends Controller
{
    /**
     * @Route("/lookup", name="index")
     */
    public function indexAction()
    {
     
        $output=array();
       $results = $this->getDoctrine()
                ->getRepository('AppBundle:Cost')
                ->findBy(array('status' => 1) ); 
       
        foreach($results as $row){               
            $percentage = $row->getpercentage()!=''?$row->getpercentage():'0';
            $output[] = array(
                        'id'                =>  $row->getid(),   
                        'Service_Code'      =>  strtoupper($row->getServicecode()),
                        'Service_Category'  =>  $row->getservicecategory(),
                        'Percentage'        =>  $percentage,
                        'status'            =>  $row->getstatus());
        }
        
        header("Content-Type: application/json");        
        return $this->render('/lookup/index.html.twig', array('output' => $output));
    }    
    
    /**
     * @Route("/service_category_add", name="category_add")
     */    
    public function AddServiceCategoryAction(Request $request)
    {        
        $todo = new \AppBundle\Entity\Cost();
        try{
            $todo->setServicecode(strtoupper($_POST['Service_Code']));
            $todo->setServicecategory($_POST['Service_Category']);
            $todo->setPercentage($_POST['Percentage']);
            $todo->setStatus(1);

            $em = $this->getDoctrine()->getManager();
            $em->persist($todo);
            $em->flush();
            
            $status = 1;            
            return new Response($status);      
        }
        catch(Exception $e)
        {
            throw new Exception("Records is not inserted.");
        }

          
    }
    
    
    /**
     * @Route("/service_category_del", name="category_del")
     */    
    public function DelServiceCategoryAction()
    {       
        try{
            $cost = $this->getDoctrine()
                    ->getRepository('AppBundle:Cost')
                    ->find($_POST['id']);

            if (empty($cost)) {
                $this->addFlash('error', 'Cost not found');
                return $this->redirectToRoute('list');
            }
            
            $cost->setStatus(0);
            $em = $this->getDoctrine()->getManager();
            $em->persist($cost);
            $em->flush();
            
            $status = 1;            
            return new Response($status);
        }
        catch(Exception $e)
        {
            throw new Exception("Records is not inserted.");
        }
            
            
              
    }
    
    /**
     * @Route("/Category/Edit", name="category_edit")
    */    
    public function cateogoryEditAction(Request $request)
    {
        $id = $_POST['id'];
            
        try{
            $arrival_id = $this->getDoctrine()
                      ->getRepository('AppBundle:Cost')
                      ->find($id);            

            if (empty($arrival_id)) {
                $this->addFlash('error', 'Cost not found');
                return $this->redirectToRoute('index');
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Not Find Out on the server");
        }            
                        
        $id         = $arrival_id->getId(); 
        $Service_Code    = strtoupper($arrival_id->getservicecode()); 
        $Service_Category  = $arrival_id->getservicecategory(); 
        $Service_Percentage = $arrival_id->getpercentage();         
            
        $EditInfo = array("id"=>$id,"scode"=>$Service_Code,"scat"=>$Service_Category,"sper"=>$Service_Percentage);      

        return new JsonResponse(['success' => $EditInfo]);
    }
    
    
     /**
     * @Route("/Category/Edit_Details", name="category_edit_submit")
    */    
    public function cateogoryEditSubmitAction(Request $request)
    {
        $todo = new \AppBundle\Entity\Cost();        
 
        $cost = $this->getDoctrine()
                ->getRepository('AppBundle:Cost')
                ->find($_POST['id']);         
        if (empty($cost)) {
            $this->addFlash('error', 'Cost not found');
        }            
        $cost->setServicecode(strtoupper($_POST['Service_Code']));
        $cost->setServicecategory($_POST['Service_Category']);
        $cost->setPercentage($_POST['Percentage']);            
        
        $em = $this->getDoctrine()->getManager();
        $em->persist($cost);
        $em->flush();
        
        
        $status = 1;            
        return new Response($status);
    }    
    
    /**
     * @Route("/lookup/fetch", name="fetchs")
     */
    /*public function fetchAction(Request $request){
        
        $todo = new \AppBundle\Entity\Cost();
        $method = $_SERVER['REQUEST_METHOD'];
        $output=array();

        if($method == "POST"){       
       
            $todo->setServicecode($_POST['Service_Code']);
            $todo->setServicecategory($_POST['Service_Category']);
            $todo->setPercentage($_POST['Percentage']);
            $todo->setStatus(1);           
            
            $em = $this->getDoctrine()->getManager();
            $em->persist($todo);
            $em->flush();
            
            $this->addFlash('notice', 'Cost is Added');
        }   
        
        if($method == 'PUT'){ 
           
            $method = $_SERVER['REQUEST_METHOD']; 
            parse_str(file_get_contents("php://input"), $_PUT);
 
             $cost = $this->getDoctrine()
                    ->getRepository('AppBundle:Cost')
                    ->find($_PUT['id']);

            if (empty($cost)) {
                $this->addFlash('error', 'Cost not found');
            }            
            $cost->setServicecode($_PUT['Service_Code']);
            $cost->setServicecategory($_PUT['Service_Category']);
            $cost->setPercentage($_PUT['Percentage']);            
            
            $em = $this->getDoctrine()->getManager();
            $em->persist($cost);
            $em->flush();
        }
     
        if($method == "DELETE"){            
            $method = $_SERVER['REQUEST_METHOD'];       
            parse_str(file_get_contents("php://input"), $_DELETE);            
            $cost = $this->getDoctrine()
                    ->getRepository('AppBundle:Cost')
                    ->find($_DELETE['id']);

            if (empty($cost)) {
                $this->addFlash('error', 'Cost not found');
                return $this->redirectToRoute('list');
            }
            
            $cost->setStatus(0);
            $em = $this->getDoctrine()->getManager();
            $em->persist($cost);
            $em->flush();
            $this->addFlash('notice', 'Cost is In-active');
        }
       
       $results = $this->getDoctrine()
                ->getRepository('AppBundle:Cost')
                ->findBy(array('status' => 1) );
        
         foreach($results as $row){               
            $output[] = array(
             'id'    => $row->getid(),   
             'Service_Code'  => $row->getservicecode(),
             'Service_Category'  => $row->getservicecategory(),
             'Percentage'   => $row->getpercentage(),             
            );
        }
        header("Content-Type: application/json");
        $a= json_encode($output);        
        return $this->render('lookup/index.html.twig', array('output' => $a));   
    }*/
}