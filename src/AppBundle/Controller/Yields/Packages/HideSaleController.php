<?php

namespace AppBundle\Controller\Yields\Packages;

use AppBundle\Entity\YieldSupRequest;
use AppBundle\Entity\YieldSup\Control as YieldSupControl;
use AppBundle\Entity\YieldSup\Yield_Supp;
use AppBundle\Entity\YieldSup\Yield_Supps;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class HideSaleController extends Controller
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
     * @Route("/yield/packages/hide-sale", name="yield_packages_hide_sale")
     * @param Request $request
     * @return JsonResponse
     */
    public function updateAction(Request $request)
    {
        $response = new JsonResponse();

        $keyData = $request->request->get('keyData');
        $hideSale = $request->request->get('hideSale');

        // Not required POST parameter, if it's not found, mock a fake request.
        $priceSupplements = $request->request->get('supplements', []);
        if (!isset($priceSupplements['adu_sup'])) {
            $priceSupplements = [
                'adu_sup' => 0,
                'chd1_sup' => 0,
                'chd2_sup' => 0
            ];
        }

        if (is_null($keyData) || is_null($hideSale)) {
            return $response->setData([
                'success' => false,
                'error' => 'Key data or hide status not readable.'
            ]);
        }

        if (!$this->isAuthorized()) {
            return $response->setData([
                'success' => false,
                'error' => 'You do not have sufficient rights to change the sale visibility of this accommodation.'
            ]);
        }

        // Make Yield Request
        $ysr = new YieldSupRequest();
        $ysr->Control = new YieldSupControl();
        $ysr->Yield_Supps = new Yield_Supps();

        $supplement = new Yield_Supp();
        $supplement->Pkg_ID = $keyData;
        $supplement->Hide_Sale = $hideSale === 'true' ? 'Y' : 'N';
        $supplement->Adu_Sup = $priceSupplements['adu_sup'];
        $supplement->Chd_Sup_1 = $priceSupplements['chd1_sup'];
        $supplement->Chd_Sup_2 = $priceSupplements['chd2_sup'];
        $ysr->Yield_Supps->Yield_Supp[] = $supplement;


        // Prepare the API call to AtCore's SOAP webservice.
        $serializer = $this->get('jms_serializer');
        $jsonRequest = $serializer->serialize($ysr, 'json');

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
