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
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

use Primera\AtcomResBundle\Entity\WebService as WS;

class ReportController extends Controller
{
    private $data;
    /**
     * @Route("/report/list", name="report_list") 
     */
    public function listAction(Request $request)
    {                   
        $gcDtForamt = 'MM/DD/YYYY';
        
        $this->data     =   [];
        $summary        =   [];
        $search_name    =   array();

        if ( $request->query->has('dep_from') || $request->query->has('dep_to') || 
             $request->query->has('book_from') || $request->query->has('book_to') || 
             $request->query->has('prom_cd')|| $request->query->has('accom')||($request->query->has('arr_cd')) || $request->query->has('dep_cd')) {

            /**
             * @var $atcore Atcore
             */
            $atcore = $this->get('app.atcore');            
            
            $view_browser=$request->query->get('view_browser', null);
            
            $gc=$request->query->get('gc', null);  
            
            $arr_cd=$request->query->get('arr_cd', null);
            $dep_cd=$request->query->get('dep_cd', null);            
            
            $gcinputar  =   array("Hotel","Revenue","Flight","PAX","Agent","R16","R9","Flight_Data","R3-20","R17.2","GC");
            
            $gcarray    = $gcinputar ;
            
            $promCd         =   $request->query->get('prom_cd', null);
            $brand_title    = $this->brandTitle($promCd);
            
            $acco           =   $request->query->get('accom', null);
            
            if( ($acco!='') && ($acco!='ALL') )
            {
                $RoomCodeDiv = explode(",",$acco);
                $AccCode= "'".implode("','",$RoomCodeDiv)."'"; 
                $AccCodes = 1;
            } else {    
                $AccCode   = 'NULL';   $AccCodes   = 'NULL';                        
            } 
     
            $depFrom    =   $request->query->get('dep_from', null);
            $depFromDates = $depFrom ? new \DateTime($depFrom) : null;
            
            $depTo      = $request->query->get('dep_to', null);       
            $depToDate  = new \DateTime($depTo);            
            
            $depToDate->setTime(23, 59, 59);
 
            $depToDates     = $depTo ?  $depToDate : null;
            $bookFrom = $request->query->get('book_from', null);
            $bookFromDates  = $bookFrom ? new \DateTime($bookFrom) : null;
            
            $bookTo   = $request->query->get('book_to', null); 
            $bookToDate     = new \DateTime($bookTo);            
            
            $bookToDates    = $bookTo ?  $bookToDate : null;
                
            $datesbookfrom  =   (array)$bookFromDates;
            $datesbookto    =   (array)$bookToDates;
            
            if($bookFrom!='') {  
               $datebooksfrom  =   "'".date($datesbookfrom['date'])."'"; 
               $fromdate   =   "'".date('d-M-Y',strtotime($datesbookfrom['date']))."'";
            } 
            else { $fromdate = ''; }
            
            if($bookTo!='') { 
                        $datebooksto    =   "'".date($datesbookto['date'])."'"; 
                        $to_date    =   date('Y-m-d H:i:s', strtotime($datesbookto['date'] . ' +1 day')); 
                        $todate     =   "'".date('d-M-Y',strtotime($to_date))."'";
               }
               else { $todate =  ''; }                        
                        
            $datesdepfrom   =   (array)$depFromDates;
            $datesdepto     =   (array)$depToDates;            
           
            $DepDatesFrToList   = date("mdY",strtotime($datesdepfrom['date']))."-".date("mdY",strtotime($datesdepto['date']));
            $datedepsfrom       =   "'".date($datesdepfrom['date'])."'";
            $datedepsto         =   "'".date($datesdepto['date'])."'";
            
            $to_depdate     =   date('Y-m-d H:i:s', strtotime($datesdepto['date'] . ' +0 day'));
            $fromdatedep    =   "'".date('d-M-Y',strtotime($datesdepfrom['date']))."'";
            $todatedep      =   "'".date('d-M-Y',strtotime($to_depdate))."'";            
            
            $excels          =   $request->query->get('excel', null);
            $excel = ($excels == 'Download Excel')?$excels:'';
            
            $searchR9           =   '';
            $searchR8           =   '';
            $searchR16          =   '';
            $search             =   '';
            $searchR17          =   '';
            $searchAgent        =   '';
            $searchFlightData   =   '';
            $searchFlight       =   '';
            $searchHotel        =   '';
            $searchRevenue      =   '';
            $searchPax          =   '';
            
            $Sheets_Arr = array();
            $all_pivot_data = array();
            $Sheets_Arr_new = array();
            foreach($gcarray as $v)
            {
                switch($v){                                        
                    case "R3-20":                                                
                        $search                 =   $this->generate_R3_20($request,$dep_cd,$arr_cd,$promCd,$fromdate,$todate,$fromdatedep,$todatedep,$acco,$excel,$gcDtForamt,$AccCode,$AccCodes);
                        $Sheets_Arr['R3-20']    =  $search;
                        break;
                    case "R17.2":
                        $searchR17          =   $this->generate_R17_2($request,$dep_cd,$arr_cd,$promCd,$fromdate,$todate,$fromdatedep,$todatedep,$acco,$excel,$gcDtForamt,$AccCode,$AccCodes);
                        $Sheets_Arr['R17.2']  =   $searchR17;
                        break;
                    case "Agent":
                        $searchAgent        =   $this->generate_Agent($request,$dep_cd,$arr_cd,$promCd,$fromdate,$todate,$fromdatedep,$todatedep,$acco,$excel,$gcDtForamt,$AccCode,$AccCodes);
                        $Sheets_Arr['Agent']= $searchAgent;
                        $all_pivot_data['AGENT'] = $searchAgent;
                        break;
                    case "Flight_Data":                        
                        $searchFlightData           =   $this->generate_flight_data($request,$dep_cd,$arr_cd,$promCd,$fromdate,$todate,$fromdatedep,$todatedep,$acco,$excel,$gcDtForamt,$AccCode,$AccCodes);                      
                        $Sheets_Arr['Flight_Data']  =   $searchFlightData;                        
                        break;
                    case "Flight":
                        $searchFlight               =   $this->generate_Flight($request,$dep_cd,$arr_cd,$promCd,$fromdate,$todate,$fromdatedep,$todatedep,$acco,$excel,$gcDtForamt,$AccCode,$AccCodes);
                        $Sheets_Arr['Flight']       =   $searchFlight;
                        $all_pivot_data['FLIGHT']    =   $searchFlight;
                        break;
                    case "R16":
                        $searchR16          =   $this->generate_R16($request,$dep_cd,$arr_cd,$promCd,$fromdate,$todate,$fromdatedep,$todatedep,$acco,$excel,$gcDtForamt,$AccCode,$AccCodes);                        
                        $Sheets_Arr['R16']  =   $searchR16;
                        break;
                    case "Hotel":
                        $searchHotel        =   $this->generate_Hotel($request,$dep_cd,$arr_cd,$promCd,$fromdate,$todate,$fromdatedep,$todatedep,$acco,$excel,$gcDtForamt,$AccCode,$AccCodes);
                        $Sheets_Arr['Hotel']=   $searchHotel;
                        $all_pivot_data['HOTEL'] = $searchHotel;
                        break;
                    case "R9":
                        $searchR9           =   $this->generate_R9($request,$dep_cd,$arr_cd,$promCd,$fromdate,$todate,$fromdatedep,$todatedep,$acco,$excel,$gcDtForamt,$AccCode,$AccCodes);                        
                        $Sheets_Arr['R9']   =   $searchR9;
                        break;
                    case "Revenue":
                        $searchRevenue          =   $this->generate_Revenue($request,$dep_cd,$arr_cd,$promCd,$fromdate,$todate,$fromdatedep,$todatedep,$acco,$excel,$gcDtForamt);
                        $Sheets_Arr['Revenue']  =   $searchRevenue; 
                        $all_pivot_data['REVENUE'] = $searchRevenue;
                        break;
                    case "PAX":
                        $searchPax          =   $this->generate_PAX($request,$dep_cd,$arr_cd,$promCd,$fromdate,$todate,$fromdatedep,$todatedep,$acco,$excel,$gcDtForamt,$AccCode,$AccCodes);                                                                        
                        $Sheets_Arr['PAX']  =   $searchPax;
                        $all_pivot_data['PAX'] = $searchPax;
                        break;
                    case "GC":
                        $searchGc           =   $this->generate_GC($depFromDates,$depToDate,$all_pivot_data);                                                                                                
                        $Sheets_Arr['GC']  =   $searchGc;
                        $Sheets_Arr_new['GC']  =   $searchGc;
                        break;
                    default:
                        echo "No Report.";
                        break;
                }
            }  
            
            if(!isset($view_browser)) {      
                if($request->query->has('excel')) {                     
                    if($gc!='')
                    {
                        array_push($gc,"GC");
                        $push = array();
                        foreach($gc as $key=>$val)
                        {
                                $char = $val;
                                $push[$char]=$char;
                        }
                        
                        $gcarray = array_intersect_key($Sheets_Arr,$push);
                        $this->move_to_top($gcarray,"GC");
                    }
                    else
                    {
                        $gcarray = $Sheets_Arr_new;
                    }
                    $this->move_to_top($gcarray,"GC");
                    
                    $arr = array();                    
                    return $this->createExcel_new($request, $gcarray,$DepDatesFrToList,$brand_title);
                }                
            }
            else{                      
                if($gc!='')
                {
                    array_push($gc,"GC");
                    $push = array();
                    foreach($gc as $key=>$val)
                    {
                            $char = $val;
                            $push[$char]=$char;
                    }

                    $gcarray = array_intersect_key($Sheets_Arr,$push);
                    $this->move_to_top($gcarray,"GC");
                }
                else
                {
                    $gcarray = $Sheets_Arr_new;
                }
                $this->move_to_top($gcarray,"GC");  
            
                $newGcArray = $gcarray['GC'];            
                 
                $revnue1    = array();
                $ar         = array();
                $hotel1     = array();
                $hotel1_ar  = array();
                $flight     = array();
                $flight_ar  = array();
                
                $agent     = array();
                $agent_ar  = array();
                
                $pax     = array();
                $pax_ar  = array();
                
                $flights     = array();
                $flights_ar  = array();
                
                $gcp    = array();
                $cur_date = $newGcArray['1']['0']['0'];
                foreach($newGcArray['1']['0'] as $keyy=>$values) 
                {       
                    $gc_arr_multi_sub           = array();
                    $hotel_gc_arr_multi_sub     = array();
                    $flight_gc_arr_multi_sub    = array();
                    $agent_gc_arr_multi_sub     = array();
                    $pax_gc_arr_multi_sub       = array();
                        
                    foreach($newGcArray['0']['REVENUE'] as $key1=>$value)
                    {	
                        if(isset($newGcArray['1']['1']))
                        {
                            foreach($newGcArray['1']['1'] as $key_air=>$value_air)
                            {
                                if( ($value['Arrival Airport Code']==$value_air) && ($value['Travel Start Date']==$values) )
                                {
                                    $gc_arr_multi_sub[$value_air] = array($value['Travel Start Date'],
                                                                          $value['Arrival Airport Code'],
                                                                          round($value['Sum of revenue']),
                                                                          round($value['Sum of other cost']),
                                                                          round($value['Sum of travel vat']));
                                }
                            }
                        }
                    }
                    
                    //Get the HOTEL Data
                    foreach($newGcArray['0']['HOTEL'] as $key1=>$hot_value)
                    {	
                        if(isset($newGcArray['1']['1']))
                        {
                            foreach($newGcArray['1']['1'] as $hot_key_air=>$hot_air)
                            {                                        
                                if( ($hot_value['DESTNATION_CODE']==$hot_air) && ($hot_value['ARRIVAL_DATE']==$values) )
                                {                                            
                                    $hotel_gc_arr_multi_sub[$hot_air] = array(
                                                                    $hot_value['ARRIVAL_DATE'],
                                                                    $hot_value['DESTNATION_CODE'],
                                                                    $hot_value['COST_LOCAL_CURRENCY']);                                                                            
                                }
                            }                                    
                        }                                
                    }
                    
                    
                    foreach($newGcArray['0']['FLIGHT'] as $key1=>$flight_value)
                    {	
                        if(isset($newGcArray['1']['1']))
                        {
                            foreach($newGcArray['1']['1'] as $flight_key_air=>$flight_air)
                            {   
                                if( ($flight_value['Airpot Codes']==$flight_air) && ($flight_value['Atcom booking date']==$values) )
                                {                                            
                                    $flight_gc_arr_multi_sub[$flight_air] = array($flight_value['Atcom booking date'],																			
                                                                                  $flight_value['Airpot Codes'],
                                                                                  $flight_value['Source Currency Amount']);                                                                            
                                }
                            }                                    
                        }                                
                    } 
                    
                    //Get the pax data
                    foreach($newGcArray['0']['PAX'] as $key1=>$pax_value)
                    {	
                        if(isset($newGcArray['1']['1']))
                        {
                            foreach($newGcArray['1']['1'] as $pax_key_air=>$pax_air)
                            {   
                                if( ($pax_value['Arrival Airport']==$pax_air) && ($pax_value['Departure Date']==$values) )
                                {                                            
                                    $pax_gc_arr_multi_sub[$pax_air] = array(
                                                                    $pax_value['Departure Date'],
                                                                    $pax_value['Arrival Airport'],
                                                                    $pax_value['Handlings fee/Complaints'],
                                                                    $pax_value['Out Sold Seats'],
                                                                    $pax_value['Out Alt Seats'],
                                                                    $pax_value['Empty_legs']);                                                                            
                                }
                            }

                        }                                
                    }      
                    
                    //Agent
                    foreach($newGcArray['0']['AGENT'] as $key1=>$agent_value)
                    {	
                        if(isset($newGcArray['1']['1']))
                        {
                            foreach($newGcArray['1']['1'] as $pax_key_air=>$agent_air)
                            {                                             
                                if( ($agent_value['Arrival AIR']==$agent_air) && ($agent_value['Departure DT']==$values) )
                                {                                            
                                    $agent_gc_arr_multi_sub[$agent_air] = array($agent_value['Departure DT'],																			
                                                                                $agent_value['Arrival AIR'],
                                                                                $agent_value['Profit']);                                                                            
                                }
                            }

                        }                                
                    }
                    array_shift($newGcArray['1']['0']);                    
                    array_push($revnue1,$gc_arr_multi_sub);
                    array_push($hotel1,$hotel_gc_arr_multi_sub);
                    array_push($flight,$flight_gc_arr_multi_sub);
                    array_push($agent,$agent_gc_arr_multi_sub);
                    array_push($pax,$pax_gc_arr_multi_sub);
                }                       
                
                //echo "<pre>";
                //print_r($pax); exit;
                
                $ar = array();
                $sum = 0;
                $sum1 = 0;
                foreach($revnue1 as $key=>$value)
                {
                    foreach($value as $kkey=>$vvalue)
                    {
                        if(array_key_exists($kkey,$ar))
                        { 
                            $ar[$kkey][2] = round($ar[$kkey]['2']+$vvalue[2]);
                            $sum = $sum+$ar[$kkey][2];
                            $ar[$kkey][3] = round($ar[$kkey]['3']+$vvalue[3]);                          
                            $sum1 = $sum1+$ar[$kkey][3];
                        }
                        else 
                        {                         
                            $ar[$kkey] = $vvalue;
                        }
                    }
                }
                
                //Hotel Summarized amount
                foreach($hotel1 as $key=>$value)
                {
                    foreach($value as $kkey=>$vvalue)
                    {
                        if(array_key_exists($kkey,$hotel1_ar))
                        { 
                            $hotel1_ar[$kkey][2] = $hotel1_ar[$kkey]['2']+$vvalue[2];                                                      
                        }
                        else
                        {                         
                            $hotel1_ar[$kkey] = $vvalue;
                        }
                    }
                }

                foreach($flight as $key=>$value)
                {
                    foreach($value as $kkey=>$vvalue)
                    {
                        if(array_key_exists($kkey,$flight_ar))
                        { 
                            $flight_ar[$kkey][2] = $flight_ar[$kkey]['2']+$vvalue[2];                              
                        }
                        else
                        {                         
                            $flight_ar[$kkey] = $vvalue;
                        }
                    }
                }
                $flights_ar = array();
                
                //GC
                foreach($newGcArray['1']['1'] as $key=>$val)
                {
                    $one    = isset($ar[$val]['2'])?$ar[$val]['2'] : '0';
                    $two    = isset($ar[$val]['3'])?$ar[$val]['3']:'0';
                    $thr    = isset($flight_ar[$val][2])?$flight_ar[$val][2]:'0';
                    $four   = isset($hotel1_ar[$val]['2'])?$hotel1_ar[$val]['2']:'0';
                    
                    $cal_new = $one-$two-$thr-$four;
                   
                    $flights_ar[$val] = array("0"=>$vvalue[0],"1"=>$val,'2'=>$cal_new);
                }
                
                //GC%
                foreach($flights_ar as $key=>$value)
                {
                    if($ar[$key]['2']!=0)
                    {
                            $val = round($value[2]/$ar[$key]['2']);
                            $gcp[$key] = array('0'=>$value[0],'1'=>$value[1],"2"=>$val);
                    }
                }                                

                //Agent
                foreach($agent as $key=>$value)
                {
                    foreach($value as $kkey=>$vvalue)
                    {
                        if(array_key_exists($kkey,$agent_ar))
                        { 
                            $agent_ar[$kkey][2] = $agent_ar[$kkey]['2']+$vvalue[2];                                                      
                        }
                        else
                        {                         
                            $agent_ar[$kkey] = $vvalue;
                        }
                    }
                }
                
                $sum1 = '0';
                $sum2 = '0';
                //echo "<pre>";
                //print_r($pax);
                //exit;
                foreach($pax as $key=>$value)
                {
                    foreach($value as $kkey=>$vvalue)
                    {
                        if(array_key_exists($kkey,$pax_ar))
                        {   
                            $pax_ar[$kkey][2] = $pax_ar[$kkey][2]+$vvalue[2];
                            $pax_ar[$kkey][3] = $pax_ar[$kkey][3]+$vvalue[3];                                          
                            
                            
                            $cal = $vvalue[2];//Allotment%
                            if($cal!=0)
                            {
                                $pax_ar[$kkey][4] = $pax_ar[$kkey][4]+round(($vvalue[3]/$vvalue[2])*100);
                            }
                            else
                            {
                                $pax_ar[$kkey][4] = $pax_ar[$kkey][4]+0;
                            }
                            
                            //Accur Delay
                            //echo "(".$vvalue[3]."*22).+".$vvalue[5];
                            //$pax_ar[$kkey][5] = $pax_ar[$kkey][5]+(($vvalue[3]*22)+$vvalue[5]);
                        }
                        else
                        {                         
                            $pax_ar[$kkey] = $vvalue;
                            //print_r($vvalue);
                        }
                    }
                }

                $accur_delay = array();
                foreach($pax_ar as $pkey=>$pval)
                {                    
                    if(isset($flight_ar[$pkey]['2']))
                    {
                        $acc_delay = ($pval[3]*22)+$flight_ar[$pkey]['2'];
                    }
                    else
                    {
                        $acc_delay = 0;
                    }
                    $accur_delay[$pkey]= array("0"=>0,"1"=>$pkey,"2"=>$acc_delay);                    
                }     
                
                $pax_allotment_ar = array();
                foreach($pax_ar as $pkey=>$pval)
                {
                    if($pval[2]!=0)
                    {   
                        $allotment = round($pval['3']/$pval[2]);
                        $pax_allotment_ar[$pkey]= array("0"=>0,"1"=>$pkey,"2"=>$allotment);
                    }
                    else
                    {
                        $pax_allotment_ar[$pkey]= array("0"=>0,"1"=>$pkey,"2"=>'0');
                    }
                }
                
                //Vat eu dest:
                $vat_ed_dest = array();                
                foreach($newGcArray['1']['1'] as $key=>$val) {
                   $airCodeVatDestPrice = $this->getVatDestArivalPrice($val); 
                    $vardest = $flights_ar[$val][2]*$airCodeVatDestPrice;
                    $vat_ed_dest[$val] = array("0"=>"0","1"=>$val,"2"=>$vardest);
                    
                }    
     
                //Destinatin Costs
                $dest_cost = array();                
                foreach($newGcArray['1']['1'] as $key=>$val)
                {
                    $airCodeVatDestPrice = $this->getDestArivalPrice($val); 
                    if(isset($pax_ar[$val][3]))
                    {
                        $destCost = $pax_ar[$val][3]*$airCodeVatDestPrice;
                    }else { $destCost = 0; }
                    $dest_cost[$val] = array("0"=>"0","1"=>$val,"2"=>$destCost);
                }    
                
               
                    
                //Revenue Pax
                $rev_pa = array();
                foreach($newGcArray['1']['1'] as $key=>$val)
                {
                    $paxdet = isset($pax_ar[$val][3])?$pax_ar[$val][3] : '0';
                    $rev    = isset($ar[$val]['2'])?$ar[$val]['2']:'0';
                    if($paxdet!=0)
                    {
                        $d = round($rev/$paxdet);
                        $rev_pa[$val] = array('0'=>"1","1"=>$val,"2"=>$d);
                    }                    
                    else { $rev_pa[$val] = array('0'=>"1","1"=>$val,"2"=>'0'); }                   
                }          
                
                //Hotel Pax
                $hotal_pa = array();
                foreach($newGcArray['1']['1'] as $key=>$val)
                {
                    $hotDet = isset($hotel1_ar[$val][2])?$hotel1_ar[$val][2] : '0';
                    $pax_det   = isset($pax_ar[$val]['3'])?$pax_ar[$val]['3']:'0';
                    if($pax_det!=0)
                    {
                        $d = round($hotDet/$pax_det);
                        $hotal_pa[$val] = array('0'=>"1","1"=>$val,"2"=>$d);
                    }                    
                    else { $hotal_pa[$val] = array('0'=>"1","1"=>$val,"2"=>'0'); }                   
                }
                
                //Flight Pax
                $flight_pa = array();
                foreach($newGcArray['1']['1'] as $key=>$val)
                {
                    $fliDet    =    isset($flight_ar[$val][2])?$flight_ar[$val][2] : '0';
                    $pax_det   =    isset($pax_ar[$val][3])?$pax_ar[$val][3]:'0';
                    if($pax_det!=0)
                    {
                        $d = round($fliDet/$pax_det);
                        $flight_pa[$val] = array('0'=>"1","1"=>$val,"2"=>$d);
                    }                    
                    else { $flight_pa[$val] = array('0'=>"1","1"=>$val,"2"=>'0'); }                   
                }                
               
                //Net GC
                $netGCP = array();
                foreach($newGcArray['1']['1'] as $key=>$val)
                {                     
                    $fliDet     =  isset($flights_ar[$val][2])?$flights_ar[$val][2] : '0';
                    $paxDet     =  isset($dest_cost[$val][2])?$dest_cost[$val][2] : '0';
                    $paxDet1    =  isset($vat_ed_dest[$val][2])?$vat_ed_dest[$val][2] : '0'; 
                    $ageDet     =  isset($agent_ar[$val][2])?$agent_ar[$val][2] : '0';
                    $paxDet2    =  isset($pax_ar[$val][5])?$pax_ar[$val][5] : '0';                   
                    
                    $netGc          = $fliDet-($paxDet);
                    $netGCP[$val]   = array("0"=>"0","1"=>$val,"2"=>$netGc);
                }   
                
                //Gc PAX
                $gc_pax = array();
                foreach($newGcArray['1']['1'] as $key=>$val)
                {
                    $netGcDet       =  isset($netGCP[$val][2])?$netGCP[$val][2]:'0';
                    $pax_det        =  isset($pax_ar[$val][3])?$pax_ar[$val][3]:'0';
                        if($pax_det!=0) {
                            $d = round($netGcDet/$pax_det);
                            $gc_pax[$val] = array('0'=>"1","1"=>$val,"2"=>$d);
                        }
                    else 
                        { $gc_pax[$val] = array('0'=>"1","1"=>$val,"2"=>'0'); }                   
                }               
                
                //Showing the on the browser
                return $this->render('report/list.html.twig', [
                    'GCDATA'    => $newGcArray,                    
                    'search'    => $request->query->all(),
                    "StartDate" => $cur_date,//$DepDatesFrToList,
                    "Brand"     => $brand_title,
                    "revenue"   => $ar,
                    "hotel"     => $hotel1_ar,
                    "flight"    => $flight_ar,
                    "agent"     => $agent_ar,
                    "pax"       => $pax_ar,
                    "GC"        => $flights_ar,
                    "GCP"       => $gcp,
                    "rev_pa"    => $rev_pa,
                    "hotel_pa"  => $hotal_pa,
                    "flight_pa" =>$flight_pa,
                    "netGCP"    => $netGCP,
                    "gcpax"=>$gc_pax,
                    "sum"=>$sum,
                    "sum1"=>$sum1,
                    "pax_allotment_ar"=>$pax_allotment_ar,
                    "vat_ed_dest"=>$vat_ed_dest, 
                    "dest_cost"=>$dest_cost,
                    "accur_delay"=>$accur_delay
                ]);
            }
                   
        }
        return $this->render('report/list.html.twig', [
                'countries' => $this->data,
                'summary' => $summary,
                'search' => $request->query->all(),
        ]);
    }
    
    public function brandTitle($promCd)
    {        
        $atcore = $this->get('app.atcore');
        $conn = $this->get('doctrine.dbal.atcore_connection');
        $sql= "select NAME from atcomres.ar_promotion where cd=UPPER('$promCd')";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll();
        return $results['0']['NAME'];
    }
    
