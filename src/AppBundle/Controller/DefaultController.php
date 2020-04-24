<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\IpUtils;

// for demo
use AppBundle\Entity\VRP\Request\PackageSearchRequest;
use AppBundle\Entity\VRP\Element\CltInfo;
use AppBundle\Entity\VRP\Element\Adm;
use AppBundle\Entity\VRP\Element\Trk;
use AppBundle\Entity\VRP\Element\Offer_Ctl;
use AppBundle\Entity\VRP\Element\Sort;
use AppBundle\Entity\VRP\Element\Prom;
use AppBundle\Entity\VRP\Element\Prc_Range;
use AppBundle\Entity\VRP\Element\Loc;
use AppBundle\Entity\VRP\Element\Route_List;
use AppBundle\Entity\VRP\Element\Routing;
use AppBundle\Entity\VRP\Element\Route as SingleRoute;
use AppBundle\Entity\VRP\Element\Occs;
use AppBundle\Entity\VRP\Element\Occ;
use AppBundle\Entity\VRP\Element\Pax;

use GuzzleHttp\Client;
use AppBundle\Entity\MemoryCache;
use AppBundle\Entity\IPWhitelist;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        $whitelistedIps = [];
        $ipAddress = $request->getClientIp();
        $ips = $this->getDoctrine()->getRepository('AppBundle:IPWhitelist')->findByActive(true);
        foreach ($ips as $ip) {
            $whitelistedIps[] = $ip->getIpAddress();
        }
        $whitelisted = IpUtils::checkIp($ipAddress, $whitelistedIps);
        
        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', [
            'ip_address' => $ipAddress,
            'ip_whitelisted' => $whitelisted
        ]);
    }
    
    /**
     * @Route("/demo", name="demo")
     */
    public function demoAction(Request $request)
    {
        $serializer = $this->get('jms_serializer');
        
		$roomType = new PackageSearchRequest();
		
        $roomType->Adm = new Adm();
        $roomType->Adm->Debug = true;
		$roomType->Adm->Trk = new Trk();
		$roomType->Adm->Trk->From = 'atcomweb';
		$roomType->Adm->Trk->To = 'atcomres';
		
        $roomType->CltInfo = new CltInfo();
		$roomType->CltInfo->Locale = 'da_DA';
        $roomType->CltInfo->CltSysContext = 3;
		$roomType->CltInfo->User_Name = 'XROADBT';
        $roomType->CltInfo->Chan = 'inhouse';
        $roomType->CltInfo->Channel_Type = 'VRP';
        $roomType->CltInfo->TermCode = 'WEB';
        $roomType->CltInfo->Agt_No = 'WEBAGT1';
        
        $roomType->Offer_Ctl = new Offer_Ctl();
        $roomType->Offer_Ctl->NumOfResPerPage = 500;
        $sort = new Sort();
        $roomType->Offer_Ctl->Sort[] = $sort;
/*        $sort = new Sort();
        $sort->Order = 'HOTELNAME';
        $roomType->Offer_Ctl->Sort[] = $sort;*/
        
        $roomType->St_Dt = new \Datetime('2016-11-19');
        $roomType->End_Dt = new \Datetime('2016-11-26');
        $roomType->St_Dt_Plus_Days = 0;
        $roomType->St_Dt_Minus_Days = 0;
        $roomType->Stay_Plus_Days = 7;
        $roomType->Stay_Minus_Days = 7;
        
        $prom = new Prom();
        $prom->Code = 'BT';
        $roomType->Prom[] = $prom;
        $prom = new Prom();
/*        $prom->Code = 'SR';
        $roomType->Prom[] = $prom;
        $prom = new Prom();
        $prom->Code = 'LM';
        $roomType->Prom[] = $prom;
        $prom = new Prom();
        $prom->Code = 'SO';
        $roomType->Prom[] = $prom;
        $prom = new Prom();
        $prom->Code = 'HF';
        $roomType->Prom[] = $prom;
        $prom = new Prom();
        $prom->Code = 'ST';
        $roomType->Prom[] = $prom;*/
        
/*        $loc = new Loc();
        $loc->Loc_Cd = 'ES';
        $loc->Loc_Tp = 'COUNTRY';
        $roomType->Loc[] = $loc;*/
        
/*        $loc = new Loc();
        $loc->Loc_Cd = 'TFS';
        $loc->Loc_Tp = 'REGION';
        $roomType->Loc[] = $loc;*/

        $roomType->Route_List = new Route_List();

        $dep = 'CPH';
        $route1 = new SingleRoute();
        $route1->Rt_Dir = 'outbound';
        $route1->Dep_Air_Cd = $dep;
//        $route1->Arr_Air_Cd = 'LPA';
        $route2 = new SingleRoute();
        $route2->Rt_Dir = 'inbound';
//        $route2->Dep_Air_Cd = 'LPA';
        $route2->Arr_Air_Cd = $dep;

        $routing = new Routing();
        $routing->Route[] = $route1;
        $routing->Route[] = $route2;
    
        $roomType->Route_List->Routing[] = $routing;
        
        $pax1 = new Pax();
        $pax1->Age = 30;
        $pax1->Index = 1;
        $pax2 = new Pax();
        $pax2->Age = 30;
        $pax2->Index = 2;
        
        $occ = new Occ();
        $occ->Rm_No = 1;
        $occ->Pax[] = $pax1;
        $occ->Pax[] = $pax2;
        
        $roomType->Occs = new Occs();
        $roomType->Occs->Occ[] = $occ;
        
        $xml = $serializer->serialize($roomType, 'xml');
		
        if ($request->query->has('debug1')) {
            Header('Content-type: text/xml;charset:utf-8');
		    print $xml;
		    die();
        }
        
		$client = new Client([
			'timeout'  => 10.0,
		]);

		$response = $client->request('POST', 'https://prm.atcoretec.com/prmprod/vrpwebservice/anitegateway/anitegateway.aspx', [
			'body' => $xml,
			'headers'=> [
				'Content-Type' => 'text/xml;charset=UTF8',
			],
		]);

        if ($request->query->has('debug2')) {
            Header('Content-type: text/xml;charset:utf-8');
		    print $response->getBody()->getContents();
		    die();
        }

		//return $response->getBody()->getContents(); // XML
        
        
        
        // MemoryCache search
        $cache = new MemoryCache();
        $cache->adults = 2;
        $cache->from = 'CPH';
        $cache->to = 'LPA';
        $cache->date = '2016-11-19';
        $cache->stay = 14;
        $cache->stayMatch = 14;
        $cache->promotion = 'BT';
        $xml = $cache->doSearch();
        
        if ($request->query->has('debug3')) {
            Header('Content-type: text/xml;charset:utf-8');
		    print $xml;
		    die();
        }

        $AtCache = $serializer->deserialize($xml, 'AppBundle\Entity\MemoryCache\AtCache', 'xml');

        if ($request->query->has('debug4')) {
            print '<pre>';
            print_r($AtCache);
		    die();
        }
        
        
    }
}
