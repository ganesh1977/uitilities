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

class DestinationController extends Controller
{
    /**
     * @Route("/destination", name="destination_index")
     */
    public function indexAction()
    {
     
       $output=array();
       $results = $this->getDoctrine()
                ->getRepository('AppBundle:Destination')
                ->findBy(array('status' => 1) );		
	
        foreach($results as $row){
               
            $output[] = array(
             'id'    => $row->getid(),   
             'Airport_code'  => $row->getairportcode(),
             'Destination_code'  => $row->getDestinationcode(),
             'Resort'   => $row->getResort(),
             'status'   => $row->getStatus(),
             'Autogen_val' =>$row->getAutogenval(),
			 'Destination_cost' =>$row->getDestinationCost()
            );
        }
        
        header("Content-Type: application/json");
        $a= json_encode($output);

 
        return $this->render('/destination/index.html.twig', array(
                   'output' => $a
               ));
    }
     /**
     * @Route("/destination/fetchdata", name="fetchdatas")
     */
    public function fetchdataAction(Request $request){
        
        $todo = new \AppBundle\Entity\Destination();
        $method = $_SERVER['REQUEST_METHOD'];
        
        
        $method = $_SERVER['REQUEST_METHOD'];

        if($method == 'GET' && ($_GET['Resort'] && $_GET['AutogenVal'] && $_GET['AirportCode'] && $_GET["DestinationCode"] ))
        {
            $data = array(
             ':airportcode'   => "'"."%" . $_GET['AirportCode'] . "%"."'",
             ':autogenval'   => "'"."%" . $_GET['AutogenVal'] . "%"."'",
             ':resort'   => "'"."%" . $_GET['Resort'] . "%"."'",
             ':destinationcode'   => "'"."%" . $_GET['DestinationCode'] . "%"."'"
             
            );
            $query = "SELECT * FROM cost WHERE autogenval LIKE :autogenval AND resort LIKE :resort AND airportcode LIKE :airportcode AND destinationcode LIKE :destinationcode  ORDER BY id DESC";
            //$results = $this->getDoctrine()
            //    ->getRepository('AppBundle:Costs')
             //   ->findBy(array('status' => 1,"servicecategory LIKE :servicecategory","percentage LIKE :percentage") );
            //$query = "SELECT * FROM sample_data WHERE first_name LIKE :first_name AND last_name LIKE :last_name AND age LIKE :age AND gender LIKE :gender ORDER BY id DESC";
            $doctrine = $this->getDoctrine();
            $em = $doctrine->getManager();            
            $user_stmt = $doctrine->getEntityManager() 
                                  ->getConnection()
                                    ->prepare($query);
            $user_stmt->execute($data);
            foreach($results as $row)
            {
                $output[] = array(
                'id'    => $row['id'],   
                'AirportCode'  => $row['airportcode'],
                'Resort'   => $row['resort'],
                'AutogenVal'   => $row['autogenval'],
                'DestinationCode'   => $row['destinationcode'],
                   'status'   => $row['status']

                );
            }
            header("Content-Type: application/json");
            echo json_encode($output);
        }
        $results = $this->getDoctrine()
                ->getRepository('AppBundle:Destination')
                ->findBy(array('status' => 1) );
        
         foreach($results as $row){
               
             $output[] = array(
             'id'    => $row->getid(),   
             'AirportCode'  => $row->getairportcode(),
              'DestinationCode'  => $row->getdestinationcode(),
             'Resort'   => $row->getresort(),
                'status'   => $row->getstatus(),
                'AutogenVal' =>$row->getautogenval()
             
            );
        }
        header("Content-Type: application/json");
        $a= json_encode($output);
        
        return $this->render('/destination/index.html.twig', array(
                   'output' => $a
               ));
    }
    /**
     * @Route("/destination/fetch", name="destination_fetch")
     */
    public function fetchAction(Request $request){
        
        
        $todo = new \AppBundle\Entity\Destination();
        $method = $_SERVER['REQUEST_METHOD'];
        $output=array();

        if($method == "POST"){                           
		
			$todo->setDestinationcode($_POST['Destination_code']);
            $todo->setResort($_POST['Resort']);
            $todo->setAirportcode($_POST['Airport_code']);
            $todo->setAutogenval($_POST['Autogen_val']);
			$todo->setDestinationCost($_POST['Destination_cost']);
             
            $todo->setstatus(1);
                 
			//print_r($todo); exit;
            $em = $this->getDoctrine()->getManager();
            $em->persist($todo);
            $em->flush();
            
            $this->addFlash('notice', 'Destination is Added');
        }   
        
        if($method == 'PUT'){       
            $method = $_SERVER['REQUEST_METHOD'];           
            parse_str(file_get_contents("php://input"), $_PUT);
 
            $cost = $this->getDoctrine()
                    ->getRepository('AppBundle:Destination')
                    ->find($_PUT['id']);
            if (empty($cost)) {
                $this->addFlash('error', 'Destination not found');
            }       
            
             $cost->setDestinationcode($_PUT['Destination_code']);
			 $cost->setDestinationcost($_PUT['Destination_cost']);
             $cost->setResort($_PUT['Resort']);
             $cost->setAirportcode($_PUT['Airport_code']);
             $cost->setAutogenval($_PUT['Autogen_val']);
             $em = $this->getDoctrine()->getManager();
             $em->persist($cost);
             $em->flush();
        }
     
        if($method == "DELETE"){            
            $method = $_SERVER['REQUEST_METHOD'];       
            parse_str(file_get_contents("php://input"), $_DELETE);            
            $cost = $this->getDoctrine()
                    ->getRepository('AppBundle:Destination')
                    ->find($_DELETE['id']);
            if (empty($cost)) {
                $this->addFlash('error', 'Destination not found');
                return $this->redirectToRoute('list');
            }
            $cost->setStatus(0);
            $em = $this->getDoctrine()->getManager();
            $em->persist($cost);
            $em->flush();
            $this->addFlash('notice', 'Destination is In-active');
        }
       
       $results = $this->getDoctrine()
                ->getRepository('AppBundle:Destination')
                ->findBy(array('status' => 1) );
        
         foreach($results as $row){               
              $output[] = array(
             'id'    => $row->getid(),   
             'Airport_code'  => $row->getairportcode(),
              'Destination_code'  => $row->getdestinationcode(),
             'Resort'   => $row->getresort(),
                'status'   => $row->getstatus(),
                'Autogen_val' =>$row->getautogenval(),
				'Destination_cost' =>$row->getDestinationCost()
             
            );
        }
        header("Content-Type: application/json");
        $a= json_encode($output);
        
        return $this->render('destination/index.html.twig', array(
                   'output' => $a
               ));
   
    }
}
   

   

   


