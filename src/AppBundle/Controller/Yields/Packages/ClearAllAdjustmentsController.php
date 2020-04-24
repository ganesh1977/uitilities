<?php

namespace AppBundle\Controller\Yields\Packages;

use AppBundle\Entity\YieldSupRequest;
use AppBundle\Entity\YieldSup\Control as YieldSupControl;
use AppBundle\Entity\YieldSup\Yield_Supp;
use AppBundle\Entity\YieldSup\Yield_Supps;
use AppBundle\Service\Atcore;
use AppBundle\Entity\PriceDefinition\Log\Batch;
use AppBundle\Entity\PriceDefinition\Log\Change;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class ClearAllAdjustmentsController extends Controller
{
    private $allowedUsers = [
        'niels@air.local'
    ];

    /**
     * Determines if you're authorized to make changes to the data.
     * @return bool
     */
    protected function isAuthorized()
    {
        $user = $this->get('security.token_storage')->getToken()->getUser();

        $isAllowed = in_array(strtolower($user->getUsername()), $this->allowedUsers);
        $isGranted = $this->get('security.authorization_checker')->isGranted('ROLE_YIELD');

        return $isAllowed || $isGranted;
    }


    /**
     * @Route("/yield/packages/clear-all-adjustments", name="yield_packages_clear_all_adjustments")
     * @param Request $request
     * @return JsonResponse
     */
    public function clearAction(Request $request)
    {
        $response = new JsonResponse();
        $packages = $request->request->get('packages');

        if (!$this->isAuthorized()) {
            $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            return $response->setData([
                'success' => false,
                'error' => 'You do not have sufficient rights to change the sale visibility of this accommodation.'
            ]);
        }

        if (empty($packages)) {
            return $response->setData([
                'success' => true,
                'message' => 'No data updated.'
            ]);
        }

        $em = $this->getDoctrine()->getManager();

        $user = $this->get('security.token_storage')->getToken()->getUser();
        $batch = new Batch($user);
        $em->persist($batch);
        $em->flush();

        foreach ($packages as $pkgId => $package) {
            $package = (object) $package;
            $pkg_id = $pkgId;
            $adu_sup = 0;
            $chd_sup_1 = 0;
            $chd_sup_2 = 0;
            $rm_cd = $package->rmCd;
            $adu_prc = $package->aduPrc;
            $chd_prc_1 = $package->chd1Prc;
            $chd_prc_2 = $package->chd2Prc;

            $change = new Change($pkg_id, $rm_cd, $adu_sup, $chd_sup_1, $chd_sup_2, $adu_prc, $chd_prc_1, $chd_prc_2);
            $change->setBatch($batch);
            $em->persist($change);
            $em->flush();
        }

        // Prepare the API call to AtCore's SOAP webservice.
        $apiRequest = $this->buildRequest($packages);
        $serializer = $this->get('jms_serializer');
        $jsonRequest = $serializer->serialize($apiRequest, 'json');

        // Connect and make the API call.
        $wsResponse = $this->callWebservice($jsonRequest, 'YieldPricingWS/YieldPricing', 'YieldPricing');

        // In the event of an invalid API response, make the HTTP response invalid as well.
        if ($wsResponse['Response']['Status'] != 'OK') {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            return $response->setData([
                'success' => false,
                'wsResponse' => $wsResponse
            ]);
        }

        return $response->setData([
            'success' => true
        ]);
    }


    /**
     * @param array $packages
     * @return YieldSupRequest
     */
    protected function buildRequest($packages)
    {
        // Make Yield Request
        $ysr = new YieldSupRequest();
        $ysr->Control = new YieldSupControl();
        $ysr->Yield_Supps = new Yield_Supps();

        foreach ($packages as $pkgId => $package) {
            $package = (object) $package;
            $supplement = new Yield_Supp();
            $supplement->Pkg_ID = $pkgId;
            $supplement->Hide_Sale = $package->hideSale;
            $supplement->Adu_Sup = 0;
            $supplement->Chd_Sup_1 = 0;
            $supplement->Chd_Sup_2 = 0;
            $ysr->Yield_Supps->Yield_Supp[] = $supplement;
        }

        return $ysr;
    }


    /**
     * @param string $request   JSON string
     * @param string $endpoint  API endpoint
     * @param string $operation API operation
     * @return object
     */
    protected function callWebservice($request, $endpoint, $operation)
    {
        $webserviceContainer = $this->container->getParameter('atcore-webservice');
        $server      = $webserviceContainer['server'];
        $environment = $webserviceContainer['env'];

        $client = new \nusoap_client('http://' . $server . '/' . $environment . '/'. $endpoint .'.asmx?WSDL', true);
        return $client->call($operation, ['Request' => json_decode($request, true)]);
    }

}