    public function move_to_top(&$array, $key) 
    {
        $temp = array($key => $array[$key]);
        unset($array[$key]);
        $array = $temp + $array;
    }    
    
    public function generate_GC($depFromDates,$depToDate,$all_pivot_data)
    { 
        $AllDiffDates   =   $this->diff_dates($depFromDates,$depToDate);
        $dAr            =   $this->GC_REPORT($AllDiffDates,$all_pivot_data);             
        
        $array = array("0"=>$all_pivot_data,"1"=>$dAr,"2"=>$AllDiffDates,'count'=>1);
        return $array;
    }
    public function generate_Hotel($request,$dep_cd,$arr_cd,$promCd,$fromdate,$todate,$fromdatedep,$todatedep,$acco,$excel,$gcDtForamt,$AccCode,$AccCodes)
    {
        $atcore = $this->get('app.atcore');
        $AccmCd    =   $acco;
        $promCds   =   strtoupper($promCd);
        $arr_cds   =   strtoupper($arr_cd);
        $dep_cds   =   strtoupper($dep_cd);

        $fromdatedept = "'".str_replace("'", '', $fromdatedep)."'";
        $todatedept   = "'".str_replace("'", '', $todatedep)."'";

        $bfromdate  = $fromdate!='' ? "'".str_replace("'", '', $fromdate)."'" : 'NULL';         
        $btodate    = $todate!='' ? "'".str_replace("'", '', $todate)."'" : 'NULL'; 

        /*$depat      =   '';
        $rooms      =   '';
        $tod        =   '';
        $prom       =   '';
        $AccmCd     =   '';*/

        $arr_pt_id  = $this->Get_Pt_ID($arr_cd);
        $dep_pt_id  = $this->Get_Pt_ID($dep_cd);

        $arrId = $arr_pt_id !='' ? $arr_pt_id : "NULL"; 
        $depId = $dep_pt_id !='' ? $dep_pt_id : "NULL";          
        
        $sql = "select dest_airport as DESTNATION_CODE,DEP_DATE as ARRIVAL_DATE,sum(total_cost) as COST_LOCAL_CURRENCY 
                    from ( SELECT DISTINCT
                                                cty3.name AS RESORT,
                                                stk.cd AS HOTEL_CD,
                                                stk.name AS HOTEL,
                                                (
                                                    SELECT
                                                        pt_cd
                                                    FROM
                                                        atcomres.ar_point
                                                    WHERE
                                                        1 = 1
                                                        AND pt_id = rs1.arr_pt_id
                                                ) AS dest_airport,     
                                                TO_CHAR(res.first_st_dt,'".$gcDtForamt."') AS DEP_DATE,
                                                res.res_id AS BOOK_NUM,
                                                res.bkg_sts as BOOK_STATUS,
                                                TO_CHAR(res.con_dt,'".$gcDtForamt."') AS CON_DT,
                                                res.payee_firstname
                                                || ' '
                                                || res.payee_lastname AS NAME,
                                                res.n_pax AS N_PAX
                                                ,res.sell_prc AS TOTAL_PRICE
                                                ,rss.stk_prc AS TOTAL_COST
                                            FROM
                                                atcomres.ar_staticstock stk
                                                LEFT JOIN atcomres.ar_sellstatic ss ON ss.stc_stk_id = stk.stc_stk_id
                                                LEFT JOIN atcomres.ar_resservice rs ON rs.ser_id = ss.sell_stc_id
                                                LEFT JOIN atcomres.ar_reservation res ON res.res_id = rs.res_id
                                                LEFT JOIN atcomres.ar_ressubservice rss ON rss.res_ser_id = rs.res_ser_id
                                                LEFT JOIN atcomres.ar_point cty3 ON cty3.pt_id = stk.cty3_pt_id
                                                LEFT JOIN atcomres.ar_promotion prm ON prm.prom_id = res.prom_id
                                                LEFT JOIN (
                                                    SELECT
                                                        *
                                                    FROM
                                                        atcomres.ar_resservice
                                                    WHERE
                                                        ser_sts = 'CON'
                                                        AND ser_tp = 'TRS'
                                                        AND dir = 'OUT'
                                                ) rs1 ON rs1.res_id = res.res_id
                                            WHERE
                                                res.bkg_sts = 'BKG'
                                                AND rs.ser_sts = 'CON'
                                                AND rs.ser_tp = 'ACC'
                                                AND prm.prom_id IN (
                                                    SELECT DISTINCT
                                                        prom_id
                                                    FROM
                                                        atcomres.ar_promotion
                                                    WHERE
                                                        substr(cd,1,2) = '$promCds'
                                                )
                                                AND res.first_st_dt BETWEEN TO_DATE($fromdatedept,'DD-MON-YYYY') AND TO_DATE($todatedept,'DD-MON-YYYY')
                                                AND   ($bfromdate IS NULL OR res.con_dt >= TO_DATE($bfromdate,'DD-MON-YYYY'))
                                                AND   ($btodate IS NULL OR res.con_dt <= TO_DATE($btodate,'DD-MON-YYYY'))
                                                AND   ($depId IS NULL OR  rs.dep_pt_id = $depId)
                                                AND   ($arrId IS NULL OR  rs.arr_pt_id = $arrId)
                                                AND   ($AccCodes IS NULL OR stk.cd IN($AccCode))    
                                            ORDER BY 1,2,3,4,5,6  ) X group by dest_airport,DEP_DATE";
                    $conn = $this->get('doctrine.dbal.atcore_connection');
                    $stmt = $conn->prepare($sql);
                    $stmt->execute();
                    $results = $stmt->fetchAll();
                    
                    $cnt = count($results);
                    
                    $multi_flights = [];
                    $search_name=array();
    
                    foreach ($results as $result) {
                        $Ddate      =   explode(" ",$result['ARRIVAL_DATE']);
                        $dates      =   date_create($Ddate['0']);
                        $dkDate     =   date_format($dates,"m/d/Y");
                        
                        $search_name[]=array('ARRIVAL_DATE'=>$dkDate,
                                            'DESTNATION_CODE'=>$result['DESTNATION_CODE'],
                                            'COST_LOCAL_CURRENCY'=>round($result['COST_LOCAL_CURRENCY'])                                            
                                            );
                    }
                    $array=array('ARRIVAL_DATE','DESTNATION_CODE','COST_LOCAL_CURRENCY');
 
                   $search_name['count']=count($array);
                   
                   if($cnt==0)
                   {
                       $search_name[]=array('ARRIVAL_DATE'=>" 0 ",
                                            'DESTNATION_CODE'=>" 0 ",
                                            'COST_LOCAL_CURRENCY'=>" 0 "
                                            );
                    }
                   if( ($excel) || ($excel=='') )  { return $search_name; }
    }

    public function generate_R16($request,$dep_cd,$arr_cd,$promCd,$fromdate,$todate,$fromdatedep,$todatedep,$acco,$excel,$gcDtForamt,$AccCode,$AccCodes)
    {         
        $atcore    =   $this->get('app.atcore');
        $AccmCd    =   $acco;
        $promCds   =   strtoupper($promCd);
        $arr_cds   =   strtoupper($arr_cd);
        $dep_cds   =   strtoupper($dep_cd);
        
        $fromdatedept = "'".str_replace("'", '', $fromdatedep)."'";
        $todatedept   = "'".str_replace("'", '', $todatedep)."'";
        
        $bfromdate = $fromdate!='' ? "'".str_replace("'", '', $fromdate)."'" : 'NULL'; 
        $btodate = $todate!='' ? "'".str_replace("'", '', $todate)."'" : 'NULL'; 
                
        /*$depat      =   '';
        $rooms      =   '';
        $tod        =   '';
        $prom       =   '';
        $AccmCd     =   '';*/
        
        $arr_pt_id  = $this->Get_Pt_ID($arr_cd);
        $dep_pt_id  = $this->Get_Pt_ID($dep_cd);
        
        $arrId = $arr_pt_id !='' ? $arr_pt_id : "NULL"; 
        $depId = $dep_pt_id !='' ? $dep_pt_id : "NULL"; 
        
        $sql = "SELECT DISTINCT
                            cty3.name AS RESORT,
                            stk.cd AS HOTEL_CD,
                            stk.name AS HOTEL,
                            (
                                SELECT
                                    pt_cd
                                FROM
                                    atcomres.ar_point
                                WHERE
                                    1 = 1
                                    AND pt_id = rs1.arr_pt_id
                            ) AS ARRIVAL_CODES,     
                            TO_CHAR(res.first_st_dt,'".$gcDtForamt."') AS DEP_DATE,
                            res.res_id AS BOOK_NUM,
                            res.bkg_sts as BOOK_STATUS,
                            TO_CHAR(res.con_dt,'".$gcDtForamt."') AS CON_DT,
                            res.payee_firstname
                            || ' '
                            || res.payee_lastname AS NAME,
                            res.n_pax AS N_PAX
                            ,res.sell_prc AS TOTAL_PRICE
                            ,rss.stk_prc AS TOTAL_COST
                        FROM
                            atcomres.ar_staticstock stk
                            LEFT JOIN atcomres.ar_sellstatic ss ON ss.stc_stk_id = stk.stc_stk_id
                            LEFT JOIN atcomres.ar_resservice rs ON rs.ser_id = ss.sell_stc_id
                            LEFT JOIN atcomres.ar_reservation res ON res.res_id = rs.res_id
                            LEFT JOIN atcomres.ar_ressubservice rss ON rss.res_ser_id = rs.res_ser_id
                            LEFT JOIN atcomres.ar_point cty3 ON cty3.pt_id = stk.cty3_pt_id
                            LEFT JOIN atcomres.ar_promotion prm ON prm.prom_id = res.prom_id
                            LEFT JOIN (
                                SELECT
                                    *
                                FROM
                                    atcomres.ar_resservice
                                WHERE
                                    ser_sts = 'CON'
                                    AND ser_tp = 'TRS'
                                    AND dir = 'OUT'
                            ) rs1 ON rs1.res_id = res.res_id
                        WHERE
                            res.bkg_sts = 'BKG'
                            AND rs.ser_sts = 'CON'
                            AND rs.ser_tp = 'ACC'
                            AND prm.prom_id IN (
                                SELECT DISTINCT
                                    prom_id
                                FROM
                                    atcomres.ar_promotion
                                WHERE
                                    substr(cd,1,2) = '$promCds'
                            )
                            AND res.first_st_dt BETWEEN TO_DATE($fromdatedept,'DD-MON-YYYY') AND TO_DATE($todatedept,'DD-MON-YYYY')
                            AND   ($bfromdate IS NULL OR res.con_dt >= TO_DATE($bfromdate,'DD-MON-YYYY'))
                            AND   ($btodate IS NULL OR res.con_dt <= TO_DATE($btodate,'DD-MON-YYYY'))
                            AND   ($depId IS NULL OR  rs.dep_pt_id = $depId)
                            AND   ($arrId IS NULL OR  rs.arr_pt_id = $arrId)
                            AND   ($AccCodes IS NULL OR stk.cd IN($AccCode))    
                        ORDER BY 1,2,3,4,5,6";                                
      
                    $conn = $this->get('doctrine.dbal.atcore_connection');
                    $stmt = $conn->prepare($sql);
                    $stmt->execute();
                    $results = $stmt->fetchAll();    
                    
                    $cnt     = count($results);
                    $multi_flights = [];
                    $search_name=array();
                    foreach ($results as $result) {                        
                        $search_name[]=array(
                                            'Resort'=>$result['RESORT'],                                                                                        
                                            'Accommodation'=>$result['HOTEL'],
                                           // 'Hotel Code'=>$result['HOTEL_CD'],
                                            'Booking Number'=>" ".$result['BOOK_NUM'],                                  
                                            'Booking Status'=>$result['BOOK_STATUS'],                                            
                                            'Departure Date'=>$result['DEP_DATE'],
                                            'Booking Date'=>$result['CON_DT'],
                                            'Destination Airport Code'=>$result['ARRIVAL_CODES'],
                                            'Name'=>$result['NAME'],
                                            'PAX'=>$result['N_PAX'],
                                            'Total Price'=>$result['TOTAL_PRICE'],
                                            'Total Cost'=>$result['TOTAL_COST']                                            
                                            );
                    } 

                    $array=array('Resort','Accommodation','Booking Number','Booking Status','Departure Date','Booking Date','Arrival Code','Name','PAX','Total Price','Total Cost');
 
                    $search_name['count']=count($array);
                   
                    if($cnt==0)
                    {
                       $search_name[]   =   array(
                                                    'Resort'=>'0',
                                                    'Booking Status'=>0,
                                                    'Accommodation'=>'0',                                                    
                                                    'Booking Number'=>'0',
                                                    'Departure Date'=>'0',
                                                    'Booking Date'=>'0',
                                                    'Destination Airport Code'=>'0',
                                                    'Name'=>'0',
                                                    'PAX'=>'0',
                                                    'Total Price'=>'0',
                                                    'Total Cost'=>'0');                       
                    }
                    
                   if( ($excel) || ($excel=='') ) { return $search_name; }
    }
    
    public function generate_PAX($request,$dep_cd,$arr_cd,$promCd,$fromdate,$todate,$fromdatedep,$todatedep,$acco,$excel,$gcDtForamt,$AccCode,$AccCodes)
    {
        $atcore     =   $this->get('app.atcore');        
       
        $promCds    =   strtoupper($promCd);
        $arr_cds    =   strtoupper($arr_cd);
        $dep_cds    =   strtoupper($dep_cd);
        
        $fromdatedept = "'".str_replace("'", '', $fromdatedep)."'";
        $todatedept   = "'".str_replace("'", '', $todatedep)."'";
        
        $bfromdate  = $fromdate!='' ? "'".str_replace("'", '', $fromdate)."'" : 'NULL'; 
        $btodate    = $todate!='' ? "'".str_replace("'", '', $todate)."'" : 'NULL'; 
        
        $depat      =   '';
        $rooms      =   '';
        $tod        =   '';
        $prom       =   '';
 
        $arr_pt_id  = $this->Get_Pt_ID($arr_cd);
        $dep_pt_id  = $this->Get_Pt_ID($dep_cd);

        $arrId = $arr_pt_id !='' ? $arr_pt_id : "NULL"; 
        $depId = $dep_pt_id !='' ? $dep_pt_id : "NULL";                           
        
        $sql = "SELECT DISTINCT dep_dt 
                                , arr_pt_cd   
                                , dep_pt_cd
                                , SUM( out_alt ) AS out_alt
                                , SUM( out_bkd ) AS out_bkd
                                , SUM( out_rem ) AS out_rem                                
                        FROM
                        ( SELECT DISTINCT TO_CHAR(tis.dep_dt_tm,'".$gcDtForamt."') AS dep_dt
                                ,th.cd           AS transport
                                ,th.name         AS trans_name
                                ,ofn.cd          AS carrier_cd
                                ,ofn.name        AS carrier
                                ,CASE WHEN length( prm.cd ) > 2 AND substr( prm.cd,3,4 ) IN ('DP','AD','TP') THEN  prm.cd
                                    ELSE substr( prm.cd,1,2 ) 
                                 END AS brand
                                ,( SELECT pt.pt_cd FROM atcomres.ar_point pt WHERE pt.pt_id = tis.arr_pt_id)   AS arr_pt_cd
                                ,( SELECT pt.pt_cd FROM atcomres.ar_point pt WHERE pt.pt_id = tis.dep_pt_id)   AS dep_pt_cd 
                                ,tis.alt         AS out_alt
                                ,tis.bkd         AS out_bkd
                                ,tis.alt-tis.bkd AS out_rem
                                ,0               AS in_alt
                                ,0               AS in_bkd
                                ,0               AS in_rem
                        FROM atcomres.ar_transhead th 
                        LEFT JOIN atcomres.ar_transinvsector tis ON tis.trans_head_id   = th.trans_head_id
                        LEFT JOIN atcomres.ar_transroute tr      ON tr.trans_head_id    = th.trans_head_id
                        LEFT JOIN atcomres.ar_transinvroute tir  ON (tir.trans_route_id = tr.trans_route_id AND tir.arr_sec_id = tis.trans_inv_sec_id )
                        LEFT JOIN atcomres.ar_resservice rs      ON rs.ser_id           = tir.trans_inv_route_id
                        LEFT JOIN atcomres.ar_reservation res    ON res.res_id          = rs.res_id
                        LEFT JOIN atcomres.ar_sellstatic ss      ON ss.sell_stc_id      = rs.ser_id
                        LEFT JOIN atcomres.ar_staticstock stk    ON stk.stc_stk_id      = ss.stc_stk_id
                        LEFT JOIN atcomres.ar_promotion prm      ON prm.prom_id         = rs.prom_id
                        LEFT JOIN atcomres.ar_officename ofn     ON ofn.off_name_id     = tis.carrier_id
                        WHERE 1=1
                          AND tis.dir_mth    ='OUT'
                          AND tr.dir_mth     ='OUT'
                          AND tis.sale_sts   ='ON'
                          AND tis.dep_dt_tm BETWEEN TO_DATE($fromdatedept ,'DD-MON-YYYY') AND TO_DATE($todatedept ,'DD-MON-YYYY')
                          AND prm.prom_id IN( SELECT DISTINCT prom_id FROM atcomres.ar_promotion WHERE substr( cd,1,2 ) ='$promCds')
                          AND ($bfromdate IS NULL OR res.con_dt >= TO_DATE($bfromdate ,'DD-MON-YYYY'))
                          AND (NULL IS NULL OR res.con_dt < TO_DATE($btodate ,'DD-MON-YYYY'))
                          AND ($depId IS NULL OR tis.dep_pt_id   = $depId )
                          AND ($arrId IS NULL OR tis.arr_pt_id   = $arrId )
                          AND ($AccCodes IS NULL OR stk.cd IN($AccCode))                          
                        UNION ALL
                        SELECT DISTINCT   TO_CHAR(tis.dep_dt_tm,'".$gcDtForamt."') AS dep_dt
                                          ,th.cd           AS transport
                                          ,th.name         AS trans_name
                                          ,ofn.cd          AS carrier_cd
                                          ,ofn.name        AS carrier
                                          ,CASE WHEN length( prm.cd ) > 2 AND substr( prm.cd,3,4 ) IN ('DP','AD','TP') THEN  prm.cd
                                              ELSE substr( prm.cd,1,2 ) 
                                           END AS brand
                                          ,( SELECT pt.pt_cd FROM atcomres.ar_point pt WHERE pt.pt_id = tis.dep_pt_id)   AS arr_pt_cd
                                          ,( SELECT pt.pt_cd FROM atcomres.ar_point pt WHERE pt.pt_id = tis.arr_pt_id)   AS dep_pt_cd 
                                          ,0               AS out_alt
                                          ,0               AS out_bkd
                                          ,0               AS out_rem
                                          ,tis.alt         AS in_alt
                                          ,tis.bkd         AS in_bkd
                                          ,tis.alt-tis.bkd AS in_rem
                        FROM atcomres.ar_transhead th 
                        LEFT JOIN atcomres.ar_transinvsector tis ON tis.trans_head_id   = th.trans_head_id
                        LEFT JOIN atcomres.ar_transroute tr      ON tr.trans_head_id    = th.trans_head_id
                        LEFT JOIN atcomres.ar_transinvroute tir  ON (tir.trans_route_id = tr.trans_route_id AND tir.arr_sec_id = tis.trans_inv_sec_id )
                        LEFT JOIN atcomres.ar_resservice rs      ON rs.ser_id           = tir.trans_inv_route_id
                        LEFT JOIN atcomres.ar_reservation res    ON res.res_id          = rs.res_id
                        LEFT JOIN atcomres.ar_sellstatic ss      ON ss.sell_stc_id      = rs.ser_id
                        LEFT JOIN atcomres.ar_staticstock stk    ON stk.stc_stk_id      = ss.stc_stk_id
                        LEFT JOIN atcomres.ar_promotion prm      ON prm.prom_id         = rs.prom_id
                        LEFT JOIN atcomres.ar_officename ofn     ON ofn.off_name_id     = tis.carrier_id
                        WHERE 1=1
                          AND tis.dir_mth    ='RET'
                          AND tr.dir_mth     ='RET'
                          AND tis.sale_sts   ='ON'
                          AND tis.dep_dt_tm BETWEEN TO_DATE($fromdatedept ,'DD-MON-YYYY') AND TO_DATE($todatedept ,'DD-MON-YYYY')
                          AND prm.prom_id IN( SELECT DISTINCT prom_id FROM atcomres.ar_promotion WHERE substr( cd,1,2 ) ='$promCds')
                          AND ($bfromdate IS NULL OR res.con_dt >= TO_DATE($bfromdate ,'DD-MON-YYYY'))
                          AND ($btodate IS NULL OR res.con_dt < TO_DATE($btodate ,'DD-MON-YYYY'))
                          AND ($depId IS NULL OR tis.dep_pt_id   = $depId )
                          AND ($arrId IS NULL OR tis.arr_pt_id   = $arrId )
                          AND ($AccCodes IS NULL OR stk.cd IN($AccCode))
                        ) GROUP BY dep_dt, transport, carrier, brand, arr_pt_cd, dep_pt_cd
                        ORDER BY dep_dt,arr_pt_cd, dep_pt_cd";
     
                   $conn = $this->get('doctrine.dbal.atcore_connection');

                   $stmt = $conn->prepare($sql);
                   
                   $stmt->execute();
                   $results = $stmt->fetchAll();  

                   $cnt = count($results);
                   
                   $multi_flights = [];
                   $search_name=array();                  

                   foreach ($results as $result) {        
                       
                        $hand_fee = $this->arrival_handling_fee($result['ARR_PT_CD']); 
                        $hand_fee = ($hand_fee!='')?($result['OUT_ALT']*$hand_fee):0; 
                        
                        $tweak_average = $this->arrival_tomben_average($result['ARR_PT_CD']);
                        $tweak_average = ($tweak_average!='')? ($tweak_average*$result['OUT_BKD']):0;        
                            
                       $search_name[]=array('Handlings fee/Complaints'=>$hand_fee,
                                            "Out Alt Seats" =>$result['OUT_ALT'],
                                            'Out Sold Seats' => $result['OUT_BKD'],                                            
                                            'Arrival Airport'=>$result['ARR_PT_CD'],
                                            'Departure Date'=>   $result['DEP_DT'],
                                            'Empty_legs'=>$tweak_average
                                           );
                   }                   
                   
                   $division_data   = array();
                   $sub_array       = array();
                   foreach($search_name as $key=>$val)
                   {
                        $sub_array[] = array($val['Arrival Airport'],$val['Departure Date']);
                   }    
                   $array_unq = array_unique($sub_array,SORT_REGULAR); 
                   
                   foreach($array_unq as $key=>$value)
                   {
                        $handf = 0;
                        $oseat = 0;
                        $osseat = 0;
                        $thomben=0;
                       foreach($search_name as $kk=>$val)
                       {
                           if( ($value['0']==$val['Arrival Airport']) && ($value['1']==$val['Departure Date']))
                           {                               
                               $handf  =  $handf+$val['Handlings fee/Complaints'];
                               $oseat  =  $oseat+$val['Out Alt Seats'];
                               $osseat =  $osseat+$val['Out Sold Seats'];        
                               $thomben =  $thomben+$val['Empty_legs']; 
                           }
                       }
                       $division_data[] = array("Handlings fee/Complaints"=>$handf,
                                                "Out Alt Seats"=>$oseat,
                                                "Out Sold Seats"=>$osseat,
                                                "Arrival Airport"=>$value['0'],
                                                "Departure Date"=>$value['1'],
                                                "Empty_legs"=>$thomben);
                   }
                   
                   $array=array('Handlings fee/Complaints','Out Alt Seats','Out Sold Seats','Arrival Airport','Departure Date','Empty_legs');                   
                   $division_data['count']=count($array); 
                if($cnt==0)
                {
                    $division_data[]= array('Handlings fee/Complaints'=>'0',
                                            "Out Alt Seats" =>'0',
                                            'Out Sold Seats' =>'0',                                            
                                            'Arrival Airport'=>'0',
                                            'Departure Date'=>'0',
                                            'Empty_legs'=>'0'
                                           );
                }
                if(($excel) || ($excel=='')) { return $division_data; }
    }
    
