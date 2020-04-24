<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Ivory\GoogleMap\Map;
use Ivory\GoogleMap\Base\Coordinate;
use Ivory\GoogleMap\Overlay\Marker;
use Ivory\GoogleMap\Overlay\InfoWindow;
use Ivory\GoogleMap\Service\Geocoder\Request\GeocoderAddressRequest;

use AppBundle\Entity\Geodata;

class GoogleMapsController extends Controller
{
    /**
     * @Route("/maps/search", name="maps_search")
     */
    public function searchIndexAction(Request $request)
    {
        $promCd = strtoupper($request->query->get('prom_cd', null));
        $depCd = strtoupper($request->query->get('dep_cd', null));
        $arrCd = strtoupper($request->query->get('arr_cd', null));
        $ptCd = strtoupper($request->query->get('pt_cd', null));

        $stDt = new \Datetime($request->query->get('st_dt', null));
        $endDt = new \Datetime($request->query->get('end_dt', null));
        $stay = $request->query->get('stay', null);

        $nAdu = intval($request->query->get('n_adu', 0));
        $nChd = intval($request->query->get('n_chd', 0));

        $board = $request->query->get('board', null);
        $rating = $request->query->get('rating', null);

        $stay = explode('-', $stay);
        $sstay = intval($stay[0]);
        $estay = isset($stay[1]) ? intval($stay[1]) : $sstay;

        $cty1 = strlen($ptCd) == 2 ? $ptCd : null;
        $cty2 = strlen($ptCd) == 3 ? $ptCd : null;
        $cty3 = strlen($ptCd) == 5 ? $ptCd : null;


        $map = new Map();
        $map->setStylesheetOption('width', '100%');
        $map->setStylesheetOption('height', '500px');
        $map->setAutoZoom(true);
        $map->setMapOption('scrollwheel', false);


        $atcore = $this->get('app.atcore');
        $atcore->promCd = $promCd;
        $atcore->depCd = $depCd;
        $atcore->arrCd = $arrCd;
        $atcore->stDt = $stDt;
        $atcore->endDt = $endDt;
        $atcore->sstay = $sstay;
        $atcore->estay = $estay;
        $atcore->nAdu = $nAdu;
        $atcore->nChd = $nChd;
        $atcore->cty1 = $cty1;
        $atcore->cty2 = $cty2;
        $atcore->cty3 = $cty3;
        $atcore->board = $board;
        $atcore->rating = $rating;
        
        $response = $atcore->memoryCacheRequest(401);
		$xml = new \SimpleXMLElement($response);
		
        $conn = $this->get('doctrine.dbal.atcore_connection');

        $em = $this->getDoctrine()->getManager();
        $updateIfOlder = new \Datetime();
        $updateIfOlder->modify('-7 days');
        $update = new \Datetime();

        $offers = [];
		foreach ($xml->Result->Offers->Offer as $offer) {
            $offers[] = $offer;
            
            $accomCd = (string)$offer->Accom['Code'];
            $accom = (string)$offer->Accom['Name'];
            
            $geodata = $this->getDoctrine()->getRepository('AppBundle:Geodata')->find($accomCd);
            if (!$geodata || $geodata->getUpdateDtTm() < $updateIfOlder) {

                if (!$geodata) {
                    $geodata = new Geodata();
                    $geodata->setStcStkCd($accomCd);
                }

                // Get data from ATCORE
                $sql = "SELECT
                            adr.add1, adr.add2, adr.add3, adr.add4, adr.add5,
                            pt.name COUNTRY
                        FROM
                            ATCOMRES.AR_STATICSTOCK stc
                                INNER JOIN ATCOMRES.AR_ADDRESS adr ON adr.add_id = stc.stc_add_id
                                    LEFT JOIN ATCOMRES.AR_POINT pt ON pt.pt_id = adr.country
                        WHERE
                            stc.cd = :accom_cd";

                $stmt = $conn->prepare($sql);
                $stmt->bindValue('accom_cd', $accomCd);
                $stmt->execute();
                $result = $stmt->fetch();
            
                $locations = [
                    $accom,
                    $result['ADD1'],
                    $result['ADD2'],
                    $result['ADD3'],
                    $result['ADD4'],
                    $result['ADD5'],
                    $result['COUNTRY'],
                ];
                $locationData = [];
                foreach ($locations as $location) {
                    if (!is_null($location)) {
                        $locationData[] = $location;
                    }
                }
            
                $geosearch = implode($locationData, ', ');

                $georequest = new GeocoderAddressRequest($geosearch);
                $georesponse = $this->container->get('ivory.google_map.geocoder')->geocode($georequest);
        
/*                if (count($georesponse->getResults()) > 1) {
                    print('More than one geocoded address for "' . $geosearch . '"<br><pre>' . var_dump($georesponse->getResults()) . '</pre>');
                }*/
                
                foreach ($georesponse->getResults() as $result) {
                    $location = $result->getGeometry()->getLocation();

                    $geodata->setLatitude($location->getLatitude());
                    $geodata->setLongitude($location->getLongitude());
                    $geodata->setPlaceId($result->getPlaceId());
                    $geodata->setAddress($result->getFormattedAddress());
                }

                $geodata->setUpdateDtTm($update);

                $em->persist($geodata);
                $em->flush();
                
                
            }
            
            if ($geodata->getPlaceId()) {
                $infoWindow = new InfoWindow($accom);
            
                $marker = new Marker(new Coordinate($geodata->getLatitude(), $geodata->getLongitude()));
                $marker->setInfoWindow($infoWindow);

                $map->getOverlayManager()->addMarker($marker);
            }
        }
        
        
        $facets = [];
		foreach ($xml->Result->Facets->Group as $group) {
            $facetName = (string)$group['Id'];
            $facets[$facetName] = [];

            foreach ($group->Facet as $facet) {
                $facets[$facetName][(string)$facet['Id']]['count'] = (integer)$facet['Count'];
                if (isset($facet['Price'])) {
                    $facets[$facetName][(string)$facet['Id']]['price'] = (integer)$facet['Price'];
                }
            }
        }        
    
        
        return $this->render('maps/search.html.twig', [
            'map' => $map,
            'offers' => $offers,
            'facets' => $facets,
            'cache_link' => $atcore->getMemoryCacheLink(401),
            'search' => $request->query->all(),
        ]);
    }

}