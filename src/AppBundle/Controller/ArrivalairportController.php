<?php

namespace AppBundle\Controller;

use AppBundle\Service\Atcore;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\JsonResponse;

use Symfony\Component\Validator\Constraints\DateTime;



//use Primera\AtcomResBundle\Entity\WebService as WS;

class ArrivalairportController extends Controller
{
    /**
     * @Route("/arrival", name="arrival_airport_index")
     */
    public function indexAction()
    {   
        $output=array();
        $results = $this->getDoctrine()
                ->getRepository('AppBundle:ArrivalAirport')
                ->findBy(array('status' => 1) );  
        //print_r($results); exit;
        foreach($results as $row){               
            $output[] = array(
                'Id'    => $row->getid(),   
                'AirportCode'  => strtoupper($row->getarrivalAirport()),
                'ArrivalAverage'  => $row->getArrivalAverage(),
                'PRPAX'   => $row->getPrPax(),
                'TweakLef'   => $row->getTweakLef(),
                'TweakAverage'   => $row->getTweakAverage(),                                
                'Status'   => $row->getStatus()
            );
        }        
      
        header("Content-Type: application/json");
        $a= json_encode($output);
        //echo $a; exit;
        return $this->render('/airport/index.html.twig', array(
                   'output' => $a));
    }
     /**
     * @Route("/arrival/fetchdata", name="fetchdatas")
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
     * @Route("/arrivalairport/fetch", name="arrival_fetch")
     */
    public function fetchAction(Request $request){     
        
        $todo = new \AppBundle\Entity\ArrivalAirport();                       
        $method = $_SERVER['REQUEST_METHOD'];
        $output=array();

        if($method == "POST"){                   
            $todo->setarrivalAirport(strtoupper($_POST['AirportCode']));
            $todo->setarrivalAverage($_POST['ArrivalAverage']);
            $todo->setprPax($_POST['PRPAX']);
            $todo->settweakLef($_POST['TweakLef']);
            $todo->settweakAverage($_POST['TweakAverage']);
            $todo->setstatus(1);                       
            
            $em = $this->getDoctrine()->getManager();
            $em->persist($todo);
            $em->flush();
            
            $this->addFlash('notice', 'Destination is Added');
        }   
        
        if($method == 'PUT'){        
            $method = $_SERVER['REQUEST_METHOD'];           
            
            parse_str(file_get_contents("php://input"), $_PUT);
 
            $arr_ave_update = $this->getDoctrine()
                    ->getRepository('AppBundle:ArrivalAirport')
                    ->find($_PUT['Id']);

            if (empty($cost)) {
                $this->addFlash('error', 'Destination not found');
            }
            
            $arr_ave_update->setarrivalAirport(strtoupper($_PUT['AirportCode']));
            $arr_ave_update->setarrivalAverage($_PUT['ArrivalAverage']);
            $arr_ave_update->setprPax($_PUT['PRPAX']);
            $arr_ave_update->settweakLef($_PUT['TweakLef']);
            $arr_ave_update->settweakAverage($_PUT['TweakAverage']);
            $arr_ave_update->setstatus(1); 
            
            $em = $this->getDoctrine()->getManager();
            $em->persist($arr_ave_update);
            $em->flush();
        }
        
        if($method == "DELETE"){
            $method = $_SERVER['REQUEST_METHOD'];       
            
            parse_str(file_get_contents("php://input"), $_DELETE);            
            
            $delete_arr_ave = $this->getDoctrine()
                    ->getRepository('AppBundle:ArrivalAirport')
                    ->find($_DELETE['Id']);
            
            if (empty($delete_arr_ave)) {
                $this->addFlash('error', 'Arrival Average not found');
                return $this->redirectToRoute('list');
            }
            
            $delete_arr_ave->setStatus(0);
            $em = $this->getDoctrine()->getManager();
            $em->persist($delete_arr_ave);
            $em->flush();
            $this->addFlash('notice', 'Arrival Average is In-active');
        }
       
        $results = $this->getDoctrine()
                ->getRepository('AppBundle:ArrivalAirport')
                ->findBy(array('status' => 1) );
        
        foreach($results as $row)
        {               
              $output[] = array(
                    'id'    => $row->getid(),   
                    'arrivalAirport'=>$row->getarrivalAirport(),
                    'arrivalAverage'=>$row->getarrivalAverage(),
                    'prPax'=>$row->getprPax(),
                    'tweakLef'=>$row->gettweakLef(),
                    'tweakAverage'=>$row->gettweakAverage());
        }
        header("Content-Type: application/json");
        $a= json_encode($output);
        
        return $this->render('airport/index.html.twig', array(
                   'output' => $a
               ));
   
    }
}
   

   

   