    public function generate_R3_20($request,$dep_cd,$arr_cd,$promCd,$fromdate,$todate,$fromdatedep,$todatedep,$acco,$excel,$gcDtForamt,$AccCode,$AccCodes)
    {
                    $atcore    =   $this->get('app.atcore');
                    
                    $promCds   =   strtoupper($promCd);
                    $arr_cds   =   strtoupper($arr_cd);
                    $dep_cds   =   strtoupper($dep_cd);

                    $fromdatedept = "'".str_replace("'", '', $fromdatedep)."'";
                    $todatedept   = "'".str_replace("'", '', $todatedep)."'";

                    $bfromdate = $fromdate!='' ? "'".str_replace("'", '', $fromdate)."'" : "NULL";         

                    $btodate = $todate!='' ? "'".str_replace("'", '', $todate)."'" : "NULL"; 

                    /*$depat      =   '';
                    $rooms      =   '';
                    $tod        =   '';
                    $prom       =   '';
                    $AccmCd     =   '';*/

                    $arr_pt_id  = $this->Get_Pt_ID($arr_cd);
                    $dep_pt_id  = $this->Get_Pt_ID($dep_cd);

                    $arrId = $arr_pt_id !='' ? $arr_pt_id : "NULL"; 
                    $depId = $dep_pt_id !='' ? $dep_pt_id : "NULL";                     

                    $sql = "SELECT DISTINCT dep_dt
                                        , transport
                                        , carrier
                                        ,CASE SUBSTR(brand,1,2) 
                                            WHEN 'SR' THEN 'Solresor'
                                            WHEN 'BT' THEN 'Bravo Tours'
                                            WHEN 'HF' THEN 'Heimsferdir'
                                            WHEN 'LM' THEN 'Matkavekka'
                                            WHEN 'SO' THEN 'Solia'
                                            WHEN 'ST' THEN 'Bravo Tours'
                                            WHEN 'UK' THEN 'Primera Holidays UK'
                                            ELSE 'Others'
                                            END AS brand
                                        , arr_pt_cd   
                                        , dep_pt_cd
                                        , SUM( out_alt ) AS out_alt
                                        , SUM( out_bkd ) AS out_bkd
                                        , SUM( out_rem ) AS out_rem
                                        , SUM( in_alt )  AS in_alt
                                        , SUM( in_bkd )  AS in_bkd
                                        , SUM( in_rem )  AS in_rem                                        
                        FROM
                        ( SELECT DISTINCT TO_CHAR(tis.dep_dt_tm,'".$gcDtForamt."') AS dep_dt
                                          ,th.cd           AS transport
                                          ,th.name         AS trans_name
                                          ,ofn.cd          AS carrier_cd
                                          ,ofn.name        AS carrier
                                          ,CASE WHEN length( prm.cd ) > 2 AND substr( prm.cd,3,4 ) IN ('DP','AD','TP') THEN  prm.cd
                                              ELSE substr( prm.cd,1,2 ) 
                                           END AS brand
                                          ,( SELECT pt.pt_cd FROM atcomres.ar_point pt WHERE pt.pt_id = tis.arr_pt_id)   AS arr_pt_cd
                                          ,( SELECT pt.pt_cd FROM atcomres.ar_point pt WHERE pt.pt_id = tis.dep_pt_id)   AS dep_pt_cd 
                                          ,tis.alt         AS out_alt
                                          ,tis.bkd         AS out_bkd
                                          ,tis.alt-tis.bkd AS out_rem
                                          ,0               AS in_alt
                                          ,0               AS in_bkd
                                          ,0               AS in_rem
                        FROM atcomres.ar_transhead th 
                        LEFT JOIN atcomres.ar_transinvsector tis ON tis.trans_head_id   = th.trans_head_id
                        LEFT JOIN atcomres.ar_transroute tr      ON tr.trans_head_id    = th.trans_head_id
                        LEFT JOIN atcomres.ar_transinvroute tir  ON (tir.trans_route_id = tr.trans_route_id AND tir.arr_sec_id = tis.trans_inv_sec_id )
                        LEFT JOIN atcomres.ar_resservice rs      ON rs.ser_id           = tir.trans_inv_route_id
                        LEFT JOIN atcomres.ar_reservation res    ON res.res_id          = rs.res_id
                        LEFT JOIN atcomres.ar_sellstatic ss      ON ss.sell_stc_id      = rs.ser_id
                        LEFT JOIN atcomres.ar_staticstock stk    ON stk.stc_stk_id      = ss.stc_stk_id
                        LEFT JOIN atcomres.ar_promotion prm      ON prm.prom_id         = rs.prom_id
                        LEFT JOIN atcomres.ar_officename ofn     ON ofn.off_name_id     = tis.carrier_id
                        WHERE 1=1
                          AND tis.dir_mth    ='OUT'
                          AND tr.dir_mth     ='OUT'
                          AND tis.sale_sts   ='ON'
                          AND tis.dep_dt_tm BETWEEN TO_DATE($fromdatedept ,'DD-MON-YYYY') AND TO_DATE($todatedept ,'DD-MON-YYYY')
                          AND prm.prom_id IN( SELECT DISTINCT prom_id FROM atcomres.ar_promotion WHERE substr( cd,1,2 ) ='$promCds')
                          AND ($bfromdate IS NULL OR res.con_dt >= TO_DATE($bfromdate ,'DD-MON-YYYY'))
                          AND (NULL IS NULL OR res.con_dt < TO_DATE($btodate ,'DD-MON-YYYY'))
                          AND ($depId IS NULL OR tis.dep_pt_id   = $depId )
                          AND ($arrId IS NULL OR tis.arr_pt_id   = $arrId )
                          AND ($AccCodes IS NULL OR stk.cd IN($AccCode))                          
                        UNION ALL
                        SELECT DISTINCT   TO_CHAR(tis.dep_dt_tm,'".$gcDtForamt."') AS dep_dt
                                          ,th.cd           AS transport
                                          ,th.name         AS trans_name
                                          ,ofn.cd          AS carrier_cd
                                          ,ofn.name        AS carrier
                                          ,CASE WHEN length( prm.cd ) > 2 AND substr( prm.cd,3,4 ) IN ('DP','AD','TP') THEN  prm.cd
                                              ELSE substr( prm.cd,1,2 ) 
                                           END AS brand
                                          ,( SELECT pt.pt_cd FROM atcomres.ar_point pt WHERE pt.pt_id = tis.dep_pt_id)   AS arr_pt_cd
                                          ,( SELECT pt.pt_cd FROM atcomres.ar_point pt WHERE pt.pt_id = tis.arr_pt_id)   AS dep_pt_cd 
                                          ,0               AS out_alt
                                          ,0               AS out_bkd
                                          ,0               AS out_rem
                                          ,tis.alt         AS in_alt
                                          ,tis.bkd         AS in_bkd
                                          ,tis.alt-tis.bkd AS in_rem
                        FROM atcomres.ar_transhead th 
                        LEFT JOIN atcomres.ar_transinvsector tis ON tis.trans_head_id   = th.trans_head_id
                        LEFT JOIN atcomres.ar_transroute tr      ON tr.trans_head_id    = th.trans_head_id
                        LEFT JOIN atcomres.ar_transinvroute tir  ON (tir.trans_route_id = tr.trans_route_id AND tir.arr_sec_id = tis.trans_inv_sec_id )
                        LEFT JOIN atcomres.ar_resservice rs      ON rs.ser_id           = tir.trans_inv_route_id
                        LEFT JOIN atcomres.ar_reservation res    ON res.res_id          = rs.res_id
                        LEFT JOIN atcomres.ar_sellstatic ss      ON ss.sell_stc_id      = rs.ser_id
                        LEFT JOIN atcomres.ar_staticstock stk    ON stk.stc_stk_id      = ss.stc_stk_id
                        LEFT JOIN atcomres.ar_promotion prm      ON prm.prom_id         = rs.prom_id
                        LEFT JOIN atcomres.ar_officename ofn     ON ofn.off_name_id     = tis.carrier_id
                        WHERE 1=1
                          AND tis.dir_mth    ='RET'
                          AND tr.dir_mth     ='RET'
                          AND tis.sale_sts   ='ON'
                          AND tis.dep_dt_tm BETWEEN TO_DATE($fromdatedept ,'DD-MON-YYYY') AND TO_DATE($todatedept ,'DD-MON-YYYY')
                          AND prm.prom_id IN( SELECT DISTINCT prom_id FROM atcomres.ar_promotion WHERE substr( cd,1,2 ) ='$promCds')
                          AND ($bfromdate IS NULL OR res.con_dt >= TO_DATE($bfromdate ,'DD-MON-YYYY'))
                          AND ($btodate IS NULL OR res.con_dt < TO_DATE($btodate ,'DD-MON-YYYY'))
                          AND ($depId IS NULL OR tis.dep_pt_id   = $depId )
                          AND ($arrId IS NULL OR tis.arr_pt_id   = $arrId )
                          AND ($AccCodes IS NULL OR stk.cd IN($AccCode))
                        ) GROUP BY dep_dt, transport, carrier, brand, arr_pt_cd, dep_pt_cd
                        ORDER BY dep_dt, transport, carrier, brand, arr_pt_cd, dep_pt_cd";                                                 
                                
                        $conn = $this->get('doctrine.dbal.atcore_connection');
                        $stmt = $conn->prepare($sql);
                        
                        $stmt->execute();
                        $results = $stmt->fetchAll();                
                        
                        $cnt = count($results);
                   
                        $multi_flights  =   [];
                        $search_name    =   array();
                        foreach ($results as $result) 
                        {   
                            
                            $hand_fee = $this->arrival_handling_fee($result['ARR_PT_CD']); 
                            $hand_fee = ($hand_fee!='')?($result['OUT_ALT']*$hand_fee):0;                            
                            
                            $tweak_average = $this->arrival_tomben_average($result['ARR_PT_CD']);
                            $tweak_average = ($tweak_average!='')? ($tweak_average*$result['OUT_BKD']):0;                            
                            
                            $search_name[]=array('Departure Date'=>$result['DEP_DT'],
                                                 'Transport'=>$result['TRANSPORT'], 
                                                 'Carrier'=>$result['CARRIER'], 
                                                 'Brand'=>$result['BRAND'],
                                                 'Arrival Airport Code'=>$result['ARR_PT_CD'],                                                 
                                                 'Departure Airport Code'=>$result['DEP_PT_CD'],                                
                                                 'Out Alt Seats'=>$result['OUT_ALT'],
                                                 'Out Sold Seats'=>$result['OUT_BKD'],
                                                 'Out Left Seats'=>$result['OUT_REM'],                                                 
                                                 'In Alt Seats'=>$result['IN_ALT'],
                                                 'In Sold Seats'=>$result['IN_BKD'],
                                                 'In Left Seats'=>$result['IN_REM'],                                                 
                                                 'Handlings fee/Complaints'=>$hand_fee,
                                                 'Empty Legs'=>$tweak_average);
                        }                                 

                   $array=array('Departure Date','Transport','Carrier','Brand',
                                'Arrival Airport Code','Departure Airport Code','Out Alt Seats',
                                'Out Sold Seats' ,'Out Left Seats','In Alt Seats','In Sold Seats',
                                'In Left Seats','Handlings fee/Complaints','Empty Legs');                   
                   
                   $search_name['count']=count($array);
                   
                   if($cnt==0)
                   {
                       $search_name[]=array('Departure Date'=>'0','Transport'=>'0','Carrier' =>'0',
                                            'Brand' =>'0','Arrival Airport Code'=>'0','Departure Airport Code'=>'0',
                                            'Out Alt Seats'=>'0','Out Sold Seats'=>'0','Out Left Seats'=>'0',                                           
                                            'In Alt Seats'=>'0','In Sold Seats'=>'0','In Left Seats'=>'0',                                           
                                            'Handlings fee/Complaints'=>'0','Empty Legs'=>'0');
                    }
                   if(($excel) || ($excel=='')) {
                   return $search_name;
                   }
   }   
   
    public function generate_Revenue($request,$dep_cd,$arr_cd,$promCd,$fromdate,$todate,$fromdatedep,$todatedep,$acco,$excel,$gcDtForamt)
    {
         $atcore     =   $this->get('app.atcore');
         /*$rooms     =   '';
         $prom      =   '';
         $arrival   =   '';
         $depat     =   '';
         $fromd     =   '';
         $tod       =   '';*/
         
         $room          =   "'".$acco."%'";
         $promCds       =   "'".strtoupper($promCd)."'";
         $arr_cds       =   "'".strtoupper($arr_cd)."'";
         $dep_cds       =   "'".strtoupper($dep_cd)."'";                  
      
        $bfromdate = $fromdate!='' ? "'".str_replace("'", '', $fromdate)."'" : 'NULL';         
        
        $btodate = $todate!='' ? "'".str_replace("'", '', $todate)."'" : 'NULL'; 
         
        $arr_pt_id  = $this->Get_Pt_ID($arr_cd);
        $dep_pt_id  = $this->Get_Pt_ID($dep_cd);
        
        $arrId = $arr_pt_id !='' ? $arr_pt_id : 'NULL'; 
        $depId = $dep_pt_id !='' ? $dep_pt_id : 'NULL'; 
        
        $results = array();
        
        $sql = "SELECT distinct (
                             SELECT pt_cd FROM atcomres.ar_point WHERE 1 = 1 AND pt_id   =rs1.arr_pt_id) AS arr_airport		
                             ,q.res_id as booking_number
                             ,q.ser_seq
                             ,q.stk_tp AS Service_Category_Code
                             ,q.stk_tp_name as Service_Category_Name
                             ,q.bkg_tp as Package_Code
                             ,TO_CHAR(q.first_st_dt,'".$gcDtForamt."') AS Travel_Start_Date
                             ,q.n_pax as PAX
                             ,q.prc AS rev
                       FROM(
                           SELECT res.res_id
                                  ,rs.ser_id
                                  ,res.first_st_dt
                                  ,res.con_dt
                                  ,rs.dep_pt_id
                                  ,rs.arr_pt_id
                                  ,rs.ser_tp
                                  ,rs.stk_tp
                                  ,rs.ser_seq
                                  ,res.prom_id
                                  ,nvl( uc.name,rs.stk_tp )AS stk_tp_name
                                  ,res.bkg_tp
                                  ,res.n_pax
                                  ,rss.prc
                           FROM atcomres.ar_reservation res
                           LEFT JOIN atcomres.ar_resservice rs ON rs.res_id   =res.res_id
                           LEFT JOIN atcomres.ar_ressubservice rss ON rss.res_ser_id   =rs.res_ser_id
                           LEFT JOIN( SELECT cd,name FROM atcomres.ar_usercodes WHERE what_id IN(21,3127)) uc ON uc.cd   =rs.stk_tp
                           WHERE 1  = 1
                                 AND res.bkg_sts   ='BKG'
                                 AND rs.ser_sts    ='CON'
                                 AND rs.ser_tp     !='FEX'
                           UNION ALL
                           SELECT res.res_id
                                  ,rs.ser_id
                                  ,res.first_st_dt
                                  ,res.con_dt
                                  ,rs.dep_pt_id
                                  ,rs.arr_pt_id
                                  ,rs.ser_tp
                                  ,CASE WHEN rs.stk_tp='MEAL' THEN 'IFM' ELSE rs.stk_tp END AS stk_tp
                                  ,rs.ser_seq
                                  ,res.prom_id
                                  ,CASE WHEN rs.stk_tp='MEAL' THEN 'Inflight Meal' ELSE nvl( uc.name,rs.stk_tp ) END AS stk_tp_name
                                  ,res.bkg_tp
                                  ,res.n_pax
                                  ,rss.prc
                           FROM atcomres.ar_reservation res
                           LEFT JOIN atcomres.ar_resservice rs ON rs.res_id   =res.res_id
                           LEFT JOIN atcomres.ar_ressubservice rss ON rss.res_ser_id   =rs.res_ser_id
                           LEFT JOIN atcomres.ar_flightextra fe ON fe.flt_extra_id   =rs.ser_id
                           LEFT JOIN atcomres.ar_usercodes uc ON uc.user_cd_id   =fe.prc_cd_id
                           WHERE 1             =1
                                 AND res.bkg_sts   ='BKG'
                                 AND rs.ser_sts    ='CON'
                                 AND rs.ser_tp     ='FEX'
                       )q
                       LEFT JOIN( SELECT * FROM atcomres.ar_resservice WHERE ser_sts ='CON' AND ser_tp ='TRS' AND dir ='OUT') rs1 ON rs1.res_id   =q.res_id
                       LEFT JOIN atcomres.ar_promotion prm ON prm.prom_id   =q.prom_id
                       WHERE 1             =1
                             AND prm.prom_id IN( SELECT DISTINCT prom_id FROM atcomres.ar_promotion WHERE substr( cd,1,2 ) ='$promCd')
                             AND q.first_st_dt BETWEEN TO_DATE($fromdatedep,'DD-MON-YYYY') AND TO_DATE($todatedep,'DD-MON-YYYY')
                             AND ($bfromdate  IS NULL OR q.con_dt >= TO_DATE($bfromdate ,'DD-MON-YYYY'))
                             AND ($btodate  IS NULL OR q.con_dt <= TO_DATE($btodate ,'DD-MON-YYYY'))
                             AND   ($depId IS NULL OR  q.dep_pt_id = $depId)
                             AND   ($arrId IS NULL OR  q.arr_pt_id = $arrId)                              
                       ORDER BY 1,2,3";
        
                   $conn = $this->get('doctrine.dbal.atcore_connection');
                   $results = $conn->fetchAll($sql);

                   $cnt = count($results); 
                   
                   $multi_flights = [];
                   $search_name=array();
                   foreach ($results as $key=>$value) { 
                       $scode           = $value['SERVICE_CATEGORY_CODE'];
                       $SCPer    = $this->serviceCategotyPercentage($scode);
                       if($SCPer!='')
                       {
                            $results[$key]['COST'] = $value['REV']*($SCPer/100);                                                        
                       }
                       else
                       {
                           $results[$key]['COST'] = '';                           
                       }
                       if($value['REV']!='')
                       {
                        $results[$key]['VAT'] = $value['REV']*(0.13*0.25);
                       }                        
                    }
                    
                    $division_data   = array();
                    $sub_array       = array();
                    
                    foreach($results as $key=>$val)
                    {
                         $sub_array[] = array($val['ARR_AIRPORT'],$val['TRAVEL_START_DATE']);
                    } 
                    $array_unq = array_unique($sub_array,SORT_REGULAR); 
                    
                   foreach($array_unq as $key=>$value)
                   {                    
                        $Rev_Cost = 0;
                        $Cost_Val = 0;
                        $Vat_Val  = 0;
                        foreach($results as $kk=>$val)
                        {
                            if( ($value['0']==$val['ARR_AIRPORT']) && ($value['1']==$val['TRAVEL_START_DATE']))
                            {                                
                                $Rev_Cost  =  $Rev_Cost+$val['REV'];
                               if(isset($val['COST']) && $val['COST']!='')
                                   { $Cost_Val  =  $Cost_Val+$val['COST']; } 
                                else 
                                   { $Cost_Val = 0; }
                               $valResult = isset($val['VAT'])? $val['VAT']:'0';                                
                               $Vat_Val   =  $Vat_Val+$valResult; 
                               
                            }
                        }
                        $division_data[] = array("Travel Start Date"=>$value['1'],
                                                "Arrival Airport Code"=>$value['0'],
                                                "Sum of revenue"=>$Rev_Cost,
                                                "Sum of other cost"=>$Cost_Val,
                                                "Sum of travel vat"=>$Vat_Val);
                   }
                   
                    $array  =   array('Travel Start Date','Arrival Airport Code','Sum of revenue','Sum of other cost','Sum of travel vat');
                    
                    $division_data['count']=count($array);
                    if($cnt==0)
                    {
                        $division_data[]=array('Travel Start Date'=>" 0 ",
                                             'Arrival Airport Code'=>" 0 ",
                                             'Sum of revenue'=>" 0 ",                                            
                                             'Sum of other cost'=>" 0 ",
                                             'Sum of travel vat'=>" 0 "
                                            );
                    } 
                    
