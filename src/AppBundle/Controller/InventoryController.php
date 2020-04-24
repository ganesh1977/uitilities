<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\VRP\Request\PackageSearchRequest;
use AppBundle\Entity\VRP\Element as Element;
use GuzzleHttp\Client;
use AppBundle\Entity\MemoryCache;

class InventoryController extends Controller
{
    
    /**
     * @Route("/inventory/mismatch", name="inventory_mismatch")
     */
    public function inventoryMismatchAction(Request $request)
    {
        $request = Request::createFromGlobals();
        if ($request->query->has('airport')) {
            $airport = preg_replace('/[^A-Z]+/', '', strtoupper($request->query->get('airport')));
        } else {
            $airport = null;
        }

        $flights = [];

        if (!empty($airport)) {

            $serializer = $this->get('jms_serializer');
                    
            $conn = $this->get('doctrine.dbal.atcore_connection');
        
            $sql = sprintf("SELECT
                                tis.cycle_dt, tis.alt, tis.bkd,
                                pt1.pt_cd as dep_pt_cd, pt2.pt_cd as arr_pt_cd
                            FROM
                                ATCOMRES.AR_TRANSINVROUTE tir,
                                ATCOMRES.AR_TRANSINVSECTOR tis,
                                ATCOMRES.AR_TRANSHEAD th,
                                ATCOMRES.AR_TRANSROUTE tr,
                                ATCOMRES.AR_POINT pt1,
                                ATCOMRES.AR_POINT pt2
                            WHERE
                                tr.dir_mth='OUT'
                                    AND
                                tis.cycle_Dt > SYSDATE
                                    AND
                                pt2.pt_cd = '%s'
                                and th.trans_head_id = tr.trans_head_id
                                and tis.trans_head_id=th.trans_head_id
                                and tis.trans_inv_sec_id=tir.dep_sec_id
                                and tir.trans_route_id= tr.trans_route_id
                                and tr.dep_pt_id=pt1.pt_id
                                and tr.arr_pt_id=pt2.pt_id
                            ORDER BY tis.cycle_dt",
                $airport
            );
                
            $results = $conn->fetchAll($sql);
            
            if (count($results) > 0) {
                $psReq = new PackageSearchRequest();
                
                $psReq->Adm = new Element\Adm();
                $psReq->Adm->Trk = new Element\Trk();
                
                $psReq->CltInfo = new Element\CltInfo();
        		$psReq->CltInfo->Locale = 'da_DA';
                $psReq->CltInfo->Agt_No = 'WEBAGT1';
        
                $psReq->Offer_Ctl = new Element\Offer_Ctl();
                $psReq->Offer_Ctl->NumOfResPerPage = 500;
        
                $psReq->St_Dt_Plus_Days = 0;
                $psReq->St_Dt_Minus_Days = 0;
                $psReq->Stay_Plus_Days = 14;
                $psReq->Stay_Minus_Days = 14;
        
                $promotions = array('BT', 'SR', 'LM', 'SO', 'HF', 'ST', 'UK');
                foreach ($promotions as $promotion) {
                    $prom = new Element\Prom();
                    $prom->Code = $promotion;
                    $psReq->Prom[] = $prom;
                }
                        
                $pax1 = new Element\Pax();
                $pax1->Age = 30;
                $pax1->Index = 1;
                $pax2 = new Element\Pax();
                $pax2->Age = 30;
                $pax2->Index = 2;
        
                $occ = new Element\Occ();
                $occ->Rm_No = 1;
                $occ->Pax[] = $pax1;
                $occ->Pax[] = $pax2;
        
                $psReq->Occs = new Element\Occs();
                $psReq->Occs->Occ[] = $occ;
            }

            $i = $looped = 0;
            foreach ($results as $result) {
                if (++$i > 100) continue;
                $looped++;
                $cycleDt = $result['CYCLE_DT'];
                $allotment = $result['ALT'];
                $booked = $result['BKD'];
                $depPtCd = $result['DEP_PT_CD'];
                $arrPtCd = $result['ARR_PT_CD'];

                $flights[$cycleDt] = [
                    'allotment' => $allotment,
                    'booked' => $booked
                ];
        
                // Set the parts of the object that chages...
                $date = new \Datetime($cycleDt);
                $startDate = $date;
                $endDate = $date->add(new \DateInterval('P14D'));
                $psReq->St_Dt = $startDate;
                $psReq->End_Dt = $endDate;

                $route1 = new Element\Route();
                $route1->Rt_Dir = 'outbound';
                $route1->Dep_Air_Cd = $depPtCd;
                $route1->Arr_Air_Cd = $arrPtCd;
                $route2 = new Element\Route();
                $route2->Rt_Dir = 'inbound';
                $route2->Dep_Air_Cd = $arrPtCd;
                $route2->Arr_Air_Cd = $depPtCd;

                $routing = new Element\Routing();
                $routing->Route[] = $route1;
                $routing->Route[] = $route2;

                $psReq->Route_List = new Element\Route_List();
                $psReq->Route_List->Routing[] = $routing;
        
                $xml = $serializer->serialize($psReq, 'xml');
		
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

        		$data = $response->getBody()->getContents(); // XML
            }

        }
        
        return $this->render('inventory/mismatch.html.twig', [
            'flights' => $flights,
            'airport' => $airport,
            'i' => $looped
        ]);
    }
}