                    if(($excel) || ($excel=='')) {
                        return $division_data;
                    }
   }
   
    public function generate_R9($request,$dep_cd,$arr_cd,$promCd,$fromdate,$todate,$fromdatedep,$todatedep,$acco,$excel,$gcDtForamt,$AccCode,$AccCodes)
    {
         $atcore     =   $this->get('app.atcore');         
         
         $room          =   "'".$acco."%'";
         $promCds       =   "'".strtoupper($promCd)."'";
         $arr_cds       =   "'".strtoupper($arr_cd)."'";
         $dep_cds       =   "'".strtoupper($dep_cd)."'";                  
      
         $bfromdate = $fromdate!='' ? "'".str_replace("'", '', $fromdate)."'" : 'NULL';         
        
         $btodate = $todate!='' ? "'".str_replace("'", '', $todate)."'" : 'NULL'; 
         
         $arr_pt_id  = $this->Get_Pt_ID($arr_cd);
         $dep_pt_id  = $this->Get_Pt_ID($dep_cd);
        
         $arrId = $arr_pt_id !='' ? $arr_pt_id : 'NULL'; 
         $depId = $dep_pt_id !='' ? $dep_pt_id : 'NULL';        
         
         $sql = "SELECT distinct (
                             SELECT pt_cd FROM atcomres.ar_point WHERE 1 = 1 AND pt_id   =rs1.arr_pt_id) AS arr_airport		
                             ,q.res_id as booking_number
                             ,q.ser_seq
                             ,q.stk_tp AS Service_Category_Code
                             ,q.stk_tp_name as Service_Category_Name
                             ,q.bkg_tp as Package_Code
                             ,TO_CHAR(q.first_st_dt,'".$gcDtForamt."') AS Travel_Start_Date
                             ,q.n_pax as PAX
                             ,q.prc AS rev
                       FROM(
                           SELECT res.res_id
                                  ,rs.ser_id
                                  ,res.first_st_dt
                                  ,res.con_dt
                                  ,rs.dep_pt_id
                                  ,rs.arr_pt_id
                                  ,rs.ser_tp
                                  ,rs.stk_tp
                                  ,rs.ser_seq
                                  ,res.prom_id
                                  ,nvl( uc.name,rs.stk_tp )AS stk_tp_name                                  
                                  ,res.bkg_tp
                                  ,res.n_pax
                                  ,rss.prc
                           FROM atcomres.ar_reservation res
                           LEFT JOIN atcomres.ar_resservice rs ON rs.res_id   =res.res_id
                           LEFT JOIN atcomres.ar_ressubservice rss ON rss.res_ser_id   =rs.res_ser_id
                           LEFT JOIN( SELECT cd,name FROM atcomres.ar_usercodes WHERE what_id IN(21,3127)) uc ON uc.cd   =rs.stk_tp
                           WHERE 1  = 1
                                 AND res.bkg_sts   ='BKG'
                                 AND rs.ser_sts    ='CON'
                                 AND rs.ser_tp     !='FEX'
                           UNION ALL
                           SELECT res.res_id
                                  ,rs.ser_id
                                  ,res.first_st_dt
                                  ,res.con_dt
                                  ,rs.dep_pt_id
                                  ,rs.arr_pt_id
                                  ,rs.ser_tp
                                  ,CASE WHEN rs.stk_tp='MEAL' THEN 'IFM' ELSE NVL(uc.cd,rs.stk_tp) END AS stk_tp
                                  ,rs.ser_seq
                                  ,res.prom_id
                                  ,CASE WHEN rs.stk_tp='MEAL' THEN 'Inflight Meal' ELSE nvl( uc.name,rs.stk_tp ) END AS stk_tp_name
                                  ,res.bkg_tp
                                  ,res.n_pax
                                  ,rss.prc
                           FROM atcomres.ar_reservation res
                           LEFT JOIN atcomres.ar_resservice rs ON rs.res_id   =res.res_id
                           LEFT JOIN atcomres.ar_ressubservice rss ON rss.res_ser_id   =rs.res_ser_id
                           LEFT JOIN atcomres.ar_flightextra fe ON fe.flt_extra_id   =rs.ser_id
                           LEFT JOIN atcomres.ar_usercodes uc ON uc.user_cd_id   =fe.prc_cd_id
                           WHERE 1             =1
                                 AND res.bkg_sts   ='BKG'
                                 AND rs.ser_sts    ='CON'
                                 AND rs.ser_tp     ='FEX'
                       )q
                       LEFT JOIN( SELECT * FROM atcomres.ar_resservice WHERE ser_sts ='CON' AND ser_tp ='TRS' AND dir ='OUT') rs1 ON rs1.res_id   =q.res_id
                       LEFT JOIN( SELECT * FROM atcomres.ar_resservice WHERE ser_sts ='CON' AND ser_tp ='ACC') rs2 ON rs2.res_id = q.res_id  
                        LEFT JOIN atcomres.ar_sellstatic ss ON ss.sell_stc_id = rs2.ser_id 
                        LEFT JOIN atcomres.ar_staticstock stk ON ss.stc_stk_id = stk.stc_stk_id
                        LEFT JOIN atcomres.ar_promotion prm ON prm.prom_id   =q.prom_id
                       WHERE 1 = 1
                             AND prm.prom_id IN( SELECT DISTINCT prom_id FROM atcomres.ar_promotion WHERE substr( cd,1,2 ) ='$promCd')
                             AND q.first_st_dt BETWEEN TO_DATE($fromdatedep,'DD-MON-YYYY') AND TO_DATE($todatedep,'DD-MON-YYYY')
                             AND ($bfromdate  IS NULL OR q.con_dt >= TO_DATE($bfromdate ,'DD-MON-YYYY'))
                             AND ($btodate  IS NULL OR q.con_dt <= TO_DATE($btodate ,'DD-MON-YYYY'))
                             AND ($depId IS NULL OR  q.dep_pt_id = $depId)
                             AND ($arrId IS NULL OR  q.arr_pt_id = $arrId) 
                             AND ($AccCodes IS NULL OR stk.cd IN ($AccCode))
                       ORDER BY 1,2,3";
   
                   $conn    = $this->get('doctrine.dbal.atcore_connection');
                   $results = $conn->fetchAll($sql);  
                   foreach($results as $key=>$value)
                   {
                       $scode           = $value['SERVICE_CATEGORY_CODE'];
                       $SCPer    = $this->serviceCategotyPercentage($scode);
                       if($SCPer!='')
                       {
                            $results[$key]['COST'] = $value['REV']*($SCPer/100);                                                        
                       }
                       else
                       {
                           $results[$key]['COST'] = '0';                           
                       }
                       if($value['REV']!='')
                       {
                        $results[$key]['VAT'] = $value['REV']*(0.13*0.25);
                       }
                   }
                   
                   $cnt = count($results);
                   
                   $multi_flights = [];
                   $search_name=array();

                   foreach ($results as $result) {   
                       $resVat = isset($results['0']['VAT'])?$results['0']['VAT']:'';
                       $booking_num = " ".$result['BOOKING_NUMBER'];
                        $search_name[]=array(
                                            'Arrival Airport Code'=>$result['ARR_AIRPORT'],                                            
                                            'Booking Number'=>$booking_num,
                                            'Service Category Code'=>$result['SERVICE_CATEGORY_CODE'],
                                            'Service Category Name'=>$result['SERVICE_CATEGORY_NAME'],
                                            'Package Code'=>$result['PACKAGE_CODE'],                            
                                            'Departure Date'=>$result['TRAVEL_START_DATE'],                                            
                                            'PAX'=>$result['PAX'],                                             
                                            'Revenue'=>$result['REV'],
                                            'Other Cost'=>$result['COST'],                                            
                                            );                        
                   }

                   $array=array('Arrival Airport Code','Booking Number','Service Category Code','Service Category Name',
                                 'Package Code','Departure Date','PAX','Revenue','Other Cost');
                    
                   $search_name['count']=count($array);
                   if($cnt==0)
                   {
                        $search_name[]=array('Arrival Airport Code'=>'0',                                            
                                            'Booking Number'=>'0',
                                            'Service Category Code'=>'0',
                                            'Service Category Name'=>'0',
                                            'Package Code'=>'0',                            
                                            'Departure Date'=>'0',                                            
                                            'PAX'=>'0',                                             
                                            'Revenue'=>'0',
                                            'Other Cost'=>'0'                                            
                                            );
                    }

                   if(($excel) || ($excel=='')) {                        
                        return $search_name;
                   }
   }   
   
    public function serviceCategotyPercentage($scode)
    {
        $sql = "SELECT percentage FROM `cost` where servicecode='".$scode."'";
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();            
        $user_stmt = $doctrine->getEntityManager() 
                      ->getConnection()
                        ->prepare($sql);
        $user_stmt->execute();    
        $data = $user_stmt->fetchAll(); 
        if(count($data))
        {
          return $data['0']['percentage'];
        }
        else
        {
            return ;
        }
    }
    
    public function generate_R17_2($request,$dep_cd,$arr_cd,$promCd,$fromdate,$todate,$fromdatedep,$todatedep,$acco,$excel,$gcDtForamt,$AccCode,$AccCodes)
    {
        $atcore    =   $this->get('app.atcore');
        
        $promCds       =   "'".strtoupper($promCd)."'";
        $arr_cds       =   "'".strtoupper($arr_cd)."'";
        $dep_cds       =   "'".strtoupper($dep_cd)."'";         
         
       
        $bfromdate = $fromdate!='' ? "'".str_replace("'", '', $fromdate)."'" : 'NULL';                 
        $btodate = $todate!='' ? "'".str_replace("'", '', $todate)."'" : 'NULL'; 
        
        $arr_pt_id  = $this->Get_Pt_ID($arr_cd);
        $dep_pt_id  = $this->Get_Pt_ID($dep_cd);
        
        $arrId = $arr_pt_id !='' ? $arr_pt_id : 'NULL'; 
        $depId = $dep_pt_id !='' ? $dep_pt_id : 'NULL'; 
        
                    $sql =  "SELECT DISTINCT 
                                            res.res_id AS bkg_num
                                            ,TO_CHAR(res.con_dt,'".$gcDtForamt."') AS bkg_dt
                                            ,TO_CHAR(res.first_st_dt,'".$gcDtForamt."') AS dep_dt
                                            ,(SELECT pt_cd FROM atcomres.ar_point WHERE pt_tp ='AIR' AND pt_id = rs1.dep_pt_id) AS dep_pt_cd
                                            ,(SELECT pt_cd FROM atcomres.ar_point WHERE pt_tp ='AIR' AND pt_id   =rs1.arr_pt_id)AS arr_pt_cd
                                            ,cty3.name         AS resort
                                            ,stk.name          AS hotel
                                            ,stk.cd            AS accom                                            
                                            ,CASE WHEN length( prm.cd ) > 2 AND substr( prm.cd,3,4 )IN('DP','AD','TP')THEN  prm.cd
                                                ELSE substr( prm.cd,1,2 ) 
                                            END                AS prom_cd
                                            ,ofn.name          AS agt_grp
                                            ,ofn.cd            AS agt_cd
                                            ,res.n_pax         AS n_pax
                                            ,res.sell_prc      AS rev
                                            ,NULL              AS commrev
                                            ,res.agt_com       AS comm
                                            ,res.agt_vat       AS vat
                                            ,res.agt_com+res.agt_vat AS commvat
                                            ,'0%' AS percnt
                                            ,res.prof_ex_vat-( res.agt_com+res.agt_vat ) AS profit
                            FROM atcomres.ar_staticstock stk
                            LEFT JOIN atcomres.ar_sellstatic ss ON ss.stc_stk_id   =stk.stc_stk_id
                            LEFT JOIN atcomres.ar_resservice rs ON rs.ser_id   =ss.sell_stc_id
                            LEFT JOIN atcomres.ar_reservation res ON res.res_id   =rs.res_id
                            LEFT JOIN atcomres.ar_price prc ON prc.res_ser_id   =rs.res_ser_id
                            LEFT JOIN atcomres.ar_point cty3 ON cty3.pt_id   =stk.cty3_pt_id
                            LEFT JOIN atcomres.ar_promotion prm ON prm.prom_id   =res.prom_id
                            LEFT JOIN atcomres.ar_agent agt ON agt.agt_id   =res.agt_id
                            LEFT JOIN atcomres.ar_officename ofn ON ofn.off_name_id   =agt.off_name_id
                            LEFT JOIN( SELECT * FROM atcomres.ar_resservice WHERE ser_sts ='CON' AND ser_tp ='TRS' AND dir ='OUT') rs1 ON rs1.res_id = res.res_id
                            WHERE 1             =1
                                  AND res.bkg_sts   ='BKG'
                                  AND rs.ser_sts    ='CON'
                                  AND rs.ser_tp     ='ACC'
                                  AND prm.prom_id IN( SELECT DISTINCT prom_id FROM atcomres.ar_promotion WHERE substr( cd,1,2 ) ='$promCd'
                                  AND res.first_st_dt BETWEEN TO_DATE($fromdatedep,'DD-MON-YYYY') AND TO_DATE($todatedep,'DD-MON-YYYY')
                                  AND ($bfromdate IS NULL OR res.con_dt >= TO_DATE($bfromdate ,'DD-MON-YYYY'))
                                  AND ($btodate IS NULL OR res.con_dt < TO_DATE($btodate,'DD-MON-YYYY'))
                                  AND ($depId IS NULL OR  rs.dep_pt_id = $depId)
                                  AND ($arrId IS NULL OR  rs.arr_pt_id = $arrId)
                                  AND ($AccCodes IS NULL OR  stk.cd IN ($AccCode))
                    ) ORDER BY 2,3";

                  $conn = $this->get('doctrine.dbal.atcore_connection');
                  $results = $conn->fetchAll($sql);                  
                  
                  $multi_flights = [];
                  $search_name=array();
                  foreach ($results as $result) {
                      
                        $resOfComRev = $result['COMMREV']!=''?$result['COMMREV']:'0';
                        $search_name[]=array('Booking Number'=>" ".$result['BKG_NUM'],
                                             'Booking Date'=>$result['BKG_DT'],
                                             'Departure Date'=>$result['DEP_DT'],
                                             'Departure Airport Code'=>$result['DEP_PT_CD'],
                                             'Arrival Airport Code'=>$result['ARR_PT_CD'], 
                                             'Resort'=>$result['RESORT'],
                                             'Tour Operator'=>$result['PROM_CD'],                                                                                      
                                             'Agent Group'=>$result['AGT_GRP'],
                                             'Agent Code'=>" ".$result['AGT_CD'],
                                             'PAX'=>$result['N_PAX'],                                             
                                             'Rev'=>$result['REV'],                    
                                             'Comm Rev'=>$resOfComRev,
                                             'Comm'=>$result['COMM'],
                                             'VAT'=>$result['VAT'],
                                             'Comm+VAT'=>$result['COMMVAT'],
                                             'Percent'=>$result['PERCNT'],
                                             'Profit'=>$result['PROFIT']
                            );
                    }

                    $array=array('Booking Number','Booking Date','Departure Date','Departure Airport Code','Arrival Airport Code','Resort','Tour Operator','Agent Group','Agent Code','PAX','Rev','Com Rev','Com','VAT','Comm+VAT','Percent','Profit');
                    if(count($search_name)!=0)
                    {                    
                        $search_name['count']=count($array);
                    }
                    else
                    {                        
                        $search_name[]=array('Booking Number'=>'0',
                                             'Booking Date'=>'0',
                                             'Departure Date'=>'0',
                                             'Departure Airport Code'=>'0',
                                             'Arrival Airport Code'=>'0', 
                                             'Resort'=>'0',
                                             'Tour Operator'=>'0',            
                                             'Agent Group'=>'0',
                                             'Agent Code'=>'0',
                                             'PAX'=>'0',                                             
                                             'Rev'=>'0',                    
                                             'Comm Rev'=>'0',
                                             'Comm'=>'0',
                                             'VAT'=>'0',
                                             'Comm+VAT'=>'0',
                                             'Percent'=>'0',
                                             'Profit'=>'0');
                        
                        $search_name['count']=count($array);
                    }             
                    if(($excel) || ($excel=='')) { return $search_name; }
    }      
    
    public function generate_Agent($request,$dep_cd,$arr_cd,$promCd,$fromdate,$todate,$fromdatedep,$todatedep,$acco,$excel,$gcDtForamt,$AccCode,$AccCodes)
    {
        $atcore    =   $this->get('app.atcore');
         
        /*$rooms     =   '';
        $prom      =   '';
        $arrival   =   '';
        $depat     =   '';
        $fromd     =   '';
        $tod       =   '';
        
        $promCds       =   "'".strtoupper($promCd)."'";
        $arr_cds       =   "'".strtoupper($arr_cd)."'";
        $dep_cds       =   "'".strtoupper($dep_cd)."'";         */
         
        $bfromdate = $fromdate!='' ? "'".str_replace("'", '', $fromdate)."'" : 'NULL';                 
        $btodate = $todate!='' ? "'".str_replace("'", '', $todate)."'" : 'NULL'; 
        
        $arr_pt_id  = $this->Get_Pt_ID($arr_cd);
        $dep_pt_id  = $this->Get_Pt_ID($dep_cd);
        
        $arrId = $arr_pt_id !='' ? $arr_pt_id : 'NULL'; 
        $depId = $dep_pt_id !='' ? $dep_pt_id : 'NULL';
        
        
        $sql = "select dep_dt as DEPARTURE_DATE,arr_pt_cd as ARRIVAL,sum(comm) as COMMSUM
                        from(SELECT DISTINCT 
                                        res.res_id AS bkg_num
                                        ,TO_CHAR(res.con_dt,'".$gcDtForamt."') AS bkg_dt
                                        ,TO_CHAR(res.first_st_dt,'".$gcDtForamt."') AS dep_dt
                                        ,(SELECT pt_cd FROM atcomres.ar_point WHERE pt_tp ='AIR' AND pt_id = rs1.dep_pt_id) AS dep_pt_cd
                                        ,(SELECT pt_cd FROM atcomres.ar_point WHERE pt_tp ='AIR' AND pt_id   =rs1.arr_pt_id)AS arr_pt_cd
                                        ,cty3.name         AS resort
                                        ,stk.name          AS hotel
                                        ,stk.cd            AS accom
                                        --to chose DP promotion 
                                        ,CASE WHEN length( prm.cd ) > 2 AND substr( prm.cd,3,4 )IN('DP','AD','TP')THEN  prm.cd
                                            ELSE substr( prm.cd,1,2 ) 
                                        END                AS prom_cd
                                        ,ofn.name          AS agt_grp
                                        ,ofn.cd            AS agt_cd
                                        ,res.n_pax         AS n_pax
                                        ,res.sell_prc      AS rev
                                        ,NULL              AS commrev
                                        ,res.agt_com       AS comm
                                        ,res.agt_vat       AS vat
                                        ,res.agt_com+res.agt_vat AS commvat
                                        ,' %' AS percnt
                                        ,res.prof_ex_vat-( res.agt_com+res.agt_vat ) AS profit
                        FROM atcomres.ar_staticstock stk
                        LEFT JOIN atcomres.ar_sellstatic ss ON ss.stc_stk_id   =stk.stc_stk_id
                        LEFT JOIN atcomres.ar_resservice rs ON rs.ser_id   =ss.sell_stc_id
                        LEFT JOIN atcomres.ar_reservation res ON res.res_id   =rs.res_id
                        LEFT JOIN atcomres.ar_price prc ON prc.res_ser_id   =rs.res_ser_id
                        LEFT JOIN atcomres.ar_point cty3 ON cty3.pt_id   =stk.cty3_pt_id
                        LEFT JOIN atcomres.ar_promotion prm ON prm.prom_id   =res.prom_id
                        LEFT JOIN atcomres.ar_agent agt ON agt.agt_id   =res.agt_id
                        LEFT JOIN atcomres.ar_officename ofn ON ofn.off_name_id   =agt.off_name_id
                        LEFT JOIN( SELECT * FROM atcomres.ar_resservice WHERE ser_sts ='CON' AND ser_tp ='TRS' AND dir ='OUT') rs1 ON rs1.res_id = res.res_id
                        WHERE 1             =1
                              AND res.bkg_sts   ='BKG'
                              AND rs.ser_sts    ='CON'
                              AND rs.ser_tp     ='ACC'
                              AND prm.prom_id IN( SELECT DISTINCT prom_id FROM atcomres.ar_promotion WHERE substr( cd,1,2 ) ='$promCd'
                              AND res.first_st_dt BETWEEN TO_DATE($fromdatedep,'DD-MON-YYYY') AND TO_DATE($todatedep,'DD-MON-YYYY')
                              AND ($bfromdate IS NULL OR res.con_dt >= TO_DATE($bfromdate ,'DD-MON-YYYY'))
                              AND ($btodate IS NULL OR res.con_dt < TO_DATE($btodate,'DD-MON-YYYY'))
                              AND ($depId IS NULL OR  rs.dep_pt_id = $depId)
                              AND ($arrId IS NULL OR  rs.arr_pt_id = $arrId)
                              AND ($AccCodes IS NULL OR  stk.cd IN ($AccCode))
                        ) ORDER BY 2,3) X group by dep_dt,arr_pt_cd";         
        
                    $conn = $this->get('doctrine.dbal.atcore_connection');
                    $results = $conn->fetchAll($sql);
                    $cnt = count($results);
                    
                    $multi_flights = [];
                    $search_name=array();
                    foreach ($results as $result) {
                        $ddate = explode(" ",$result['DEPARTURE_DATE']);
                        
                        $dates      =   date_create($ddate['0']);
                        $dkDate     =   date_format($dates,"m/d/Y");
                        
                        $search_name[]=array('Departure DT'=>$dkDate,
                                             'Arrival AIR'=>$result['ARRIVAL'],
                                             'Profit'=>$result['COMMSUM']);                        
                    }

                    $array=array('Departure DT','Arrival AIR','Profit');                    
                    $search_name['count']=count($array);
                    
                    if($cnt==0)
                    {
                         $search_name[]=array('Departure DT'=>" 0 ",
                                              'Arrival AIR'=>" 0 ",
                                              'Profit'=>" 0 ");
                    }
                    
                    if(($excel) || ($excel=='')) {
                        return $search_name;
                    }
    }
    
    public function generate_flight_data($request,$dep_cd,$arr_cd,$promCd,$fromdate,$todate,$fromdatedep,$todatedep,$acco,$excel,$gcDtForamt,$AccCode,$AccCodes)
    {
         $atcore    =   $this->get('app.atcore');

        $promCds   =   strtoupper($promCd);
        $arr_cds   =   strtoupper($arr_cd);
        $dep_cds   =   strtoupper($dep_cd);
        
        $fromdatedept = "'".str_replace("'", '', $fromdatedep)."'";
        $todatedept   = "'".str_replace("'", '', $todatedep)."'";
        
        $bfromdate = $fromdate!='' ? "'".str_replace("'", '', $fromdate)."'" : 'NULL'; 
        $btodate = $todate!='' ? "'".str_replace("'", '', $todate)."'" : 'NULL'; 
                
        /*$depat      =   '';
        $rooms      =   '';
        $tod        =   '';
        $prom       =   '';
        $AccmCd     =   '';*/
        
        $arr_pt_id  = $this->Get_Pt_ID($arr_cd);
        $dep_pt_id  = $this->Get_Pt_ID($dep_cd);
        
        $arrId = $arr_pt_id !='' ? $arr_pt_id : "NULL"; 
        $depId = $dep_pt_id !='' ? $dep_pt_id : "NULL";       
        
        $sql="SELECT DISTINCT TO_CHAR(res.first_st_dt,'".$gcDtForamt."')AS Date_of_Departure
                ,res.res_id as Atcom_Booking_No
                ,(SELECT pt_cd FROM atcomres.ar_point WHERE pt_id = rs.arr_pt_id AND pt_tp='AIR')AS arrival_airport
                ,SUBSTR(prm.cd,1,2) AS BRAND
                ,prm.name as PACKAGE_NAME
                ,res.n_pax  as PASSENGERS
                ,rss.stk_prc as  SOURCE_CURRENCY_AMOUNT
                ,stk.cd      AS accomcd
                ,stk.name    AS accomname
                ,cty3.name   AS resort
            FROM atcomres.ar_reservation res
            LEFT JOIN atcomres.ar_resservice rs ON rs.res_id   =res.res_id
            LEFT JOIN atcomres.ar_ressubservice rss ON rss.res_ser_id   =rs.res_ser_id
            LEFT JOIN atcomres.ar_promotion prm ON prm.prom_id   =res.prom_id
            LEFT JOIN( SELECT * FROM atcomres.ar_resservice WHERE ser_tp ='ACC')rs1 ON rs1.res_id=res.res_id AND rs1.ser_sts = rs.ser_sts
            LEFT JOIN atcomres.ar_sellstatic ss ON ss.sell_stc_id   =rs1.ser_id 
            LEFT JOIN atcomres.ar_staticstock stk ON ss.stc_stk_id   =stk.stc_stk_id
            LEFT JOIN atcomres.ar_point cty3 ON cty3.pt_id   =stk.cty3_pt_id
            WHERE res.bkg_sts    ='BKG'
                  AND rs.ser_sts ='CON'
                  AND rs.ser_tp  ='TRS'
                  AND rs.dir     ='OUT'
                  AND prm.prom_id IN(SELECT DISTINCT prom_id FROM atcomres.ar_promotion WHERE substr( cd,1,2 ) ='SR')
                  AND res.first_st_dt BETWEEN TO_DATE($fromdatedept,'DD-MON-YYYY') AND TO_DATE($todatedept,'DD-MON-YYYY')
                  AND ($bfromdate IS NULL OR res.con_dt >= TO_DATE($bfromdate,'DD-MON-YYYY'))
                  AND ($btodate IS NULL OR res.con_dt <= TO_DATE($btodate,'DD-MON-YYYY'))
                  AND( $depId IS NULL OR rs.dep_pt_id   =$depId )
                  AND( $arrId IS NULL OR rs.arr_pt_id   =$arrId )
                  AND ($AccCodes IS NULL OR  stk.cd IN ($AccCode))";          

                  $conn = $this->get('doctrine.dbal.atcore_connection');
                  $results = $conn->fetchAll($sql); 
                  
                  $CNT = count($results); 
                  $multi_flights = [];
                  $search_name=array();
                  foreach ($results as $result) {
                        $search_name[]=array('Booking Number'=>" ".$result['ATCOM_BOOKING_NO'],
                                             'Departure Date'=>$result['DATE_OF_DEPARTURE'],
                                             'Arrival Airport Code'=>$result['ARRIVAL_AIRPORT'],
                                             'Brand'=>$result['BRAND'],
                                             'Package Name'=>$result['PACKAGE_NAME'],
                                             'PAX'=>$result['PASSENGERS'],
                                             'Source Currency Amount'=>$result['SOURCE_CURRENCY_AMOUNT'] );
                    }

                    $array=array('Booking Number','Departure Date','Arrival Airport Code','Brand','Package Name','PAX','Source Currency Amount');
                    
                    $search_name['count']=count($array);
                    if($CNT==0)
                    {
                        $search_name[]  =   array('Booking Number'=>'0',
                                                  'Departure Date'=>'0',
                                                  'Arrival Airport Code'=>'0',
                                                  'Brand'=>'0',
                                                  'Package Name'=>'0',
                                                  'PAX'=>'0',
                                                  'Source Currency Amount'=>'0');
                    }                    
                    if(($excel) || ($excel=='')) { return $search_name; }
    }
    
    public function generate_Flight($request,$dep_cd,$arr_cd,$promCd,$fromdate,$todate,$fromdatedep,$todatedep,$acco,$excel,$gcDtForamt,$AccCode,$AccCodes)
    {
         $atcore    =   $this->get('app.atcore');
         
         $promCds   =   strtoupper($promCd);
        $arr_cds   =   strtoupper($arr_cd);
        $dep_cds   =   strtoupper($dep_cd);
        
        $fromdatedept = "'".str_replace("'", '', $fromdatedep)."'";
        $todatedept   = "'".str_replace("'", '', $todatedep)."'";
        
        $bfromdate = $fromdate!='' ? "'".str_replace("'", '', $fromdate)."'" : 'NULL'; 
        $btodate = $todate!='' ? "'".str_replace("'", '', $todate)."'" : 'NULL'; 
                
        /*$depat      =   '';
        $rooms      =   '';
        $tod        =   '';
        $prom       =   '';
        $AccmCd     =   '';*/
        
        $arr_pt_id  = $this->Get_Pt_ID($arr_cd);
        $dep_pt_id  = $this->Get_Pt_ID($dep_cd);
        
        $arrId = $arr_pt_id !='' ? $arr_pt_id : "NULL"; 
        $depId = $dep_pt_id !='' ? $dep_pt_id : "NULL";    
   
        
         $sql="select date_of_departure,arrival_airport,sum(source_currency_amount) as SOURCE_CURRENCY_AMOUNT  from (
                        SELECT DISTINCT
                            TO_CHAR(res.first_st_dt,'".$gcDtForamt."') AS date_of_departure,
                            res.res_id AS atcom_booking_no,
                            (
                                SELECT
                                    pt_cd
                                FROM
                                    atcomres.ar_point
                                WHERE
                                    pt_id = rs.arr_pt_id
                                    AND pt_tp = 'AIR'
                            ) AS arrival_airport,
                            substr(prm.cd,1,2) AS brand,
                            prm.name AS package_name,
                            res.n_pax AS passengers,
                            rss.stk_prc AS source_currency_amount,
                            stk.cd AS accomcd,
                            stk.name AS accomname,
                            cty3.name AS resort
                        FROM
                            atcomres.ar_reservation res
                            LEFT JOIN atcomres.ar_resservice rs ON rs.res_id = res.res_id
                            LEFT JOIN atcomres.ar_ressubservice rss ON rss.res_ser_id = rs.res_ser_id
                            LEFT JOIN atcomres.ar_promotion prm ON prm.prom_id = res.prom_id
                            LEFT JOIN (
                                SELECT
                                    *
                                FROM
                                    atcomres.ar_resservice
                                WHERE
                                    ser_tp = 'ACC'
                            ) rs1 ON rs1.res_id = res.res_id
                                     AND rs1.ser_sts = rs.ser_sts
                            LEFT JOIN atcomres.ar_sellstatic ss ON ss.sell_stc_id = rs1.ser_id
                            LEFT JOIN atcomres.ar_staticstock stk ON ss.stc_stk_id = stk.stc_stk_id
                            LEFT JOIN atcomres.ar_point cty3 ON cty3.pt_id = stk.cty3_pt_id                        
                        WHERE res.bkg_sts    ='BKG'
                                AND rs.ser_sts ='CON'
                                AND rs.ser_tp  ='TRS'
                                AND rs.dir     ='OUT'
                                AND prm.prom_id IN(SELECT DISTINCT prom_id FROM atcomres.ar_promotion WHERE substr( cd,1,2 ) ='SR')
                                AND res.first_st_dt BETWEEN TO_DATE($fromdatedept,'DD-MON-YYYY') AND TO_DATE($todatedept,'DD-MON-YYYY')
                                AND ($bfromdate IS NULL OR res.con_dt >= TO_DATE($bfromdate,'DD-MON-YYYY'))
                                AND ($btodate IS NULL OR res.con_dt <= TO_DATE($btodate,'DD-MON-YYYY'))
                                AND( $depId IS NULL OR rs.dep_pt_id   =$depId )
                                AND( $arrId IS NULL OR rs.arr_pt_id   =$arrId )
                                AND ($AccCodes IS NULL OR  stk.cd IN ($AccCode)))"
                    ." X group by date_of_departure,arrival_airport";              
                  $conn = $this->get('doctrine.dbal.atcore_connection');
                  $results = $conn->fetchAll($sql);
                  
                  $cnt = count($results);
                  
                  $multi_flights = [];
                  $search_name=array();

                  foreach ($results as $result) {
                        $search_name[]=array(                            
                                             'Atcom booking date'=>$result['DATE_OF_DEPARTURE'],
                                             'Airpot Codes'=>$result['ARRIVAL_AIRPORT'],
                                             'Source Currency Amount'=>$result['SOURCE_CURRENCY_AMOUNT']                                             
                                            );
                    }

                    $array=array('Atcom booking date','Airpot Codes','Source Currency Amount');                                        
                    if($cnt==0)
                    {
                        $search_name[]  =   array('Atcom booking date'=>" 0 ",
                                                  'Airpot Codes'=>" 0 ",
                                                  'Source Currency Amount'=>" 0 ");
                    }
                    $search_name['count']=count($array);
                    if(($excel) || ($excel=='')) { return $search_name; }
    }  
    
    public function createExcel_new($request, $search_name,$DepDateFrToList,$brand_title)
    {  
        $Total_sheets   = count($search_name);
        $phpExcelObject = $this->get('phpexcel')->createPHPExcelObject();
        $k=1;      

        foreach($search_name as $key=>$value)
        {   
            if($k<=$Total_sheets) {
                
                $phpExcelObject->createSheet($k);            
                $SheetName  =  $search_name[$key];
                $key_title  =  $key;
                $xls_col    =  'A';
                $xls_row    =  1;
                $l          =  1;
                $c          =  $search_name[$key]['count'];   
                    
                //Flight Pivot Table
                if($key === 'Flight') {
                    $xls_col    = 'A';
                    $xls_row    =  2;
                    $arrival    = array();
                    $arr_dates  = array();    
                    
                    if($search_name[$key]!=='count')
                    {
                        foreach($search_name[$key] as $key=>$value)
                        {   
                            if($value['Airpot Codes']!='')
                            {
                                $arrival[] = $value['Airpot Codes'];
                            }
                            if($value['Atcom booking date']!='')
                            {
                                $arr_dates[$key] = $value['Atcom booking date'];                                                        
                            }
                        }
                            
                        $phpExcelObject->getActiveSheet()->getStyle($xls_col . $xls_row )->getFont()->setBold(true);
                        $flight_dates = array_unique($arr_dates);                        
                        sort($flight_dates);                       
                        $dCnt = count($flight_dates);
                        
                        $flight_arr = array_unique($arrival);                    
                        sort($flight_arr);    

                        $l  =   0;
                        $p  =   2;
                        $cc = $dCnt;

                        $d_str_char =   66;                    

                        $phpExcelObject->getActiveSheet()->setCellValue("A" . '2', "Arrival Airport Codes");                                                                     

                        for($i=$d_str_char;$i<$cc+$d_str_char;$i++)
                        {                               
                            $str = chr($i);
                                $phpExcelObject->getActiveSheet()->getStyle($str . $p)->getFont()->setBold(true);
                                $phpExcelObject->getActiveSheet()->setCellValue($str . $p, $flight_dates[$l]);                                                                     
                            $l++;                        
                        }  
                        $str = chr($i);                                         
                        $phpExcelObject->getActiveSheet()->getStyle($str . "2")->getFont()->setBold(true);
                        $phpExcelObject->getActiveSheet()->setCellValue($str . "2", 'Grand Total'); 

                        foreach ($flight_arr as $key1 => $value) {                                       
                            $xls_col = 'A';
                            $xls_row++;                                
                                $a = $value;
                                $phpExcelObject->getActiveSheet()->setCellValue($xls_col++ . $xls_row, $a);
                                $l++;                                                           
                        }

                        $xls_row=$xls_row+1;
                        $xls_col='A'; 
                        $phpExcelObject->getActiveSheet()->setCellValue($xls_col . $xls_row, "Grand Total");                                                                     
                        $phpExcelObject->getActiveSheet()->getStyle($xls_col . $xls_row)->getFont()->setBold(true);                        
                        
                        //Display all records                  
                        foreach($flight_dates as $keyy=>$values) 
                        {   
                            $arr_multi_sub = array();						
                            foreach($search_name['Flight'] as $key=>$value)
                            { 	
                                if($value['Atcom booking date']==$values)
                                {
                                    $kk=0;										
                                    foreach($flight_arr as $key_air=>$value_air)
                                    {
                                        if(($value['Airpot Codes']==$value_air) && ($value['Atcom booking date']==$values) )
                                        {				
                                                $arr_multi_sub[$value_air] = array($value['Source Currency Amount']);						
                                                $kk++;
                                        }					   			
                                    }                                    
                                } 
                            }                                                        
                            $keys   = array_keys($arr_multi_sub);		                            
                            $keyss  = array_values($flight_arr);		

                            $l          =   0;
                            $d          =   $d_str_char;                            

                            $xls_row    =   3;                                
                            $sum = 0;
                            $cnt = 1; 
                            foreach($keyss as $ss=>$vv)
                            {                                    
                                $xls_col = chr($d_str_char);                                    
                                if(in_array($vv,$keys))
                                {   
                                    $phpExcelObject->getActiveSheet()->setCellValue($xls_col . $xls_row, $arr_multi_sub[$vv][0]);                                        
                                    
                                    if(isset($arr_multi_sub[$vv][0]) && $arr_multi_sub[$vv][0]!=0)
                                    {
                                        $sum = $sum + $arr_multi_sub[$vv][0];                                
                                    }
                                    else
                                    {
                                        $sum = $sum + 0;                                
                                    }
                                }
                                else
                                {
                                    $phpExcelObject->getActiveSheet()->setCellValue($xls_col . $xls_row, '0');                                                                            
                                }

                                $xls_col++;
                                $xls_row++;
                            }

                            $chr_sym = ord($xls_col);
                            $chr_sym=$chr_sym-1;                                
                            $xls_col = chr($chr_sym);
                            
                            $phpExcelObject->getActiveSheet()->setCellValue($xls_col.$xls_row, $sum);    
                            $phpExcelObject->getActiveSheet()->getStyle($xls_col . $xls_row)->getFont()->setBold(true);
                            $d_str_char ++;
                        }
                        $str = chr(66);                    
                        $last_char = $cc+66;
                        $be_last_char = $cc+66-1;

                        $blast_str = chr($be_last_char);
                        $last_str = chr($last_char);                    
                        for($loc=0;$loc<count($flight_arr);$loc++)
                        {
                            $locnum = $loc+3;                                      
                            $phpExcelObject->getActiveSheet()->setCellValue($last_str.$locnum,"=SUM($str".$locnum.":".$blast_str.$locnum.')');                        
                            $phpExcelObject->getActiveSheet()->getStyle($last_str.$locnum)->getFont()->setBold(true);
                        }                    
                        $phpExcelObject->getActiveSheet()->setTitle($key_title);                             

                        $lastTot = $locnum+1;
                        
                        $phpExcelObject->getActiveSheet()->setCellValue($last_str.$lastTot,"=SUM(".$last_str."3".":".$last_str.$locnum.')');
                        $phpExcelObject->getActiveSheet()->getStyle($last_str.$lastTot)->getFont()->setBold(true);                        
                        //---------------------------------------------------------------------------
                        
                        
                        if(!isset($search_name['PAX']))                        
                        {
                            $search_name['PAX'] = $search_name['GC']['0']['PAX'];
                        }
                        if(isset($search_name['PAX']))
                        {
                            $RevenueArrival = array();
                            $ReveunueArrivalDates = array();
                            
                            if($search_name['PAX']!=='count')
                            {                            
                                    foreach($search_name['PAX'] as $key=>$value) 
                                    {                        
                                            if($value['Arrival Airport']!='')
                                            {
                                                            $RevenueArrival[] = $value['Arrival Airport'];
                                            }
                                            if($key!='count')
                                            {
                                                            $ReveunueArrivalDates[$key] = $value['Departure Date'];                        
                                            }
                                    }
                                    
                                    $xls_row = '20';
                                    $xls_col = 'A';
                                    $d_str_char =   66;
                                    
                                    $phpExcelObject->getActiveSheet()->getStyle('A' . $xls_row . ':' . $xls_col . $xls_row)->getFont()->setBold(true);
                                    $DatesList = array_unique($ReveunueArrivalDates);     
                                    sort($DatesList);
                                   
                                    $dCnt = count($DatesList);
                                    $ArrivalCodes = array_unique($RevenueArrival);                    
                                    sort($ArrivalCodes);  
                                    
                                    
                                    $phpExcelObject->getActiveSheet()->getStyle('A' . $xls_row . ':' . $xls_col . $xls_row)->getFont()->setBold(true);

                                    $PP  =   20;
                                    $phpExcelObject->getActiveSheet()->setCellValue("A" . '19', "Sum of Empty Legs");      
                                    $phpExcelObject->getActiveSheet()->getStyle("A" . '19')->getFont()->setBold(true);                                         
                                    $phpExcelObject->getActiveSheet()->setCellValue("A" . '20', "Arrival Airport Codes");      
                                    $strChar = 66;
                                    
                                    $l = 0;
                                    
                                    for($i=66;$i<=$dCnt+65;$i++){       	
                                            $str        = chr($strChar);
                                            $lastChar   = chr($strChar+2);
                                            $phpExcelObject->getActiveSheet()->getStyle($str . $PP)->getFont()->setBold(true);                                         

                                            if(isset($DatesList[$l]))
                                            {                                            
                                                $phpExcelObject->getActiveSheet()->setCellValue($str . $PP, $DatesList[$l]);                        
                                            }
                                            $l++;
                                            $strChar = $strChar + 1;
                                    }
                                    //echo "<pre>";
                                    //var_dump($phpExcelObject);
                                    //exit;
                                        
                                    $lChar = chr($strChar-1)."20";
                                    $phpExcelObject->getActiveSheet()->getStyle($lChar)->getFont()->setBold(true);
                                    $phpExcelObject->getActiveSheet()->setCellValue($lChar, "Grand Total");       

                                    $xls_row    =  20;
                                    foreach ($ArrivalCodes as $key1 => $value) {                                       
                                                            $xls_col = 'A';
                                                            $xls_row++;                                
                                                            $a = $value;                                
                                                            $phpExcelObject->getActiveSheet()->setCellValue($xls_col++ . $xls_row, $a);                                                                                                   
                                    }

                                    $airChar = $xls_row+1;     
                                    $phpExcelObject->getActiveSheet()->getStyle($airChar)->getFont()->setBold(true);
                                    $phpExcelObject->getActiveSheet()->setCellValue("A".$airChar, "Grand Total"); 
                                    
                                    foreach($DatesList as $keyy=>$values) 
                                    {   
                                            $PAX_arr_multi_sub = array();						
                                            foreach($search_name['PAX'] as $key=>$value)
                                            { 
                                                    if($value['Departure Date']==$values)
                                                    {
                                                            $kk=0;										
                                                            foreach($ArrivalCodes as $key_air=>$value_air)
                                                            {
                                                                    if(($value['Arrival Airport']==$value_air) && ($value['Departure Date']==$values) )
                                                                    {				
                                                                            $PAX_arr_multi_sub[$value_air] = array($value['Empty_legs']);						
                                                                            $kk++;
                                                                    }					   			
                                                            }                                    
                                                    }
                                            }

                                            $keys   = array_keys($PAX_arr_multi_sub);		                            
                                            $keyss  = array_values($ArrivalCodes);		

                                            $l          =   0;
                                            $d          =   66;                            

                                            $xls_row    =   4;                         

                                            $cnt    = 21;                                

                                            $sum    = 0;                           

                                            foreach($keyss as $ss=>$vv)
                                            {   
                                                    $xls_col = chr($d_str_char);                                    

                                                    $f  =   chr($d_str_char).$cnt;                                

                                                    if(in_array($vv,$keys))
                                                    {
                                                            $phpExcelObject->getActiveSheet()->setCellValue($f, $PAX_arr_multi_sub[$vv][0]);                                        
                                                            $sum = $sum + $PAX_arr_multi_sub[$vv][0];
                                                    }
                                                    else
                                                    {
                                                            $phpExcelObject->getActiveSheet()->setCellValue($f, '0');                                        

                                                    }
                                                    $cnt++;                                                                       
                                            }      

                                            $tSum   = chr($d_str_char).$cnt;
                                            $phpExcelObject->getActiveSheet()->setCellValue($tSum,$sum);              

                                            $d_str_char = $d_str_char + 1;                                
                                    }
                                    
                                    $last_char = chr($strChar-1);
                                    $str = chr(66);                                                
                                    $be_last_char = $strChar-1;  

                                    $blast_str = chr($be_last_char-1);

                                    $lop = 2;                            
                                    for($loc=0;$loc<count($ArrivalCodes);$loc++)
                                    {                                          
                                            $locnum = $lop+19;                        
                                                    $phpExcelObject->getActiveSheet()->setCellValue($last_char.$locnum,"=SUM($str".$locnum.":".$blast_str.$locnum.')');                        
                                                    $phpExcelObject->getActiveSheet()->getStyle($last_char.$locnum)->getFont()->setBold(true);
                                            $lop++;                                     
                                    }

                            $phpExcelObject->getActiveSheet()->setCellValue($last_char.$airChar,"=SUM(".$last_char."21".":".$last_char.$locnum.')'); 
                            $phpExcelObject->getActiveSheet()->getStyle($last_char.$airChar)->getFont()->setBold(true);
                                    
                            }
                        }
                        $phpExcelObject->setActiveSheetIndex($k);
                    }
                }
                //AGENT Pivot Table
                if($key === 'Agent') {                        
                    $xls_col    = 'A';
                    $xls_row    =  2;
                    $arrival    = array();
                    $arr_dates  = array();    

                    if($search_name[$key]!=='count')
                    {
                        foreach($search_name[$key] as $key=>$value)
                        {                        
                            if($value['Arrival AIR']!='')
                            {
                                $arrival[] = $value['Arrival AIR'];
                            }
                            if($key!='count')
                            {
                                $arr_dates[$key] = $value['Departure DT'];                        
                            }
                        }	               
                        $phpExcelObject->getActiveSheet()->getStyle('A' . $xls_row . ':' . $xls_col . $xls_row)->getFont()->setBold(true);
                        $ss = array_unique($arr_dates);
                        sort($ss);
                        $dCnt = count($ss);
                        $dd = array_unique($arrival);                    
                        sort($dd);    

                        $l  =   0;
                        $p  =   2;
                        $cc = $dCnt;

                        $d_str_char =   66;                    

                        $phpExcelObject->getActiveSheet()->setCellValue("A" . '2', "Arrival Airport Codes");                                                                     

                        for($i=$d_str_char;$i<$cc+$d_str_char;$i++)
                        {                               
                            $str = chr($i);
                                $phpExcelObject->getActiveSheet()->getStyle($str . $p)->getFont()->setBold(true);
                                $phpExcelObject->getActiveSheet()->setCellValue($str . $p, $ss[$l]);                                                                     
                            $l++;                        
                        }  
                        $str = chr($i);          
                        $phpExcelObject->getActiveSheet()->getStyle($str . "2")->getFont()->setBold(true);
                        $phpExcelObject->getActiveSheet()->setCellValue($str . "2", 'Grand Total'); 

                        foreach ($dd as $key1 => $value) {                                       
                            $xls_col = 'A';
                            $xls_row++;                                
                                $a = $value;
                                $phpExcelObject->getActiveSheet()->setCellValue($xls_col++ . $xls_row, $a);
                                $l++;                                                           
                        }

                        $xls_row=$xls_row+1;
                        $xls_col='A'; 
                        $phpExcelObject->getActiveSheet()->getStyle($xls_row)->getFont()->setBold(true);
                        $phpExcelObject->getActiveSheet()->setCellValue($xls_col . $xls_row, "Grand Total");                                                                     

                        //Display all records                  
                        foreach($ss as $keyy=>$values) 
                        {   
                            $arr_multi_sub = array();						
                            foreach($search_name['Agent'] as $key=>$value)
                            { 	
                                if($value['Departure DT']==$values)
                                {
                                    $kk=0;										
                                    foreach($dd as $key_air=>$value_air)
                                    {
                                        if(($value['Arrival AIR']==$value_air) && ($value['Departure DT']==$values) )
                                        {				
                                                $arr_multi_sub[$value_air] = array($value['Profit']);						
                                                $kk++;
                                        }					   			
                                    }                                    
                                } 
                            }                                                        
                            $keys   = array_keys($arr_multi_sub);		                            
                            $keyss  = array_values($dd);		

                            $l          =   0;
                            $d          =   $d_str_char;                            

                            $xls_row    =   3;                                
                            $sum = 0;
                            $cnt = 1; 
                            foreach($keyss as $ss=>$vv)
                            {                                    
                                $xls_col = chr($d_str_char);                                    
                                if(in_array($vv,$keys))
                                {   
                                    $phpExcelObject->getActiveSheet()->setCellValue($xls_col . $xls_row, $arr_multi_sub[$vv][0]);                                        
                                    $sum = $sum + $arr_multi_sub[$vv][0];                                
                                }
                                else
                                {
                                    $phpExcelObject->getActiveSheet()->setCellValue($xls_col . $xls_row, '0');                                                                            
                                }

                                $xls_col++;
                                $xls_row++;
                            }

                            $chr_sym = ord($xls_col);
                            $chr_sym=$chr_sym-1;                                
                            $xls_col = chr($chr_sym);

                            $phpExcelObject->getActiveSheet()->setCellValue($xls_col . $xls_row, $sum);                                            
                            $d_str_char ++;
                        }
                        $str = chr(66);                    
                        $last_char = $cc+66;
                        $be_last_char = $cc+66-1;

                        $blast_str = chr($be_last_char);
                        $last_str = chr($last_char);                    
                        for($loc=0;$loc<count($dd);$loc++)
                        {
                            $locnum = $loc+3;                        
                            $phpExcelObject->getActiveSheet()->setCellValue($last_str.$locnum,"=SUM($str".$locnum.":".$blast_str.$locnum.')');                        
                            $phpExcelObject->getActiveSheet()->getStyle($last_str.$locnum)->getFont()->setBold(true);
                        }                    
                        $phpExcelObject->getActiveSheet()->setTitle($key_title);                             

                        $lastTot = $locnum+1;

                        $phpExcelObject->getActiveSheet()->setCellValue($last_str.$lastTot,"=SUM(".$last_str."3".":".$last_str.$locnum.')');
                        $phpExcelObject->getActiveSheet()->getStyle($last_str.$lastTot)->getFont()->setBold(true);

                        $phpExcelObject->setActiveSheetIndex($k);
                    }
                }
                //Hotel Pivot Table
                if($key === 'Hotel'){
                    $xls_col    = 'A'; 
                    $xls_row = 2;
                    $hotel_arrival = array();
                    $Hotel_arr_dates = array();    
                    if($search_name[$key]!=='count')
                    {                        
                        foreach($search_name[$key] as $key=>$value) 
                        {                        
                            if($value['DESTNATION_CODE']!='')
                            {
                                $hotel_arrival[] = $value['DESTNATION_CODE'];
                            }
                            if($key!='count')
                            {
                                $Hotel_arr_dates[$key] = $value['ARRIVAL_DATE'];                        
                            }
                        }	               
                        $phpExcelObject->getActiveSheet()->getStyle('A' . $xls_row . ':' . $xls_col . $xls_row)->getFont()->setBold(true);
                        $ss = array_unique($Hotel_arr_dates);
                        sort($ss);
                        $dCnt = count($ss);
                        $dd = array_unique($hotel_arrival);                    
                        sort($dd);    

                        $l  =   0;
                        $p  =   2;
                        $cc = $dCnt;

                        $d_str_char =   66;                    

                        $phpExcelObject->getActiveSheet()->setCellValue("A" . '2', "Destination Airport Codes");                                                                     

                        for($i=66;$i<$cc+66;$i++)
                        {                               
                            $str = chr($i);
                                $phpExcelObject->getActiveSheet()->getStyle($str . $p)->getFont()->setBold(true);
                                $phpExcelObject->getActiveSheet()->setCellValue($str . $p, $ss[$l]);                                                                     
                            $l++;                        
                        }  
                        $str = chr($i);                                         
                        $phpExcelObject->getActiveSheet()->getStyle($str . "2")->getFont()->setBold(true);
                        $phpExcelObject->getActiveSheet()->setCellValue($str . "2", 'Grand Total'); 

                        foreach ($dd as $key1 => $value) {                                       
                            $xls_col = 'A';
                            $xls_row++;                                
                                $a = $value;
                                $phpExcelObject->getActiveSheet()->setCellValue($xls_col++ . $xls_row, $a);                                
                                $l++;                                                           
                        }

                        $xls_row    =   $xls_row+1;
                        $xls_col='A'; 
                        $phpExcelObject->getActiveSheet()->getStyle($xls_col . $xls_row)->getFont()->setBold(true);
                        $phpExcelObject->getActiveSheet()->setCellValue($xls_col . $xls_row, "Grand Total");                                                                     

                    //Display all records                                      
                        foreach($ss as $keyy=>$values) 
                        {   
                            $hotel_arr_multi_sub = array();						
                            foreach($search_name['Hotel'] as $key=>$value)
                            { 	
                                if($value['ARRIVAL_DATE']==$values)
                                {
                                    $kk=0;										
                                    foreach($dd as $key_air=>$value_air)
                                    {
                                        if(($value['DESTNATION_CODE']==$value_air) && ($value['ARRIVAL_DATE']==$values) )
                                        {				
                                                $hotel_arr_multi_sub[$value_air] = array($value['COST_LOCAL_CURRENCY']);						
                                                $kk++;
                                        }					   			
                                    }                                    
                                } 
                            }                                                        
                            $keys   = array_keys($hotel_arr_multi_sub);		                            
                            $keyss  = array_values($dd);		

                            $l          =   0;
                            $d          =   66;                            

                            $xls_row    =   3;                                
                            $sum = 0;
                            $cnt = 1;
                            foreach($keyss as $ss=>$vv)
                            {                                    
                                $xls_col = chr($d_str_char);                                    
                                if(in_array($vv,$keys))
                                {   
                                    $phpExcelObject->getActiveSheet()->setCellValue($xls_col . $xls_row, $hotel_arr_multi_sub[$vv][0]);                                        
                                    $sum = $sum + $hotel_arr_multi_sub[$vv][0];
                                }
                                else
                                {
                                    $phpExcelObject->getActiveSheet()->setCellValue($xls_col . $xls_row, '0');                                                                            
                                }

                                $xls_col++;
                                $xls_row++;
                            }

                            $chr_sym    =   ord($xls_col);
                            $chr_sym    =   $chr_sym-1;                                
                            $xls_col    =   chr($chr_sym);
                            
                            $phpExcelObject->getActiveSheet()->setCellValue($xls_col . $xls_row, $sum); 
                            $phpExcelObject->getActiveSheet()->getStyle($xls_col . $xls_row)->getFont()->setBold(true);
                            $d_str_char ++;
                        }
                        $str            = chr(66);                    
                        $last_char      = $cc+66;
                        $be_last_char   = $cc+66-1;

                        $blast_str  = chr($be_last_char);
                        $last_str   = chr($last_char);                    
                        for($loc=0;$loc<count($dd);$loc++)
                        {
                            $locnum = $loc+3;                        
                            $phpExcelObject->getActiveSheet()->setCellValue($last_str.$locnum,"=SUM($str".$locnum.":".$blast_str.$locnum.')');                        
                            $phpExcelObject->getActiveSheet()->getStyle($last_str.$locnum)->getFont()->setBold(true);
                        }    

                        $phpExcelObject->getActiveSheet()->setTitle($key_title);

                        $lastTot = $locnum+1;


                        $phpExcelObject->getActiveSheet()->setCellValue($last_str.$lastTot,"=SUM(".$last_str."3".":".$last_str.$locnum.')');
                        $phpExcelObject->getActiveSheet()->getStyle($last_str.$lastTot)->getFont()->setBold(true);
                    }
                } 
                //REVENUE Pivot Table
                if($key === 'Revenue'){   
                    $xls_col    = 'A'; 
                    $xls_row    =  2;
                    $RevenueArrival         = array();
                    $ReveunueArrivalDates   = array();    

                    if($search_name[$key]!=='count')
                    {                            
                        foreach($search_name[$key] as $key=>$value) 
                        {                        
                            if($value['Arrival Airport Code']!='')
                            {
                                    $RevenueArrival[] = $value['Arrival Airport Code'];
                            }
                            if($key!='count')
                            {
                                    $ReveunueArrivalDates[$key] = $value['Travel Start Date'];                        
                            }
                        }	
                        
                        $phpExcelObject->getActiveSheet()->getStyle('A' . $xls_row . ':' . $xls_col . $xls_row)->getFont()->setBold(true);
                        
                        $DatesList = array_unique($ReveunueArrivalDates);                            
                        sort($DatesList);

                        $dCnt = count($DatesList);
                        $ArrivalCodes = array_unique($RevenueArrival);                    
                        sort($ArrivalCodes);  

                        $l  =   0;
                        $p  =   2;
                        $cc = $dCnt;

                        $d_str_char =   66;                                   

                        $phpExcelObject->getActiveSheet()->setCellValue("A" . '3', "Arrival Airport Codes");                                
                        $phpExcelObject->getActiveSheet()->getStyle("A" . '3')->getFont()->setBold(true);
                        $nextC = 0;
                        
                        $strChar = 66;
                        for($i=66;$i<=$cc+65;$i++)
                        {       
                            $str        = chr($strChar);
                            $lastChar   = chr($strChar+2);

                            $MergeCells = $str.$p.":".$lastChar.$p;                                       

                            $f  =   chr($strChar)."3";
                            $f1 =   chr($strChar+1)."3";
                            $f2 =   chr($strChar+2)."3";
                            
                            $phpExcelObject->getActiveSheet()->mergeCells($MergeCells);     
                            $phpExcelObject->getActiveSheet()->getStyle($str . $p)->getFont()->setBold(true);
                            $phpExcelObject->getActiveSheet()->setCellValue($str . $p, $DatesList[$l]);
                            
                            $phpExcelObject->getActiveSheet()->setCellValue($f, "Sum of Revenue");                                   
                            $phpExcelObject->getActiveSheet()->getStyle($f)->getFont()->setBold(true);
                            
                            $phpExcelObject->getActiveSheet()->setCellValue($f1, "Sum of Other Cost");       
                            $phpExcelObject->getActiveSheet()->getStyle($f1)->getFont()->setBold(true);
                            
                            $phpExcelObject->getActiveSheet()->setCellValue($f2, "Sum of Travel VAT");       
                            $phpExcelObject->getActiveSheet()->getStyle($f2)->getFont()->setBold(true);

                            $l++;
                            $strChar = $strChar + 3;
                        } 
                        
                        //Extract Total colums
                        $totCol = $dCnt+3;                        
                        
                        $lChar = chr($strChar)."3";
                        $lChar1 = chr(++$strChar)."3";
                        $lChar2 = chr(++$strChar)."3";
                        $phpExcelObject->getActiveSheet()->getStyle($lChar)->getFont()->setBold(true);
                        $phpExcelObject->getActiveSheet()->setCellValue($lChar, "Total Sum of Revenue");       
                        
                        $phpExcelObject->getActiveSheet()->setCellValue($lChar1, "Total Sum of Other Cost"); 
                        $phpExcelObject->getActiveSheet()->getStyle($lChar1)->getFont()->setBold(true);
                        
                        $phpExcelObject->getActiveSheet()->setCellValue($lChar2, "Total Sum of Travel VAT"); 
                        $phpExcelObject->getActiveSheet()->getStyle($lChar2)->getFont()->setBold(true);

                        
                        $xls_row    =  3;
                        foreach ($ArrivalCodes as $key1 => $value) {                                       
                                    $xls_col = 'A';
                                    $a       = $value;
                                    $xls_row++;

                                    $phpExcelObject->getActiveSheet()->setCellValue($xls_col++ . $xls_row, $a);                                                                                                   
                        }
                        $airChar = $xls_row+1;                  
                        $phpExcelObject->getActiveSheet()->getStyle($airChar)->getFont()->setBold(true);
                        $phpExcelObject->getActiveSheet()->setCellValue("A".$airChar, "Grand Total");       

                        foreach($DatesList as $keyy=>$values) 
                        {   
                            $hotel_arr_multi_sub = array();						
                            foreach($search_name['Revenue'] as $key=>$value)
                            { 
                                if($value['Travel Start Date']==$values)
                                {
                                    $kk=0;										
                                    foreach($ArrivalCodes as $key_air=>$value_air)
                                    {
                                        if(($value['Arrival Airport Code']==$value_air) && ($value['Travel Start Date']==$values) )
                                        {				
                                            $hotel_arr_multi_sub[$value_air] = array($value['Sum of revenue'],$value['Sum of other cost'],$value['Sum of travel vat']);						
                                            $kk++;
                                        }					   			
                                    }                                    
                                }
                            }

                            $keys   = array_keys($hotel_arr_multi_sub);		                            
                            $keyss  = array_values($ArrivalCodes);		

                            $l          =   0;
                            $d          =   66;                            

                            $xls_row    =   4;                                

                            $cnt    = 4;                                

                            $sum    = 0;
                            $sum1   = 0;
                            $sum2   = 0;

                            foreach($keyss as $ss=>$vv)
                            {   
                                $xls_col = chr($d_str_char);                                    
                                //Three colum positions
                                $f  =   chr($d_str_char).$cnt;                                    
                                $f1 =   chr($d_str_char+1).$cnt;                                    
                                $f2 =   chr($d_str_char+2).$cnt;

                                if(in_array($vv,$keys))
                                {
                                    $phpExcelObject->getActiveSheet()->setCellValue($f, $hotel_arr_multi_sub[$vv][0]);                                        
                                    $sum = $sum + $hotel_arr_multi_sub[$vv][0];

                                    $phpExcelObject->getActiveSheet()->setCellValue($f1, $hotel_arr_multi_sub[$vv][1]);                                        
                                    $sum1 = $sum1 + $hotel_arr_multi_sub[$vv][1];

                                    $phpExcelObject->getActiveSheet()->setCellValue($f2, $hotel_arr_multi_sub[$vv][2]);                                            
                                    $sum2 = $sum2 + $hotel_arr_multi_sub[$vv][2];
                                }
                                else
                                {
                                    $phpExcelObject->getActiveSheet()->setCellValue($f, '0');                                        

                                    $phpExcelObject->getActiveSheet()->setCellValue($f1, '0');                                        

                                    $phpExcelObject->getActiveSheet()->setCellValue($f2, '0');                                            
                                }
                                $cnt++;                                                                       
                            }      

                            $tSum   = chr($d_str_char).$cnt;
                            $tSum1  = chr($d_str_char+1).$cnt;
                            $tSum2  = chr($d_str_char+2).$cnt;                                

                            //Individual Column wise sum
                            $phpExcelObject->getActiveSheet()->setCellValue($tSum,$sum);                                                                                                                    
                            $phpExcelObject->getActiveSheet()->setCellValue($tSum1,$sum1);                                                                                                            
                            $phpExcelObject->getActiveSheet()->setCellValue($tSum2,$sum2);                                            

                            $d_str_char = $d_str_char + 3;                                
                        } 

                        $last_char = chr($strChar);                            
                                                                        
                        $be_last_char = $strChar-1;  

                        $blast_str = chr($be_last_char);
                            
                            for($CharStNum=66;$CharStNum<66+3;$CharStNum++)
                            {
                                $lop = 4; 
                                for($loc=0;$loc<count($ArrivalCodes);$loc++)
                                {                                
                                    $str = chr($CharStNum);
                                    $ar = array();
                                    for($dateList_cnt=0;$dateList_cnt<count($DatesList);$dateList_cnt++)
                                    {   
                                        $n = $str;             
                                        $n1 = ++$str;
                                        $n2 = ++$str;
                                        $n3 = ++$str;

                                        $ar[]= array($n.$lop,$n1.$lop,$n2.$lop,$n3.$lop);
                                    } 
                                    $concat = '';
                                    $LATCHAR = $str.$lop;
                                
                                    foreach($ar as $arr_key=>$arr_val)
                                    {
                                        $concat.=$arr_val['0'].",";
                                    }                                
                                    $calString = rtrim($concat,',');
                                    
                                    $phpExcelObject->getActiveSheet()->setCellValue($LATCHAR,"=SUM(".$calString.')');                                                                                    
                                    $phpExcelObject->getActiveSheet()->getStyle($LATCHAR)->getFont()->setBold(true);
                                    $lop++;                                                                    
                                }    
                                $lTot = $str.(4+count($ArrivalCodes));
                                $sumCalPos = $str."4".":".$str.(count($ArrivalCodes)+3);
                                
                                $phpExcelObject->getActiveSheet()->setCellValue($lTot,"=SUM(".$sumCalPos.')'); 
                                $phpExcelObject->getActiveSheet()->getStyle($lTot)->getFont()->setBold(true);
                            }                        

                        //$phpExcelObject->getActiveSheet()->setCellValue($last_char.$airChar,"=SUM(".$last_char."4".":".$last_char.$locnum.')');                                                    
                    }
                }
                //PAX Pivot Table
                if($key === 'PAX'){
                    $xls_col    = 'A'; 
                    $xls_row    =  2;
                    $RevenueArrival         = array();
                    $ReveunueArrivalDates   = array();    

                    if($search_name[$key]!=='count')
                    {                            
                        foreach($search_name[$key] as $key=>$value) 
                        {                        
                            if($value['Arrival Airport']!='')
                            {
                                    $RevenueArrival[] = $value['Arrival Airport'];
                            }
                            if($key!='count')
                            {
                                    $ReveunueArrivalDates[$key] = $value['Departure Date'];                        
                            }
                        }	               

                        $phpExcelObject->getActiveSheet()->getStyle('A' . $xls_row . ':' . $xls_col . $xls_row)->getFont()->setBold(true);
                        $DatesList = array_unique($ReveunueArrivalDates);                            
                        sort($DatesList);

                        $dCnt = count($DatesList);
                        $ArrivalCodes = array_unique($RevenueArrival);                    
                        sort($ArrivalCodes);  

                        $l  =   0;
                        $p  =   2;
                        $cc = $dCnt;

                        $d_str_char =   66;                                   

                        $phpExcelObject->getActiveSheet()->setCellValue("A" . '3', "Arrival Airport Codes");  
                        $phpExcelObject->getActiveSheet()->getStyle("A" . '3')->getFont()->setBold(true);
                        $nextC = 0;

                        $strChar = 66;
                        for($i=66;$i<=$cc+65;$i++)
                        {       
                            $str        = chr($strChar);
                            $lastChar   = chr($strChar+2);

                            $MergeCells = $str.$p.":".$lastChar.$p;                                       

                            $f =  chr($strChar)."3";
                            $f1 = chr($strChar+1)."3";
                            $f2 = chr($strChar+2)."3";

                            $phpExcelObject->getActiveSheet()->mergeCells($MergeCells);     
                            $phpExcelObject->getActiveSheet()->setCellValue($str . $p, $DatesList[$l]);
                            $phpExcelObject->getActiveSheet()->getStyle($str . $p)->getFont()->setBold(true);
                            $phpExcelObject->getActiveSheet()->setCellValue($f, "Sum of Handlings fee/Complaints");       
                            $phpExcelObject->getActiveSheet()->getStyle($f)->getFont()->setBold(true);
                            $phpExcelObject->getActiveSheet()->setCellValue($f1, "Sum of Out Alt Seats");       
                            $phpExcelObject->getActiveSheet()->getStyle($f1)->getFont()->setBold(true);
                            $phpExcelObject->getActiveSheet()->setCellValue($f2, "Sum of Out Sold Seats");
                            $phpExcelObject->getActiveSheet()->getStyle($f2)->getFont()->setBold(true);

                            $l++;
                            $strChar = $strChar + 3;
                        } 

                        $lChar = chr($strChar)."3";
                        $lChar1 = chr(++$strChar)."3";
                        $lChar2 = chr(++$strChar)."3";
                        
                        $phpExcelObject->getActiveSheet()->getStyle($lChar)->getFont()->setBold(true);
                        $phpExcelObject->getActiveSheet()->setCellValue($lChar, "Total Sum of Out Alt Seats");       
                        
                        $phpExcelObject->getActiveSheet()->setCellValue($lChar1, "Total Sum of Out Sold Seats"); 
                        $phpExcelObject->getActiveSheet()->getStyle($lChar1)->getFont()->setBold(true);
                        
                        $phpExcelObject->getActiveSheet()->setCellValue($lChar2, "Total Sum of Handlings fee/Complaints"); 
                        $phpExcelObject->getActiveSheet()->getStyle($lChar2)->getFont()->setBold(true);
            

                        $xls_row    =  3;
                        foreach ($ArrivalCodes as $key1 => $value) {                                       
                                    $xls_col = 'A';
                                    $xls_row++;                                
                                    $a = $value;
                                        $phpExcelObject->getActiveSheet()->setCellValue($xls_col++ . $xls_row, $a);                                                                                                   
                        }
                        $airChar = $xls_row+1;       
                        $phpExcelObject->getActiveSheet()->getStyle($airChar)->getFont()->setBold(true);
                        $phpExcelObject->getActiveSheet()->setCellValue("A".$airChar, "Grand Total");       

                        foreach($DatesList as $keyy=>$values) 
                        {   
                            $hotel_arr_multi_sub = array();						
                            foreach($search_name['PAX'] as $key=>$value)
                            { 
                                if($value['Departure Date']==$values)
                                {
                                    $kk=0;										
                                    foreach($ArrivalCodes as $key_air=>$value_air)
                                    {
                                        if(($value['Arrival Airport']==$value_air) && ($value['Departure Date']==$values) )
                                        {				
                                            $hotel_arr_multi_sub[$value_air] = array($value['Handlings fee/Complaints'],$value['Out Alt Seats'],$value['Out Sold Seats']);						
                                            $kk++;
                                        }					   			
                                    }                                    
                                }
                            }

                            $keys   = array_keys($hotel_arr_multi_sub);		                            
                            $keyss  = array_values($ArrivalCodes);		

                            $l          =   0;
                            $d          =   66;                            

                            $xls_row    =   4;                         

                            $cnt    = 4;                                

                            $sum    = 0;
                            $sum1   = 0;
                            $sum2   = 0;

                            foreach($keyss as $ss=>$vv)
                            {   
                                $xls_col = chr($d_str_char);                                    

                                $f  =   chr($d_str_char).$cnt;

                                $f1 =   chr($d_str_char+1).$cnt;

                                $f2 =   chr($d_str_char+2).$cnt;

                                if(in_array($vv,$keys))
                                {
                                    $phpExcelObject->getActiveSheet()->setCellValue($f, $hotel_arr_multi_sub[$vv][0]);                                        
                                    $sum = $sum + $hotel_arr_multi_sub[$vv][0];

                                    $phpExcelObject->getActiveSheet()->setCellValue($f1, $hotel_arr_multi_sub[$vv][1]);                                        
                                    $sum1 = $sum1 + $hotel_arr_multi_sub[$vv][1];

                                    $phpExcelObject->getActiveSheet()->setCellValue($f2, $hotel_arr_multi_sub[$vv][2]);                                            
                                    $sum2 = $sum2 + $hotel_arr_multi_sub[$vv][2];
                                }
                                else
                                {
                                    $phpExcelObject->getActiveSheet()->setCellValue($f, '0');                                        

                                    $phpExcelObject->getActiveSheet()->setCellValue($f1, '0');                                        

                                    $phpExcelObject->getActiveSheet()->setCellValue($f2, '0');                                            
                                }
                                $cnt++;                                                                       
                            }      

                            $tSum   = chr($d_str_char).$cnt;
                            $tSum1  = chr($d_str_char+1).$cnt;
                            $tSum2  = chr($d_str_char+2).$cnt;                                

                            $phpExcelObject->getActiveSheet()->setCellValue($tSum,$sum);                                                                                    

                            $phpExcelObject->getActiveSheet()->setCellValue($tSum1,$sum1);                                                                            

                            $phpExcelObject->getActiveSheet()->setCellValue($tSum2,$sum2);                                            

                            $d_str_char = $d_str_char + 3;                                
                        } 

                        $last_char = chr($strChar);                            
                        $str = chr(66);                                                
                        $be_last_char = $strChar-1;  

                        $blast_str = chr($be_last_char);                        
                        
                        for($CharStNum=66;$CharStNum<66+3;$CharStNum++)
                        {
                            $lop = 4; 
                            for($loc=0;$loc<count($ArrivalCodes);$loc++)
                            {                                
                                $str = chr($CharStNum);
                                $ar = array();
                                for($dateList_cnt=0;$dateList_cnt<count($DatesList);$dateList_cnt++)
                                {   
                                    $n = $str;             
                                    $n1 = ++$str;
                                    $n2 = ++$str;
                                    $n3 = ++$str;

                                    $ar[]= array($n.$lop,$n1.$lop,$n2.$lop,$n3.$lop);
                                } 
                                $concat = '';
                                $LATCHAR = $str.$lop;

                                foreach($ar as $arr_key=>$arr_val)
                                {
                                    $concat.=$arr_val['0'].",";
                                }                                
                                $calString = rtrim($concat,',');
                                $phpExcelObject->getActiveSheet()->setCellValue($LATCHAR,"=SUM(".$calString.')');  
                                $phpExcelObject->getActiveSheet()->getStyle($LATCHAR)->getFont()->setBold(true);
                                $lop++;                                                                    
                            }    
                            $lTot = $str.(4+count($ArrivalCodes));
                            $sumCalPos = $str."4".":".$str.(count($ArrivalCodes)+3);
                            $phpExcelObject->getActiveSheet()->setCellValue($lTot,"=SUM(".$sumCalPos.')'); 
                            $phpExcelObject->getActiveSheet()->getStyle($lTot)->getFont()->setBold(true);
                        }                        
                    }                    
                }
                
                if($key === 'GC') {                
                    
                    $xls_col    = 'A'; 
                    $xls_row    =  5;                    
                    
                    $phpExcelObject->getActiveSheet()->getStyle('A' . $xls_row . ':' . $xls_col . $xls_row)->getFont()->setBold(true);
                    
                    $l  =   0;
                    $startRow =   5;
                    
                    $dCnt = count($search_name[$key]['1']['0']);                    
                    
                    $gc_str_char    =   67;
                    
                    $charArray = array(); 
                    
                    $phpExcelObject->getActiveSheet()->setCellValue('A2', $brand_title);
                    $phpExcelObject->getActiveSheet()->getStyle('A2')->getFont()->setBold(true)->setSize(16);
                    $phpExcelObject->getActiveSheet()->setCellValue('A4', "Departure From Date");                    
                    $phpExcelObject->getActiveSheet()->setCellValue('B4', $search_name[$key]['1']['0'][0]);                    
                                        
                    $num_pos ='6';                    
                    $p=5;
                    $allChar = array();
                    for ($j=0,$i = 'C'; $i !== 'ZZ'; $j++,$i++){
                        $allChar[]  = $i;
                        if(isset($search_name[$key]['1']['0'][$l]))
                        {
                            if($j%18==0)
                            {
                                $k_kick =   $i;
                                $k_kick_bold =   $i;
                                $charArray[] = $i;
                                
                                $phpExcelObject->getActiveSheet()->setCellValue($i . $p, $search_name[$key]['1']['0'][$l]);
                                $phpExcelObject->getActiveSheet()->getStyle($i . $p)->getFont()->setBold(true);
                                
                                $phpExcelObject->getActiveSheet()->setCellValue($k_kick.$num_pos, "Revenue");
                                $phpExcelObject->getActiveSheet()->getStyle($k_kick_bold.$num_pos )->getFont()->setBold(true);
                                $phpExcelObject->getActiveSheet()->setCellValue(++$k_kick.$num_pos, "Other cost");
                                $phpExcelObject->getActiveSheet()->getStyle(++$k_kick_bold.$num_pos)->getFont()->setBold(true);
                                $phpExcelObject->getActiveSheet()->setCellValue(++$k_kick.$num_pos, "Hotel");
                                $phpExcelObject->getActiveSheet()->getStyle(++$k_kick_bold.$num_pos)->getFont()->setBold(true);
                                $phpExcelObject->getActiveSheet()->setCellValue(++$k_kick.$num_pos, "Flight");
                                $phpExcelObject->getActiveSheet()->getStyle(++$k_kick_bold.$num_pos)->getFont()->setBold(true);
                                $phpExcelObject->getActiveSheet()->setCellValue(++$k_kick.$num_pos, "GC");                                
                                $phpExcelObject->getActiveSheet()->getStyle(++$k_kick_bold.$num_pos)->getFont()->setBold(true);
                                $phpExcelObject->getActiveSheet()->setCellValue(++$k_kick.$num_pos, "GC%"); 
                                $phpExcelObject->getActiveSheet()->getStyle(++$k_kick_bold.$num_pos)->getFont()->setBold(true);
                                $phpExcelObject->getActiveSheet()->setCellValue(++$k_kick.$num_pos, "Allotment"); 
                                $phpExcelObject->getActiveSheet()->getStyle(++$k_kick_bold.$num_pos)->getFont()->setBold(true);
                                $phpExcelObject->getActiveSheet()->setCellValue(++$k_kick.$num_pos, "Sold Seats"); 
                                $phpExcelObject->getActiveSheet()->getStyle(++$k_kick_bold.$num_pos)->getFont()->setBold(true);
                                $phpExcelObject->getActiveSheet()->setCellValue(++$k_kick.$num_pos, "Allotment%"); 
                                $phpExcelObject->getActiveSheet()->getStyle(++$k_kick_bold.$num_pos)->getFont()->setBold(true);
                                $phpExcelObject->getActiveSheet()->setCellValue(++$k_kick.$num_pos, "Sales Comm."); 
                                $phpExcelObject->getActiveSheet()->getStyle(++$k_kick_bold.$num_pos)->getFont()->setBold(true);
                                
                                $phpExcelObject->getActiveSheet()->setCellValue(++$k_kick.$num_pos, "Accrued delay etc."); 
                                $phpExcelObject->getActiveSheet()->getStyle(++$k_kick_bold.$num_pos)->getFont()->setBold(true);
                                
                                $phpExcelObject->getActiveSheet()->setCellValue(++$k_kick.$num_pos, "Destination costs"); 
                                $phpExcelObject->getActiveSheet()->getStyle(++$k_kick_bold.$num_pos)->getFont()->setBold(true);
                                
                                $phpExcelObject->getActiveSheet()->setCellValue(++$k_kick.$num_pos, "VAT EU dest."); 
                                $phpExcelObject->getActiveSheet()->getStyle(++$k_kick_bold.$num_pos)->getFont()->setBold(true);
                                
                                $phpExcelObject->getActiveSheet()->setCellValue(++$k_kick.$num_pos, "Net GC"); 
                                $phpExcelObject->getActiveSheet()->getStyle(++$k_kick_bold.$num_pos)->getFont()->setBold(true);
                                
                                $phpExcelObject->getActiveSheet()->setCellValue(++$k_kick.$num_pos, "GC/PAX"); 
                                $phpExcelObject->getActiveSheet()->getStyle(++$k_kick_bold.$num_pos)->getFont()->setBold(true);
                                
                                $phpExcelObject->getActiveSheet()->setCellValue(++$k_kick.$num_pos, "Rev/PAX"); 
                                $phpExcelObject->getActiveSheet()->getStyle(++$k_kick_bold.$num_pos)->getFont()->setBold(true);
                                
                                $phpExcelObject->getActiveSheet()->setCellValue(++$k_kick.$num_pos, "Hotel/PAX"); 
                                $phpExcelObject->getActiveSheet()->getStyle(++$k_kick_bold.$num_pos)->getFont()->setBold(true);
                                
                                $phpExcelObject->getActiveSheet()->setCellValue(++$k_kick.$num_pos, "Flight/PAX"); 
                                $phpExcelObject->getActiveSheet()->getStyle(++$k_kick_bold.$num_pos)->getFont()->setBold(true);
                                $l++;
                            }
                        }                        
                    }   
                    
                        $agent      = $charArray;
                        $revenue    = $charArray;
                        $hotel      = $charArray;
                        $pax        = $charArray;

                        //Airport Codes
                        $phpExcelObject->getActiveSheet()->setCellValue('A6', "Arrival Airport Codes");
                        $phpExcelObject->getActiveSheet()->getStyle('A6')->getFont()->setBold(true);
                        $phpExcelObject->getActiveSheet()->setCellValue('B6', " ");
                        
                        $arr = $num_pos+1;
                        foreach($search_name[$key]['1']['1'] as $arr_key=>$arr_val){
                                    $phpExcelObject->getActiveSheet()->setCellValue('A'.$arr, $arr_val); 
                                    $arr++;
                        }               
                        $phpExcelObject->getActiveSheet()->getStyle('A'.$arr)->getFont()->setBold(true);
                        $phpExcelObject->getActiveSheet()->setCellValue('A'.$arr, "SUM");

                        array_pop($search_name[$key]['0']['REVENUE']);                    
                        array_pop($search_name[$key]['0']['HOTEL']); 
                        array_pop($search_name[$key]['0']['AGENT']); 
                        array_pop($search_name[$key]['0']['PAX']);                     

                        $rev_rem_fields = array();

                        $hotel_gc_arr_multi_sub = array();
                        $flight_gc_arr_multi_sub = array();
                        $pax_gc_arr_multi_sub = array();
                        $agent_gc_arr_multi_sub = array();
                        $gc_arr_multi_sub = array();

                        foreach($search_name[$key]['1']['0'] as $keyy=>$values) {                        

                            if(isset($search_name[$key]['0']['REVENUE']) && isset($search_name[$key]['0']['AGENT']) && isset($search_name[$key]['0']['HOTEL']) && isset($search_name[$key]['0']['PAX']) ) 
                            {
                                //Get the Revenue Data
                                foreach($search_name[$key]['0']['REVENUE'] as $key1=>$value)
                                {	
                                    if(isset($search_name[$key]['1']['1']))
                                    {
                                        foreach($search_name[$key]['1']['1'] as $key_air=>$value_air)
                                        {
                                            if( ($value['Arrival Airport Code']==$value_air) && ($value['Travel Start Date']==$values) )
                                            {
                                                $gc_arr_multi_sub[$value_air] = array(
                                                                                $value['Travel Start Date'],
                                                                                $value['Arrival Airport Code'],
                                                                                $value['Sum of revenue'],
                                                                                $value['Sum of other cost'],
                                                                                $value['Sum of travel vat']);
                                            }
                                        }
                                    }
                                }                     

                                //Get the HOTEL Data
                                foreach($search_name[$key]['0']['HOTEL'] as $key1=>$hot_value)
                                {	
                                    if(isset($search_name[$key]['1']['1']))
                                    {
                                        foreach($search_name[$key]['1']['1'] as $hot_key_air=>$hot_air)
                                        {                                        
                                            if( ($hot_value['DESTNATION_CODE']==$hot_air) && ($hot_value['ARRIVAL_DATE']==$values) )
                                            {                                            
                                                $hotel_gc_arr_multi_sub[$hot_air] = array(
                                                                                $hot_value['ARRIVAL_DATE'],
                                                                                $hot_value['DESTNATION_CODE'],
                                                                                $hot_value['COST_LOCAL_CURRENCY']);                                                                            
                                            }
                                        }                                    
                                    }                                
                                }                           

                                //Get the pax data
                                foreach($search_name[$key]['0']['PAX'] as $key1=>$pax_value)
                                {	
                                    if(isset($search_name[$key]['1']['1']))
                                    {
                                        foreach($search_name[$key]['1']['1'] as $pax_key_air=>$pax_air)
                                        {   
                                            if( ($pax_value['Arrival Airport']==$pax_air) && ($pax_value['Departure Date']==$values) )
                                            {                                            
                                                $pax_gc_arr_multi_sub[$pax_air] = array(
                                                                                $pax_value['Departure Date'],
                                                                                $pax_value['Arrival Airport'],
                                                                                $pax_value['Handlings fee/Complaints'],
                                                                                $pax_value['Out Sold Seats'],
                                                                                $pax_value['Out Alt Seats'],
                                                                                $pax_value['Empty_legs']);                                                                            
                                            }
                                        }

                                    }                                
                                }                            
                                //Agent Data
                                foreach($search_name[$key]['0']['AGENT'] as $key1=>$agent_value)
                                {	
                                    if(isset($search_name[$key]['1']['1']))
                                    {
                                        foreach($search_name[$key]['1']['1'] as $pax_key_air=>$agent_air)
                                        {                                             
                                            if( ($agent_value['Arrival AIR']==$agent_air) && ($agent_value['Departure DT']==$values) )
                                            {                                            
                                                $agent_gc_arr_multi_sub[$agent_air] = array($agent_value['Departure DT'],																			
                                                                                            $agent_value['Arrival AIR'],
                                                                                            $agent_value['Profit']);                                                                            
                                            }
                                        }

                                    }                                
                                }                                        
                                
                                //Flight Data
                                foreach($search_name[$key]['0']['FLIGHT'] as $key1=>$flight_value)
                                {	
                                    if(isset($search_name[$key]['1']['1']))
                                    {
                                        foreach($search_name[$key]['1']['1'] as $flight_key_air=>$flight_air)
                                        {   
                                            if( ($flight_value['Airpot Codes']==$flight_air) && ($flight_value['Atcom booking date']==$values) )
                                            {                                            
                                                $flight_gc_arr_multi_sub[$flight_air] = array($flight_value['Atcom booking date'],																			
                                                                                              $flight_value['Airpot Codes'],
                                                                                              $flight_value['Source Currency Amount']);                                                                            
                                            }
                                        }                                    
                                    }                                
                                }                            
                                
                                //echo "<pre>";
                                //print_r($flight_gc_arr_multi_sub);
                                
                                 //Revenue Data                                
                                if(count($gc_arr_multi_sub)!=0)
                                {                                                   
                                    $keys   = array_keys($gc_arr_multi_sub);//Revenue Dates

                                    $arrival_codes_list  = array_values($search_name[$key]['1']['1']);

                                    $keyDates  = array_values($search_name[$key]['1']['0']);

                                    $cCount = count($charArray);
                                    $lastCharNew = $charArray[$cCount-1];
                                    $f      = '';
                                    $f1     = '';
                                    $newk   = '';
                                    $ff     = '';
                                    $newkk  = '';
                                    $ff1    = '';

                                    for($date=0,$charType=0;$date<count($keyDates),$charType<count($charArray);$date++,$charType++)
                                    {   
                                        $cnt        =   7;    
                                        foreach($arrival_codes_list as $ss=>$arival_c)
                                        {                  
                                            $f              =   $charArray[$charType].$cnt;
                                            $newk           =   $charArray[$charType];
                                            $f1             =   (++$newk).$cnt;      
                                            if(isset($gc_arr_multi_sub[$arival_c]))
                                            {   
                                                if($gc_arr_multi_sub[$arival_c]['0'] == $keyDates[$date])
                                                {
                                                    if(in_array($arival_c,$keys)) {                                                 
                                                        $phpExcelObject->getActiveSheet()->setCellValue($f, $gc_arr_multi_sub[$arival_c][2]);                                                                                                            
                                                        $phpExcelObject->getActiveSheet()->setCellValue($f1, $gc_arr_multi_sub[$arival_c][3]);                                                                                            
                                                    }                                                 
                                                }  
                                            } 
                                            else 
                                            {
                                                $ff                 =   $charArray[$charType].$cnt;
                                                $newkk              =   $charArray[$charType];
                                                $ff1                =   (++$newkk).$cnt;       
                                                
                                                $phpExcelObject->getActiveSheet()->setCellValue($f, 0);                                                                                                            
                                                $phpExcelObject->getActiveSheet()->setCellValue($f1,0);                             
                                            }                                        
                                            $cnt++;                                           
                                        }
                                    }                                                          
                                    array_shift($keyDates);
                                }               
                                
                                
                                if(isset($hotel_gc_arr_multi_sub)&&(count($hotel_gc_arr_multi_sub)!=0))
                                {                                
                                    $keys   = array_keys($hotel_gc_arr_multi_sub);		                            
                                    $arrival_codes_list  = array_values($search_name[$key]['1']['1']);

                                    $keyDates  = array_values($search_name[$key]['1']['0']);

                                    for($date=0,$charType=0;$date<count($keyDates),$charType<count($charArray);$date++,$charType++)
                                    {                                   
                                        $h_cnt    = 7;                                

                                        $sum    = 0;
                                        $sum1   = 0;                                                           

                                        foreach($arrival_codes_list as $ss=>$vv)
                                        {                   
                                            $xls_col        =   chr($gc_str_char);   
                                            
                                            $f              =   $charArray[$charType].$h_cnt;
                                            $newk           =   $charArray[$charType];
                                            $f1             =   (++$newk);                                                
                                            $f2             =    ++$f1.$h_cnt;   

                                            if(isset($hotel_gc_arr_multi_sub[$vv]))
                                            {    
                                                if($hotel_gc_arr_multi_sub[$vv]['0'] == $keyDates[$date])
                                                {                                                                                                
                                                    if(in_array($vv,$keys))
                                                    {                                                 
                                                        $phpExcelObject->getActiveSheet()->setCellValue($f2, $hotel_gc_arr_multi_sub[$vv][2]);                                                                                                            
                                                    }                                                
                                                }                                                                                        
                                            }   
                                            else {        
                                                $phpExcelObject->getActiveSheet()->setCellValue($f2, '0');                                                                                                            
                                                
                                            }
                                            $h_cnt++;                                                                                                                                 
                                        }
                                    }                                
                                    array_shift($keyDates);
                                }        
                                
                                if(isset($agent_gc_arr_multi_sub)&&(count($agent_gc_arr_multi_sub)!=0))
                                {                                
                                        $keys   = array_keys($agent_gc_arr_multi_sub);		                            
                                        $arrival_codes_list  = array_values($search_name[$key]['1']['1']);

                                        $keyDates  = array_values($search_name[$key]['1']['0']);

                                        for($date=0,$charType=0;$date<count($keyDates),$charType<count($charArray);$date++,$charType++)                                
                                        {                                                                                  
                                                $xls_row    =   7;                                

                                                $a_cnt    = 7;                                

                                                $sum    = 0;
                                                $sum1   = 0;                                                           

                                                foreach($arrival_codes_list as $ss=>$vv)
                                                {                   
                                                        $xls_col        =   chr($gc_str_char);                                    

                                                        if(isset($pax_gc_arr_multi_sub[$vv]))
                                                        {        

                                                                if($pax_gc_arr_multi_sub[$vv]['0'] == $keyDates[$date])
                                                                {
                                                                    $f              =   $charArray[$charType].$a_cnt;
                                                                    $newk           =   $charArray[$charType];                                                
                                                                    $f1             =   (++$newk);                                                                                                
                                                                    $f2             =   ++$f1;                                                                                                                                                
                                                                    $f3             =   ++$f2;                                                 
                                                                    $f4             =   ++$f3;                                                 
                                                                    $f5             =   ++$f4;                                                 
                                                                    $f6             =   ++$f5;
                                                                    $ithCol         =   $f6.$a_cnt;                                                
                                                                    $f7             =   ++$f6;
                                                                    $jthCol         =   $f7.$a_cnt;
                                                                    $f8             =   ++$f7;
                                                                    $kithCol        =   $f8.$a_cnt;
                                                                    $f9             =	++$f8;
                                                                    $l_thcol	    =	$f9.$a_cnt;

                                                                    if(in_array($vv,$keys))
                                                                    {   
                                                                            $phpExcelObject->getActiveSheet()->setCellValue($l_thcol, $agent_gc_arr_multi_sub[$vv][2]);                 
                                                                    }
                                                                    else
                                                                    {
                                                                            $phpExcelObject->getActiveSheet()->setCellValue($l_thcol,'0');                                                    
                                                                    }
                                                                }                                                                                       
                                                        }                                        
                                                        $a_cnt++;                                                                                                                                 
                                                }
                                        }                                
                                    array_shift($keyDates);
                                }                            

                                if(isset($flight_gc_arr_multi_sub)&&(count($flight_gc_arr_multi_sub)!=0))
                                {                                
                                    $keys   = array_keys($flight_gc_arr_multi_sub);		                            
                                    $arrival_codes_list  = array_values($search_name[$key]['1']['1']);

                                    $keyDates  = array_values($search_name[$key]['1']['0']);

                                    for($date=0,$charType=0;$date<count($keyDates),$charType<count($charArray);$date++,$charType++)
                                    {
                                        $fl_cnt    = 7;                                

                                        foreach($arrival_codes_list as $ss=>$vv)
                                        {                   
                                            $xls_col        =   chr($gc_str_char);      
                                            $f              =   $charArray[$charType].$fl_cnt;
                                            $newk           =   $charArray[$charType];
                                            $f1 =  (++$newk);
                                            $char_d = $f1.$fl_cnt;

                                            $f2             =    ++$f1;
                                            $char_e = $f2.$fl_cnt;

                                            $f3             =    ++$f2.$fl_cnt;

                                            $ff3 = ++$f2.$fl_cnt;

                                            $f4 = ++$f2.$fl_cnt;

                                            if(isset($flight_gc_arr_multi_sub[$vv]))
                                            {    
                                                if($flight_gc_arr_multi_sub[$vv]['0'] == $keyDates[$date])
                                                { 
                                                    if(in_array($vv,$keys))
                                                    {                   
                                                        //GC Colum Value
                                                        $RRound = "=ROUND(($f-$char_d-$char_e-$f3),0)";
                                                        $phpExcelObject->getActiveSheet()->setCellValue($ff3,$RRound);
                                                        
                                                        //GC% Colum Value
                                                        $asr = '=IFERROR(ROUND(('.$ff3."/".$f.'),0),"0")';
                                                        $phpExcelObject->getActiveSheet()->setCellValue($f4,$asr);

                                                        $phpExcelObject->getActiveSheet()->setCellValue($f3, $flight_gc_arr_multi_sub[$vv][2]);
                                                    }
                                                    else
                                                    {                                                        
                                                        $phpExcelObject->getActiveSheet()->setCellValue($f3,'0');                                                    
                                                    } 
                                                }                                                                                         
                                            }  
                                            else
                                                    {
                                                        $RRound = "=ROUND(($f-$char_d-$char_e-$f3),0)";
                                                        $phpExcelObject->getActiveSheet()->setCellValue($ff3,$RRound);
                                                        
                                                        //GC% Colum Value
                                                        $asr = '=IFERROR(ROUND(('.$ff3."/".$f.'),0),"0")';
                                                        $phpExcelObject->getActiveSheet()->setCellValue($f4,$asr);                                                                                                         
                                                    } 
                                            $fl_cnt++;                                                    
                                        }
                                    }                                
                                    array_shift($keyDates);
                                }
                                if(isset($pax_gc_arr_multi_sub)&&(count($pax_gc_arr_multi_sub)!=0))
                                {                                
                                    $keys   = array_keys($pax_gc_arr_multi_sub);		                            
                                    $arrival_codes_list  = array_values($search_name[$key]['1']['1']);

                                    $keyDates  = array_values($search_name[$key]['1']['0']);

                                    for($date=0,$charType=0;$date<count($keyDates),$charType<count($charArray);$date++,$charType++)
                                    {
                                        $p_cnt    = 7;                                
                                        foreach($arrival_codes_list as $ss=>$vv)
                                        {                   
                                            $xls_col        =   chr($gc_str_char);     
                                            
                                            $f              =   $charArray[$charType].$p_cnt;
                                            $newk           =   $charArray[$charType];  
                                            $cithCol        =   $f;
                                            $f1             =   (++$newk);
                                            $dithcol        =   $f1.$p_cnt;

                                            $f2             =   ++$dithcol;                                                                                                                                                 
                                            $eithCol        =   ++$f1.$p_cnt;

                                            $f3             =   ++$f2;      
                                            $fithCol        =  ++$f1.$p_cnt;

                                            $f4             =   ++$f3;                                                 
                                            $githCol        =   ++$f1.$p_cnt;

                                            $f5             =   ++$f4; 
                                            $hthCol        =   ++$f1.$p_cnt;

                                            $f6             =   ++$f5;
                                            $ithCol         =   ++$f1.$p_cnt;                                                
                                            $f7             =   ++$f6;
                                            $jthCol         =   ++$f1.$p_cnt;
                                            $f8             =   ++$f7;

                                            $kithCol = ++$f1.$p_cnt;  

                                            $lithCol = ++$f1.$p_cnt;

                                            $mithCol = ++$f1.$p_cnt;

                                            $nithCol = ++$f1.$p_cnt;

                                            $oithCol = ++$f1.$p_cnt;

                                            $pithCol = ++$f1.$p_cnt;

                                            $qithCol = ++$f1.$p_cnt;

                                            $rithCol = ++$f1.$p_cnt;
                                            $sithCol = ++$f1.$p_cnt;
                                            $tithCol = ++$f1.$p_cnt; 

                                            $divVal='';

                                            if(isset($pax_gc_arr_multi_sub[$vv]))
                                            { 
                                                if($pax_gc_arr_multi_sub[$vv]['0'] == $keyDates[$date])
                                                {
                                                    if(in_array($vv,$keys))
                                                    {                       
                                                        //$tval = $pax_gc_arr_multi_sub[$vv][5];
                                                        
                                                        if(isset($flight_gc_arr_multi_sub[$vv][2]))
                                                        {
                                                            $tval = $flight_gc_arr_multi_sub[$vv][2];
                                                        } else { $tval = 0; }
                                                        if($pax_gc_arr_multi_sub[$vv][2]!=0)
                                                            {  $divVal = round(($pax_gc_arr_multi_sub[$vv][3]/$pax_gc_arr_multi_sub[$vv][2])); }
                                                        else 
                                                            { $divVal ="0"; }
                                                        //echo $jthCol.",".$pax_gc_arr_multi_sub[$vv][3];
                                                        //echo "<br>";
                                                        $phpExcelObject->getActiveSheet()->setCellValue($ithCol, $pax_gc_arr_multi_sub[$vv][2]);                    
                                                        $phpExcelObject->getActiveSheet()->setCellValue($jthCol, $pax_gc_arr_multi_sub[$vv][3]);                    
                                                        $phpExcelObject->getActiveSheet()->setCellValue($kithCol, $divVal);                                                            
                                                        $phpExcelObject->getActiveSheet()->setCellValue($mithCol, "=((".$jthCol."*22)+".$tval.")");                    

                                                        $airCodeDestPrice = $this->getDestArivalPrice($vv);
                                                        $airCodeVatDestPrice = $this->getVatDestArivalPrice($vv);

                                                        $phpExcelObject->getActiveSheet()->setCellValue($nithCol, "=((".$jthCol."*".$airCodeDestPrice."))");                                                                                                                                                                                                                                
                                                        $phpExcelObject->getActiveSheet()->setCellValue($oithCol, "=((".$githCol."*".$airCodeVatDestPrice."))");                                                                                                                                                                                                                                                                                                                                       
                                                    }
                                                    else
                                                    {
                                                        $phpExcelObject->getActiveSheet()->setCellValue($ithCol,'0');                                                    
                                                        $phpExcelObject->getActiveSheet()->setCellValue($jthCol,'0');                                                    
                                                    }
                                                    
                                                    $phpExcelObject->getActiveSheet()->setCellValue($pithCol,"=($githCol-$mithCol-$nithCol-$lithCol-$oithCol)");                                                                                                                                                                                                                                
                                                    $phpExcelObject->getActiveSheet()->setCellValue($qithCol,"=IFERROR(ROUND(($pithCol/$jthCol),0),0)");
                                                    $phpExcelObject->getActiveSheet()->setCellValue($rithCol,"=IFERROR(ROUND(($cithCol/$jthCol),0),0)");
                                                    $phpExcelObject->getActiveSheet()->setCellValue($sithCol,"=IFERROR(ROUND(($eithCol/$jthCol),0),0)");                                                    
                                                    $phpExcelObject->getActiveSheet()->setCellValue($tithCol,"=IFERROR(ROUND(($fithCol/$jthCol),0),0)"); 
                                                }                                                                                       
                                            } 
                                            else
                                            {
                                                $phpExcelObject->getActiveSheet()->setCellValue($pithCol,"=($githCol-$mithCol-$nithCol-$lithCol-$oithCol)");                                                                                                                                                                                                                                
                                                $phpExcelObject->getActiveSheet()->setCellValue($qithCol,"=IFERROR(ROUND(($pithCol/$jthCol),0),0)");
                                                $phpExcelObject->getActiveSheet()->setCellValue($rithCol,"=IFERROR(ROUND(($cithCol/$jthCol),0),0)");
                                                $phpExcelObject->getActiveSheet()->setCellValue($sithCol,"=IFERROR(ROUND(($eithCol/$jthCol),0),0)");                                                    
                                                $phpExcelObject->getActiveSheet()->setCellValue($tithCol,"=IFERROR(ROUND(($fithCol/$jthCol),0),0)"); 
                                                
                                                $phpExcelObject->getActiveSheet()->setCellValue($ithCol,0);  
                                                $phpExcelObject->getActiveSheet()->setCellValue($jthCol,0);  
                                                $phpExcelObject->getActiveSheet()->setCellValue($kithCol,0); 
                                                $phpExcelObject->getActiveSheet()->setCellValue($mithCol,0);
                                                $phpExcelObject->getActiveSheet()->setCellValue($nithCol,0); 
                                                $phpExcelObject->getActiveSheet()->setCellValue($oithCol,0);								
                                            }
                                            $p_cnt++;                                                                                                                                 
                                        }
                                    }                                
                                    array_shift($keyDates);
                                }  
                            }  
                        }  
                        
                        $datesCount = count($search_name[$key]['1']['0'])*18+18;

                        $arrCount = count($search_name[$key]['1']['1']);
                        $c=7;
                        for ($j=0,$i = 'C'; $i !== 'ZZ',$j<$datesCount; $j++,$i++){

                            $startPos       = $i.$c;
                            $lastCharPos    = $i.($c+$arrCount-1);                        
                            $sumCalPos = $i.($c+($arrCount-1)+1);
                            
                            $phpExcelObject->getActiveSheet()->setCellValue($sumCalPos,"=SUM(".$startPos.":".$lastCharPos.')');
                            $phpExcelObject->getActiveSheet()->getStyle($sumCalPos)->getFont()->setBold(true);
                        }
                    
                        $datesCount     = count($search_name[$key]['1']['0'])*18;
                        $lastNumChar    = $datesCount+18;                         
                    
                        $totStartChar = $allChar[$datesCount];
                        $totStartChar_bold = $allChar[$datesCount];
                        $phpExcelObject->getActiveSheet()->setCellValue($totStartChar."5", "Total");  
                        $phpExcelObject->getActiveSheet()->getStyle($totStartChar."5")->getFont()->setBold(true);
                        $phpExcelObject->getActiveSheet()->setCellValue($totStartChar.$num_pos, "Revenue");  
                        $phpExcelObject->getActiveSheet()->getStyle($totStartChar.$num_pos)->getFont()->setBold(true);
                        
                        $ColPos = 7;
                        $arrival_codes_list  = array_values($search_name[$key]['1']['1']);                        
                        
                        for($arri_val=0;$arri_val<count($arrival_codes_list);$arri_val++)
                        {
                            $pos = array();
                            for($charType=0;$charType<count($charArray);$charType++)
                            {
                                $CurPlace = $totStartChar.$ColPos;                                                        
                                $pos[] = $charArray[$charType].$ColPos;
                                $AllColPos = implode(",",$pos);
                            }
                            
                            $ColPos++;
                            
                            $phpExcelObject->getActiveSheet()->setCellValue($CurPlace,"=SUM(".$AllColPos.')');
                            $phpExcelObject->getActiveSheet()->getStyle($CurPlace)->getFont()->setBold(true);
                          
                        }
                        
                        $phpExcelObject->getActiveSheet()->setCellValue(++$totStartChar.$num_pos, "Hotel");
                        $phpExcelObject->getActiveSheet()->getStyle(++$totStartChar_bold.$num_pos)->getFont()->setBold(true);
                        
                        $ColPos = 7;                                               
                        
                        for($arri_val=0;$arri_val<count($arrival_codes_list);$arri_val++)
                        {
                            $pos = array();
                            for($charType=0;$charType<count($charArray);$charType++)
                            {
                                $Hotel_CurPlace = $totStartChar.$ColPos;                                                        
                                $new = $charArray[$charType];
                                $DithCol = ++$new;
                                $EithCol = ++$new;
                                
                                $pos[] = $EithCol.$ColPos;
                                $Hotel_AllColPos = implode(",",$pos);
                            }                            
                            $ColPos++;
                            $phpExcelObject->getActiveSheet()->getStyle($Hotel_CurPlace)->getFont()->setBold(true);
                            $phpExcelObject->getActiveSheet()->setCellValue($Hotel_CurPlace,"=SUM(".$Hotel_AllColPos.')');                          
                        }
                        
                        $phpExcelObject->getActiveSheet()->setCellValue(++$totStartChar.$num_pos, "Flight");
                        $phpExcelObject->getActiveSheet()->getStyle(++$totStartChar_bold.$num_pos)->getFont()->setBold(true);
                        $ColPos = 7;                                               
                        
                        for($arri_val=0;$arri_val<count($arrival_codes_list);$arri_val++)
                        {
                            $pos = array();
                            for($charType=0;$charType<count($charArray);$charType++)
                            {
                                $Flight_CurPlace = $totStartChar.$ColPos;                                                        
                                $news = $charArray[$charType];
                                $DithCol = ++$news;
                                $EithCol = ++$DithCol;                             
                                $FithCol = ++$DithCol; 
                                
                                $pos[] = $FithCol.$ColPos;
                                $Flight_AllColPos = implode(",",$pos);
                            }                          
                            $ColPos++;                           
                            $phpExcelObject->getActiveSheet()->setCellValue($Flight_CurPlace,"=SUM(".$Flight_AllColPos.')');                          
                            $phpExcelObject->getActiveSheet()->getStyle($Flight_CurPlace)->getFont()->setBold(true);
                        }
                        
                        $phpExcelObject->getActiveSheet()->setCellValue(++$totStartChar.$num_pos, "Other Cost");  
                        $phpExcelObject->getActiveSheet()->getStyle(++$totStartChar_bold.$num_pos)->getFont()->setBold(true);
                        $ColPos = 7;                                               
                        
                        for($arri_val=0;$arri_val<count($arrival_codes_list);$arri_val++)
                        {
                            $pos = array();
                            for($charType=0;$charType<count($charArray);$charType++)
                            {
                                $Other_CurPlace = $totStartChar.$ColPos;                                                        
                                $news = $charArray[$charType];
                                $DithCol = ++$news;
                                
                                $pos[] = $DithCol.$ColPos;
                                $Other_AllColPos = implode(",",$pos);
                            }                          
                            $ColPos++;                         
                            $phpExcelObject->getActiveSheet()->setCellValue($Other_CurPlace,"=SUM(".$Other_AllColPos.')');                          
                            $phpExcelObject->getActiveSheet()->getStyle($Other_CurPlace)->getFont()->setBold(true);
                        }                       
                        
                        
                        $phpExcelObject->getActiveSheet()->setCellValue(++$totStartChar.$num_pos, "GC");  
                        $phpExcelObject->getActiveSheet()->getStyle(++$totStartChar_bold.$num_pos)->getFont()->setBold(true);
                        $ColPos = 7;                                               
                        
                        for($arri_val=0;$arri_val<count($arrival_codes_list);$arri_val++)
                        {
                            $pos = array();
                            for($charType=0;$charType<count($charArray);$charType++)
                            {
                                $GC_CurPlace = $totStartChar.$ColPos;                                                        
                                $news = $charArray[$charType];
                                $DithCol = ++$news;
                                $EithCol = ++$DithCol;                             
                                $FithCol = ++$DithCol; 
                                $GithCol = ++$DithCol;                                 
                                
                                $pos[] = $GithCol.$ColPos;
                                $GC_AllColPos = implode(",",$pos);
                            }                          
                            $ColPos++;                         
                            
                            $phpExcelObject->getActiveSheet()->setCellValue($GC_CurPlace,"=SUM(".$GC_AllColPos.')');                          
                            $phpExcelObject->getActiveSheet()->getStyle($GC_CurPlace)->getFont()->setBold(true);
                        }
                        
                        $phpExcelObject->getActiveSheet()->setCellValue(++$totStartChar.$num_pos, "GC%"); 
                        $phpExcelObject->getActiveSheet()->getStyle(++$totStartChar_bold.$num_pos)->getFont()->setBold(true);
                        
                        $ColPos = 7;                                               
                        
                        for($arri_val=0;$arri_val<count($arrival_codes_list);$arri_val++)
                        {
                            $pos = array();
                            for($charType=0;$charType<count($charArray);$charType++)
                            {
                                $GCP_CurPlace = $totStartChar.$ColPos;                                                        
                                $news = $charArray[$charType];
                                $DithCol = ++$news;
                                $EithCol = ++$DithCol;                             
                                $FithCol = ++$DithCol; 
                                $GithCol = ++$DithCol;                                 
                                $HithCol = ++$DithCol;
                                
                                $pos[] = $HithCol.$ColPos;
                                $GCP_AllColPos = implode(",",$pos);
                            }                          
                            $ColPos++;                         
                            $phpExcelObject->getActiveSheet()->setCellValue($GCP_CurPlace,"=SUM(".$GCP_AllColPos.')');                          
                            $phpExcelObject->getActiveSheet()->getStyle($GCP_CurPlace)->getFont()->setBold(true);
                        }
                        
                        $phpExcelObject->getActiveSheet()->setCellValue(++$totStartChar.$num_pos, "Allotment"); 
                        $phpExcelObject->getActiveSheet()->getStyle(++$totStartChar_bold.$num_pos)->getFont()->setBold(true);
                        $ColPos = 7;                                               
                        
                        for($arri_val=0;$arri_val<count($arrival_codes_list);$arri_val++)
                        {
                            $pos = array();
                            for($charType=0;$charType<count($charArray);$charType++)
                            {
                                $All_CurPlace = $totStartChar.$ColPos;                                                        
                                $news = $charArray[$charType];
                                $DithCol = ++$news;
                                $EithCol = ++$DithCol;                             
                                $FithCol = ++$DithCol; 
                                $GithCol = ++$DithCol;                                 
                                $HithCol = ++$DithCol;
                                $IithCol = ++$DithCol;
                                
                                $pos[] = $IithCol.$ColPos;
                                $All_AllColPos = implode(",",$pos);
                            }                          
                            $ColPos++;                         
                            $phpExcelObject->getActiveSheet()->setCellValue($All_CurPlace,"=SUM(".$All_AllColPos.')');                          
                            $phpExcelObject->getActiveSheet()->getStyle($All_CurPlace)->getFont()->setBold(true);
                        }
                        
                        $phpExcelObject->getActiveSheet()->setCellValue(++$totStartChar.$num_pos, "Sold Seats"); 
                        $phpExcelObject->getActiveSheet()->getStyle(++$totStartChar_bold.$num_pos)->getFont()->setBold(true);
                        
                        $ColPos = 7;                                               
                        
                        for($arri_val=0;$arri_val<count($arrival_codes_list);$arri_val++)
                        {
                            $pos = array();
                            for($charType=0;$charType<count($charArray);$charType++)
                            {
                                $Sold_CurPlace = $totStartChar.$ColPos;                                                        
                                $news = $charArray[$charType];
                                $DithCol = ++$news;
                                $EithCol = ++$DithCol;                             
                                $FithCol = ++$DithCol; 
                                $GithCol = ++$DithCol;                                 
                                $HithCol = ++$DithCol;
                                $IithCol = ++$DithCol;
                                $JithCol = ++$DithCol;
                                
                                $pos[] = $JithCol.$ColPos;
                                $Sold_AllColPos = implode(",",$pos);
                            }                          
                            $ColPos++;                         
                            $phpExcelObject->getActiveSheet()->setCellValue($Sold_CurPlace,"=SUM(".$Sold_AllColPos.')');                          
                            $phpExcelObject->getActiveSheet()->getStyle($Sold_CurPlace)->getFont()->setBold(true);
                        }
                        
                        $phpExcelObject->getActiveSheet()->setCellValue(++$totStartChar.$num_pos, "Allotment%"); 
                        $phpExcelObject->getActiveSheet()->getStyle(++$totStartChar_bold.$num_pos)->getFont()->setBold(true);
                        
                        $ColPos = 7;                                               
                        
                        for($arri_val=0;$arri_val<count($arrival_codes_list);$arri_val++)
                        {
                            $pos = array();
                            for($charType=0;$charType<count($charArray);$charType++)
                            {
                                $ALLP_CurPlace = $totStartChar.$ColPos;                                                        
                                $news = $charArray[$charType];
                                $DithCol = ++$news;
                                $EithCol = ++$DithCol;                             
                                $FithCol = ++$DithCol; 
                                $GithCol = ++$DithCol;                                 
                                $HithCol = ++$DithCol;
                                $IithCol = ++$DithCol;
                                $JithCol = ++$DithCol;
                                $KithCol = ++$DithCol;
                                
                                $pos[] = $KithCol.$ColPos;
                                $ALLP_AllColPos = implode(",",$pos);
                            }                          
                            $ColPos++;                         
                            $phpExcelObject->getActiveSheet()->setCellValue($ALLP_CurPlace,"=SUM(".$ALLP_AllColPos.')');                          
                            $phpExcelObject->getActiveSheet()->getStyle($ALLP_CurPlace)->getFont()->setBold(true);
                        }
                        
                        $phpExcelObject->getActiveSheet()->setCellValue(++$totStartChar.$num_pos, "VAT EU dest."); 
                        $phpExcelObject->getActiveSheet()->getStyle(++$totStartChar_bold.$num_pos)->getFont()->setBold(true);
                        
                        $ColPos = 7;                                                                       
                        for($arri_val=0;$arri_val<count($arrival_codes_list);$arri_val++)
                        {
                            $pos = array();
                            for($charType=0;$charType<count($charArray);$charType++)
                            {
                                $VAT_EU_CurPlace = $totStartChar.$ColPos;                                                        
                                $news = $charArray[$charType];
                                $DithCol = ++$news;
                                $EithCol = ++$DithCol;                             
                                $FithCol = ++$DithCol; 
                                $GithCol = ++$DithCol;                                 
                                $HithCol = ++$DithCol;
                                $IithCol = ++$DithCol;
                                $JithCol = ++$DithCol;
                                $KithCol = ++$DithCol;
                                $LithCol = ++$DithCol;
                                $MithCol = ++$DithCol;
                                $NithCol = ++$DithCol;
                                $OithCol = ++$DithCol;
                                
                                $pos[] = $OithCol.$ColPos;
                                $VAT_EU_AllColPos = implode(",",$pos);
                            }                          
                            $ColPos++;                         
                            $phpExcelObject->getActiveSheet()->setCellValue($VAT_EU_CurPlace,"=SUM(".$VAT_EU_AllColPos.')');                          
                            $phpExcelObject->getActiveSheet()->getStyle($VAT_EU_CurPlace)->getFont()->setBold(true);
                        }                        
                                              
                        $phpExcelObject->getActiveSheet()->setCellValue(++$totStartChar.$num_pos, "Sales Comm.");
                        $phpExcelObject->getActiveSheet()->getStyle(++$totStartChar_bold.$num_pos)->getFont()->setBold(true);
                        $ColPos = 7;                                                                       
                        for($arri_val=0;$arri_val<count($arrival_codes_list);$arri_val++)
                        {
                            $pos = array();
                            for($charType=0;$charType<count($charArray);$charType++)
                            {
                                $SALC_CurPlace = $totStartChar.$ColPos;                                                        
                                $news = $charArray[$charType];
                                $DithCol = ++$news;
                                $EithCol = ++$DithCol;                             
                                $FithCol = ++$DithCol; 
                                $GithCol = ++$DithCol;                                 
                                $HithCol = ++$DithCol;
                                $IithCol = ++$DithCol;
                                $JithCol = ++$DithCol;
                                $KithCol = ++$DithCol;
                                $LithCol = ++$DithCol;
                                
                                $pos[] = $LithCol.$ColPos;
                                $SALC_AllColPos = implode(",",$pos);
                            }                          
                            $ColPos++;                         
                            $phpExcelObject->getActiveSheet()->setCellValue($SALC_CurPlace,"=SUM(".$SALC_AllColPos.')');                          
                            $phpExcelObject->getActiveSheet()->getStyle($SALC_CurPlace)->getFont()->setBold(true);
                        }
                        
                        $phpExcelObject->getActiveSheet()->setCellValue(++$totStartChar.$num_pos, "Accrued delay/Empty leg"); 
                        $phpExcelObject->getActiveSheet()->getStyle(++$totStartChar_bold.$num_pos)->getFont()->setBold(true);
                        
                        $ColPos = 7;                                                                       
                        for($arri_val=0;$arri_val<count($arrival_codes_list);$arri_val++)
                        {
                            $pos = array();
                            for($charType=0;$charType<count($charArray);$charType++)
                            {
                                $ACCU_CurPlace = $totStartChar.$ColPos;                                                        
                                $news = $charArray[$charType];
                                $DithCol = ++$news;
                                $EithCol = ++$DithCol;                             
                                $FithCol = ++$DithCol; 
                                $GithCol = ++$DithCol;                                 
                                $HithCol = ++$DithCol;
                                $IithCol = ++$DithCol;
                                $JithCol = ++$DithCol;
                                $KithCol = ++$DithCol;
                                $LithCol = ++$DithCol;
                                $MithCol = ++$DithCol;
                                
                                $pos[] = $MithCol.$ColPos;
                                $ACCU_AllColPos = implode(",",$pos);
                            }                          
                            $ColPos++;                         
                            $phpExcelObject->getActiveSheet()->setCellValue($ACCU_CurPlace,"=SUM(".$ACCU_AllColPos.')');                          
                            $phpExcelObject->getActiveSheet()->getStyle($ACCU_CurPlace)->getFont()->setBold(true);
                        }
                        
                        $phpExcelObject->getActiveSheet()->setCellValue(++$totStartChar.$num_pos, "Destination Costs");   
                        $phpExcelObject->getActiveSheet()->getStyle(++$totStartChar_bold.$num_pos)->getFont()->setBold(true);
                        
                        $ColPos = 7;                                                                       
                        for($arri_val=0;$arri_val<count($arrival_codes_list);$arri_val++)
                        {
                            $pos = array();
                            for($charType=0;$charType<count($charArray);$charType++)
                            {
                                $DEST_CurPlace = $totStartChar.$ColPos;                                                        
                                $news = $charArray[$charType];
                                $DithCol = ++$news;
                                $EithCol = ++$DithCol;                             
                                $FithCol = ++$DithCol; 
                                $GithCol = ++$DithCol;                                 
                                $HithCol = ++$DithCol;
                                $IithCol = ++$DithCol;
                                $JithCol = ++$DithCol;
                                $KithCol = ++$DithCol;
                                $LithCol = ++$DithCol;
                                $MithCol = ++$DithCol;
                                $NithCol = ++$DithCol;
                                
                                $pos[] = $NithCol.$ColPos;
                                $DEST_AllColPos = implode(",",$pos);
                            }                          
                            $ColPos++;                         
                            $phpExcelObject->getActiveSheet()->setCellValue($DEST_CurPlace,"=SUM(".$DEST_AllColPos.')');                          
                            $phpExcelObject->getActiveSheet()->getStyle($DEST_CurPlace)->getFont()->setBold(true);
                        }
                        
                        
                        $phpExcelObject->getActiveSheet()->setCellValue(++$totStartChar.$num_pos, "Net GC"); 
                        $phpExcelObject->getActiveSheet()->getStyle(++$totStartChar_bold.$num_pos)->getFont()->setBold(true);
                        
                        $ColPos = 7;                                                                       
                        for($arri_val=0;$arri_val<count($arrival_codes_list);$arri_val++)
                        {
                            $pos = array();
                            for($charType=0;$charType<count($charArray);$charType++)
                            {
                                $Net_GC_CurPlace = $totStartChar.$ColPos;                                                        
                                $news = $charArray[$charType];
                                $DithCol = ++$news;
                                $EithCol = ++$DithCol;                             
                                $FithCol = ++$DithCol; 
                                $GithCol = ++$DithCol;                                 
                                $HithCol = ++$DithCol;
                                $IithCol = ++$DithCol;
                                $JithCol = ++$DithCol;
                                $KithCol = ++$DithCol;
                                $LithCol = ++$DithCol;
                                $MithCol = ++$DithCol;
                                $NithCol = ++$DithCol;
                                $OithCol = ++$DithCol;
                                $PithCol = ++$DithCol;
                                
                                $pos[] = $PithCol.$ColPos;
                                $Net_GC_AllColPos = implode(",",$pos);
                            }                          
                            $ColPos++;                         
                            $phpExcelObject->getActiveSheet()->setCellValue($Net_GC_CurPlace,"=SUM(".$Net_GC_AllColPos.')');                          
                            $phpExcelObject->getActiveSheet()->getStyle($Net_GC_CurPlace)->getFont()->setBold(true);
                        }
                        
                        
                        $phpExcelObject->getActiveSheet()->setCellValue(++$totStartChar.$num_pos, "GC/PAX"); 
                        $phpExcelObject->getActiveSheet()->getStyle(++$totStartChar_bold.$num_pos)->getFont()->setBold(true);
                        
                        $ColPos = 7;                                                                       
                        for($arri_val=0;$arri_val<count($arrival_codes_list);$arri_val++)
                        {
                            $pos = array();
                            for($charType=0;$charType<count($charArray);$charType++)
                            {
                                $GC_PAX_CurPlace = $totStartChar.$ColPos;                                                        
                                $news = $charArray[$charType];
                                $DithCol = ++$news;
                                $EithCol = ++$DithCol;                             
                                $FithCol = ++$DithCol; 
                                $GithCol = ++$DithCol;                                 
                                $HithCol = ++$DithCol;
                                $IithCol = ++$DithCol;
                                $JithCol = ++$DithCol;
                                $KithCol = ++$DithCol;
                                $LithCol = ++$DithCol;
                                $MithCol = ++$DithCol;
                                $NithCol = ++$DithCol;
                                $OithCol = ++$DithCol;
                                $PithCol = ++$DithCol;
                                $QithCol = ++$DithCol;
                                
                                $pos[] = $QithCol.$ColPos;
                                $GC_PAX_AllColPos = implode(",",$pos);
                            }                          
                            $ColPos++;                         
                            $phpExcelObject->getActiveSheet()->setCellValue($GC_PAX_CurPlace,"=SUM(".$GC_PAX_AllColPos.')');                          
                            $phpExcelObject->getActiveSheet()->getStyle($GC_PAX_CurPlace)->getFont()->setBold(true);
                        }                        
                        $phpExcelObject->getActiveSheet()->setCellValue(++$totStartChar.$num_pos, "Rev/PAX"); 
                        $phpExcelObject->getActiveSheet()->getStyle(++$totStartChar_bold.$num_pos)->getFont()->setBold(true);
                        
                        $ColPos = 7;                                                                       
                        for($arri_val=0;$arri_val<count($arrival_codes_list);$arri_val++)
                        {
                            $pos = array();
                            for($charType=0;$charType<count($charArray);$charType++)
                            {
                                $REV_PAX_CurPlace = $totStartChar.$ColPos;                                                        
                                $news = $charArray[$charType];
                                $DithCol = ++$news;
                                $EithCol = ++$DithCol;                             
                                $FithCol = ++$DithCol; 
                                $GithCol = ++$DithCol;                                 
                                $HithCol = ++$DithCol;
                                $IithCol = ++$DithCol;
                                $JithCol = ++$DithCol;
                                $KithCol = ++$DithCol;
                                $LithCol = ++$DithCol;
                                $MithCol = ++$DithCol;
                                $NithCol = ++$DithCol;
                                $OithCol = ++$DithCol;
                                $PithCol = ++$DithCol;
                                $QithCol = ++$DithCol;
                                $RithCol = ++$DithCol;
                                
                                $pos[] = $RithCol.$ColPos;
                                $REV_PAX_AllColPos = implode(",",$pos);
                            }                          
                            $ColPos++;                         
                            $phpExcelObject->getActiveSheet()->setCellValue($REV_PAX_CurPlace,"=SUM(".$REV_PAX_AllColPos.')');                          
                            $phpExcelObject->getActiveSheet()->getStyle($REV_PAX_CurPlace)->getFont()->setBold(true);
                        }
                        
                        $phpExcelObject->getActiveSheet()->setCellValue(++$totStartChar.$num_pos, "Hotel/PAX"); 
                        $phpExcelObject->getActiveSheet()->getStyle(++$totStartChar_bold.$num_pos)->getFont()->setBold(true);
                        
                        $ColPos = 7;                                                                       
                        for($arri_val=0;$arri_val<count($arrival_codes_list);$arri_val++)
                        {
                            $pos = array();
                            for($charType=0;$charType<count($charArray);$charType++)
                            {
                                $HOTEL_PAX_CurPlace = $totStartChar.$ColPos;                                                        
                                $news = $charArray[$charType];
                                $DithCol = ++$news;
                                $EithCol = ++$DithCol;                             
                                $FithCol = ++$DithCol; 
                                $GithCol = ++$DithCol;                                 
                                $HithCol = ++$DithCol;
                                $IithCol = ++$DithCol;
                                $JithCol = ++$DithCol;
                                $KithCol = ++$DithCol;
                                $LithCol = ++$DithCol;
                                $MithCol = ++$DithCol;
                                $NithCol = ++$DithCol;
                                $OithCol = ++$DithCol;
                                $PithCol = ++$DithCol;
                                $QithCol = ++$DithCol;
                                $RithCol = ++$DithCol;
                                $SithCol = ++$DithCol;
                                
                                $pos[] = $SithCol.$ColPos;
                                $HOTEL_PAX_AllColPos = implode(",",$pos);
                            }                          
                            $ColPos++;                         
                            $phpExcelObject->getActiveSheet()->setCellValue($HOTEL_PAX_CurPlace,"=SUM(".$HOTEL_PAX_AllColPos.')');                          
                            $phpExcelObject->getActiveSheet()->getStyle($HOTEL_PAX_CurPlace)->getFont()->setBold(true);
                        }
                        
                        $phpExcelObject->getActiveSheet()->setCellValue(++$totStartChar.$num_pos, "Flight/PAX");
                        $phpExcelObject->getActiveSheet()->getStyle(++$totStartChar_bold.$num_pos)->getFont()->setBold(true);
                        
                        $ColPos = 7;                                                                       
                        for($arri_val=0;$arri_val<count($arrival_codes_list);$arri_val++)
                        {
                            $pos = array();
                            for($charType=0;$charType<count($charArray);$charType++)
                            {
                                $FLIGT_PAX_CurPlace = $totStartChar.$ColPos;                                                        
                                $news = $charArray[$charType];
                                $DithCol = ++$news;
                                $EithCol = ++$DithCol;                             
                                $FithCol = ++$DithCol; 
                                $GithCol = ++$DithCol;                                 
                                $HithCol = ++$DithCol;
                                $IithCol = ++$DithCol;
                                $JithCol = ++$DithCol;
                                $KithCol = ++$DithCol;
                                $LithCol = ++$DithCol;
                                $MithCol = ++$DithCol;
                                $NithCol = ++$DithCol;
                                $OithCol = ++$DithCol;
                                $PithCol = ++$DithCol;
                                $QithCol = ++$DithCol;
                                $RithCol = ++$DithCol;
                                $SithCol = ++$DithCol;
                                $TithCol = ++$DithCol;
                                
                                $pos[] = $TithCol.$ColPos;
                                $FLIGT_PAX_AllColPos = implode(",",$pos);
                            }                          
                            $ColPos++;                         
                            $phpExcelObject->getActiveSheet()->setCellValue($FLIGT_PAX_CurPlace,"=SUM(".$FLIGT_PAX_AllColPos.')');                          
                            $phpExcelObject->getActiveSheet()->getStyle($FLIGT_PAX_CurPlace)->getFont()->setBold(true);
                        }
                        
                    $phpExcelObject->getActiveSheet()->setTitle($key_title);    
                    //exit;
                }

                // All Sub Report Functionality 
                if( ($key === 'R9')|| ($key === 'R3-20') || ($key === 'R16') || ($key === 'R17.2') || ($key === 'Flight_Data') )
                {
                    foreach( $search_name[$key] as $kl=>$vl){
                        if($kl!=='count'){
                            foreach($search_name[$key][$kl] as $o1=>$v11){
                                if($l<=$c){
                                    $phpExcelObject->getActiveSheet()->setCellValue($xls_col++ . $xls_row, $o1);        
                                }                   
                                $l++; 
                            }
                        }       
                    }

                    $phpExcelObject->getActiveSheet()->getStyle('A' . $xls_row . ':' . $xls_col . $xls_row)->getFont()->setBold(true);    
                    $l=0;

                    foreach ($search_name[$key] as $key1 => $value) {            
                        if($key1!=='count')
                        { 
                            $xls_col = 'A';
                            $xls_row++;
                            foreach($search_name[$key][$key1] as $o=>$v)
                            {
                                $a = $search_name[$key][$key1][$o];                                             
                                $phpExcelObject->getActiveSheet()->setCellValue($xls_col++ . $xls_row, $a);
                                $l++;  
                            }
                        }
                    }   
                    $max_col = $c; 

                    for ($xls_col = 'A', $i = 0; $i < $max_col; $xls_col++, $i++) {
                        $phpExcelObject->getActiveSheet()->getColumnDimension($xls_col)->setAutoSize(true);
                    } 
                }      
                
                // Set auto width
                $phpExcelObject->getActiveSheet()->setTitle($key_title);                              

                // Set the 2. sheet
                $phpExcelObject = $this->createExcelContentSheet($phpExcelObject, $request);

                // Set active sheet index to the first sheet, so Excel opens this as the first sheet
                $phpExcelObject->setActiveSheetIndex($k);                    
                $k++;
                
                // create the writer
                $writer = $this->get('phpexcel')->createWriter($phpExcelObject, 'Excel2007');

                // create the response
                $response = $this->get('phpexcel')->createStreamedResponse($writer);
                
                //TravelcoNordic-GC-Report
                $filename = sprintf('GC-Report-%s.xlsx', $DepDateFrToList);
                $dispositionHeader = $response->headers->makeDisposition(
                    ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                    $filename
                );  
            }            
        }
         
        $phpExcelObject->setActiveSheetIndex(0); 

        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        //$response->headers->set('Pragma', 'public');
        //$response->headers->set('Cache-Control', 'maxage=1');
        $response->headers->set('Content-Disposition', $dispositionHeader);
        return $response;        
    }    
    
    public function diff_dates($depFrom,$depTo)
    {
        $DFrom  = $depFrom->format('Y-m-d');
        $DTo    = $depTo->format('Y-m-d');
        
        $Variable1 = strtotime($DFrom); 
        $Variable2 = strtotime($DTo); 
        
        $array = array(); 
        
        // Use for loop to store dates into array 
        for ($currentDate = $Variable1; $currentDate <= $Variable2; $currentDate += (86400)) { 
                $Store = date('Y-m-d', $currentDate); 
                $array[] = $Store;
        }        
       
        return $array;
    }

    private function createExcelContentSheet($phpExcelObject, $request) {
        return $phpExcelObject;
    }
    
    public function GC_REPORT($AllDiffDates,$all_pivot_data)
    {
            $airportCodes = array();
            $airportDates = array();

            $ar = array();            
            
            foreach($all_pivot_data as $key=>$value)
            {
                foreach($value as $kkey=>$vvalue)
                {
                    if($kkey!=='count')
                    {                    
                        if(isset($vvalue['Arrival AIR']))
                        {
                            $airportCodes[] = $vvalue['Arrival AIR'];
                            $airportDates[] = $vvalue['Departure DT'];
                        }

                        if(isset($vvalue['DESTNATION_CODE']))
                        {
                            $airportCodes[] = $vvalue['DESTNATION_CODE'];
                            $airportDates[] = $vvalue['ARRIVAL_DATE'];
                        }

                        if(isset($vvalue['Arrival Airport Code']))
                        {
                            $airportCodes[] = $vvalue['Arrival Airport Code'];
                            $airportDates[] = $vvalue['Travel Start Date'];
                        }
                    }
                }
            }            
            
            $aDates = array_unique($airportDates);
            $aCodes = array_unique($airportCodes);
            sort($aDates);
            sort($aCodes);                   
            $ar = array("0"=>$aDates,"1"=>$aCodes);
            return $ar;
    } 
    
    public function Get_Pt_ID($pt_cd)            
    {   
        $pt_cd = strtoupper($pt_cd);
        $sql = "SELECT PT_ID FROM atcomres.ar_point where pt_tp ='AIR' AND PT_CD ='".$pt_cd."'";
        $conn = $this->get('doctrine.dbal.atcore_connection'); 

        $stmt = $conn->prepare($sql);

        $stmt->execute();
        $results = $stmt->fetchAll();  

        $D = count($results)>0?$results['0']['PT_ID']: '';
        return $D;                                
    }    
    
    public function arrival_handling_fee($arr_cd)
    {
        $sql = "select handling_fee_pr_pax from airport_lookups where airport_code='".$arr_cd."'";
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();            
        $user_stmt = $doctrine->getEntityManager() 
                      ->getConnection()
                        ->prepare($sql);
        $user_stmt->execute();    
        $data = $user_stmt->fetchAll(); 
        
        $D = count($data)>0?$data['0']['handling_fee_pr_pax']: '';
        return $D;        
    }
    public function arrival_tomben_average($dep_cd)
    {
        $sql = "select empty_leg_average from airport_lookups where airport_code='".$dep_cd."'";
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();            
        $user_stmt = $doctrine->getEntityManager() 
                      ->getConnection()
                      ->prepare($sql);
        $user_stmt->execute();    
        $data = $user_stmt->fetchAll(); 
        
        $D = count($data)>0?$data['0']['empty_leg_average']: '0';
        return $D;        
    }
    
    public function getDestArivalPrice($arivalCode)
    {
        $sql = "SELECT cost_pr_pax FROM `airport_lookups` where airport_code=UPPER('".$arivalCode."') ORDER BY `airport_lookups`.`cost_pr_pax` DESC";
                
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();            
        $user_stmt = $doctrine->getEntityManager() 
                      ->getConnection()
                      ->prepare($sql);
        $user_stmt->execute();    
        $data = $user_stmt->fetchAll();         
        $D = count($data)>0?$data['0']['cost_pr_pax']: '0';
        return $D;        
    }
    
    public function getVatDestArivalPrice($arivalCode)
    {        
        $sql = "SELECT vat FROM `airport_lookups` where `airport_code`=UPPER('".$arivalCode."') ORDER BY `airport_lookups`.`status` DESC";                       
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();            
        $user_stmt = $doctrine->getEntityManager() 
                      ->getConnection()
                      ->prepare($sql);
        $user_stmt->execute();    
        $data = $user_stmt->fetchAll();         
        $D = count($data)>0?$data['0']['vat']: '0';
        return $D;        
    }
}  