<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Campaign\Campaign;
use AppBundle\Entity\Campaign\Offer;
use AppBundle\Repository\CampaignRepository;
use AppBundle\Repository\OfferRepository;
use DateTime;
use Doctrine\DBAL\Driver\PDOConnection;
use DoctrineExtensions\Query\Mysql\Date;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;

class WebserviceController extends Controller
{

    private final function handleAuthentication()
    {
        if (!isset($_SERVER['PHP_AUTH_USER'])) {
            $this->_throwUnauthorized();
        }

        $username = $_SERVER['PHP_AUTH_USER'];
        $token = $_SERVER['PHP_AUTH_PW'];

        /**
         * @var $conn PDOConnection
         */
        $conn = $this->get('doctrine.dbal.local_connection');

        $stmt = $conn->prepare("
            SELECT `id`
            FROM `api_users`
            WHERE `username` = :username
            AND `token` = :token
        ");
        $stmt->bindValue('username', $username);
        $stmt->bindValue('token', $token);
        $stmt->execute();

        if ($stmt->rowCount() != 1) {
            $this->_throwUnauthorized();
        }
    }

    private final function _throwUnauthorized()
    {
        header('WWW-Authenticate: Basic realm="Utils Webservice"');
        header('Content-type: application/json');
        $response = new JsonResponse([
            'status' => 401,
            'error' => 'You are unauthorized!'
        ], 401);
        $response->sendContent();
        exit;
    }

    /**
     * @Route("/webservice/rooms", name="webservice_rooms")
     */
    public function webserviceRoomsAction(Request $request)
    {                    
        $conn = $this->get('doctrine.dbal.atcore_connection');
    
        $sql = "SELECT
                  su.sell_unit_id,
                  COALESCE(ovrrm.name, rm.name) AS rm_name,
                  dan.des AS da_rm_name,
                  swe.des AS sv_rm_name,
                  fin.des AS fi_rm_name,
                  nor.des AS no_rm_name,
                  ice.des AS is_rm_name
                FROM
                  ATCOMRES.AR_SELLUNIT su
                    INNER JOIN ATCOMRES.AR_ROOM rm
                      ON rm.rm_id = su.rm_id
                    LEFT JOIN ATCOMRES.AR_ROOM ovrrm
                      ON ovrrm.rm_id = su.or_rm_id
                    LEFT JOIN ATCOMRES.AR_LANGUAGEDESCRIPTION dan
                      ON dan.lang_locale_id = 2254026
                      AND dan.link_id = COALESCE(su.or_rm_id, su.rm_id)
                      AND dan.link_cd = 'RM'
                    LEFT JOIN ATCOMRES.AR_LANGUAGEDESCRIPTION swe
                      ON swe.lang_locale_id = 2254029
                      AND swe.link_id = COALESCE(su.or_rm_id, su.rm_id)
                      AND swe.link_cd = 'RM'
                    LEFT JOIN ATCOMRES.AR_LANGUAGEDESCRIPTION nor
                      ON nor.lang_locale_id = 2254028
                      AND nor.link_id = COALESCE(su.or_rm_id, su.rm_id)
                      AND nor.link_cd = 'RM'
                    LEFT JOIN ATCOMRES.AR_LANGUAGEDESCRIPTION fin
                      ON fin.lang_locale_id = 2254027
                      AND fin.link_id = COALESCE(su.or_rm_id, su.rm_id)
                      AND fin.link_cd = 'RM'
                    LEFT JOIN ATCOMRES.AR_LANGUAGEDESCRIPTION ice
                      ON ice.lang_locale_id = 2992107
                      AND ice.link_id = COALESCE(su.or_rm_id, su.rm_id)
                      AND ice.link_cd = 'RM'";
                
        $results = $conn->fetchAll($sql);
        
        $response = new JsonResponse();
        return $response->setData($results);
        
    }

    /**
     * @Route("/webservice/slowresponse", name="webservice_slowresponse")
     */
    public function slowResponseAction(Request $request)
    {
        sleep(20);
        
        $response = new Response('200 OK - but slow...');
        
        return $response;
    }


    /**
     * @Route("/webservice/statistics/fourweeks", name="statistics_fourweeks")
     */
    public function statisticsFourWeeksAction(Request $request)
    {
        $promCd = strtoupper($request->query->get('prom_cd'));
        $type = $request->query->get('type', null);
        
        $conn = $this->get('doctrine.dbal.atcore_connection');
        
        $pax = $tot = $prf = [];

        $stDt = new \Datetime();
        $stDt->modify('-28 days');
        
        $sql = "SELECT
                    TO_CHAR(res.origin_dt, 'YYYY-MM-DD') DT,
                    SUM(res.n_pax) PAX,
                    COUNT(res.res_id) TOT,
                    SUM(res.prof_ex_vat) PRF
                FROM
                    ATCOMRES.AR_RESERVATION res
                        INNER JOIN ATCOMRES.AR_PROMOTION prom ON prom.prom_id = res.prom_id
                WHERE
                    res.origin_dt > :st_dt
                        AND
                    res.bkg_sts IN ('BKG','OPT')
                        AND
                    prom.cd = :prom_cd
                GROUP BY
                    TO_CHAR(res.origin_dt, 'YYYY-MM-DD')";

        $stmt = $conn->prepare($sql);
        $stmt->bindValue('st_dt', $stDt, 'date');
        $stmt->bindValue('prom_cd', $promCd);
        $stmt->execute();
        $results = $stmt->fetchAll();
        
        foreach ($results as $result) {
            $pax[] = [$result['DT'], (int)$result['PAX']];
            $tot[] = [$result['DT'], (int)$result['TOT']];
            $prf[] = [$result['DT'], (int)$result['PRF']];
        }

        $data = (object)[
            'x_axis' => (object)[
                'type' => 'datetime',
            ],
            'series' => [],
        ];
        
        if (is_null($type) || $type == 'Pax') {
            $data->series[] = (object)[
                'name' => 'Pax',
                'data' => $pax,
                'incomplete_from' => date('Y-m-d'),
            ];
        }

        if (is_null($type) || $type == 'Bookings') {
            $data->series[] = (object)[
                'name' => 'Bookings',
                'data' => $tot,
                'incomplete_from' => date('Y-m-d'),
            ];
        }

        if ($type == 'Profits') {
            $data->series[] = (object)[
                'name' => 'Profits',
                'data' => $prf,
                'incomplete_from' => date('Y-m-d'),
            ];
        }
                
        $response = new JsonResponse();
        return $response->setData($data);
    }


    /**
     * @Route("/webservice/statistics/oneday", name="statistics_oneday")
     */
    public function statisticsOneAction(Request $request)
    {
        $promCd = strtoupper($request->query->get('prom_cd'));
        $mktCd = $request->query->get('mkt_cd', null);
        $offset = $request->query->get('offset', 0);

        $stDt = new \Datetime(date('Y-m-d', time()-60*60*24*$offset));
        
        $endDt = clone $stDt;
        $endDt->modify('+1 day');
        
        $conn = $this->get('doctrine.dbal.atcore_connection');
        
        $sql = "SELECT
                    SUM(res.n_pax) PAX
                FROM
                    ATCOMRES.AR_RESERVATION res
                        INNER JOIN ATCOMRES.AR_PROMOTION prom ON prom.prom_id = res.prom_id
                        INNER JOIN ATCOMRES.AR_MARKET mkt ON mkt.mkt_id = res.mkt_id
                WHERE
                    res.origin_dt >= :st_dt
                        AND
                    res.origin_dt < :end_dt
                        AND
                    res.bkg_sts IN ('BKG','OPT')
                        AND
                    (:prom_cd is null OR prom.cd = :prom_cd)
                        AND
                    (:mkt_cd is null OR mkt.cd = :mkt_cd)";

        $stmt = $conn->prepare($sql);
        $stmt->bindValue('st_dt', $stDt, 'datetime');
        $stmt->bindValue('end_dt', $endDt, 'datetime');
        $stmt->bindValue('prom_cd', $promCd);
        $stmt->bindValue('mkt_cd', $mktCd);
        $stmt->execute();
        
        $result = $stmt->fetch();
        
        $data = (object)[
            'item' => [
                (object)[
                    'value' => $result['PAX'],
                ],
            ],
        ];
        
        $response = new JsonResponse();
        return $response->setData($data);
    }


    /**
     * @Route("/webservice/accommodations", name="ws_accommodations")
     */
    public function accommodationsAction(Request $request)
    {
        $term = strtoupper($request->query->get('term'));
        
        $data = [];
        
        if (strlen($term) > 3) {
            
            $accomCd = null;
            $accom = null;
        
            if (preg_match('/^[a-z]{3}[0-9]{1,3}/i', $term)) {
                $accomCd = $term . '%';
            } else {
                $accom = $term . '%';
            }

            $sql = "SELECT
                        stc.cd, stc.name
                    FROM
                        atcomres.ar_staticstock stc
                    WHERE
                        (:accom_cd is null OR LOWER(stc.cd) LIKE LOWER(:accom_cd))
                            AND
                        (:accom is null OR LOWER(stc.name) LIKE LOWER(:accom))
                    ORDER BY
                        stc.name";

            $conn = $this->get('doctrine.dbal.atcore_connection');

            $stmt = $conn->prepare($sql);
            $stmt->bindValue('accom_cd', $accomCd);
            $stmt->bindValue('accom', $accom);
            $stmt->execute();
        
            $results = $stmt->fetchAll();
        
            foreach ($results as $result) {
                $data[] = (object)[
                    'label' => $result['NAME'],
                    'value' => $result['CD'],
                ];
            }
        }

        $response = new JsonResponse();
        return $response->setData($data);
    }

    /**
     * @Route("/webservice/v1/campaigns", name="ws_campaigns")
     */
    public function campaignListAction()
    {
        // Authentication!
        $this->handleAuthentication();

        try {
            /**
             * @var CampaignRepository $repo
             */
            $repo = $this->getDoctrine()->getRepository(Campaign::class);
            $_activeCampaigns = $repo->findAllActiveCampaigns();
            $activeCampaigns = [];

            $timezone = new \DateTimeZone("Europe/Copenhagen");

            foreach ($_activeCampaigns as $_campaign) {
                $_campaign = (object) $_campaign;

                $campaign = $repo->find($_campaign->id);
                $offers = count($campaign->getOffers());

                $activeCampaigns[] = [
                    'id' => $_campaign->id,
                    'campaign_cd' => !empty($_campaign->cd) ? $_campaign->cd : null,
                    'prom_cd' => !empty($_campaign->prom_cd) ? $_campaign->prom_cd : null,
                    'description' => !empty($_campaign->description) ? $_campaign->description : null,
                    'active_st_dt' => !empty($_campaign->st_dt) ? $_campaign->st_dt->setTimezone($timezone)->format(DateTime::ATOM) : null,
                    'active_end_dt' => !empty($_campaign->end_dt) ? $_campaign->end_dt->setTimezone($timezone)->format(DateTime::ATOM) : null,
                    'offers' => $offers,
                    'overrule_sort' => $_campaign->overrule_sort ? 1 : 0,
                    'created_at' => !empty($_campaign->created_at) ? $_campaign->created_at->setTimezone($timezone)->format(DateTime::ATOM) : null,
                    'updated_at' => !empty($_campaign->updated_at) ? $_campaign->updated_at->setTimezone($timezone)->format(DateTime::ATOM) : null
                ];
            }

        }
        catch (\Exception $e) {
            return new JsonResponse([
                'status' => 500,
                'error' => 'Unknown error!'
            ], 500);
        }

        return new JsonResponse([
            'status' => 200,
            'total' => count($activeCampaigns),
            'campaigns' => $activeCampaigns
        ], 200);
    }

    /**
     * @Route("/webservice/v1/campaign/{campaignId}/offers", name="ws_campaign_offer")
     */
    public function campaignOfferListAction($campaignId)
    {
        // Authentication!
        $this->handleAuthentication();

        try {
            /**
             * @var OfferRepository $repo
             */
            $doctrine = $this->getDoctrine();
            $repo = $doctrine->getRepository(Offer::class);

            if (!$campaignId) {
                return new JsonResponse([
                    'status' => 404,
                    'error' => 'Campaign not found.'
                ], 404);
            }

            $campaign = $doctrine->getRepository(Campaign::class)->find($campaignId);

            $today = new \DateTime('now');
            $today->setTime(0, 0);

            if (is_null($campaign) || !is_null($campaign->getDeletedAt()) /*|| !($campaign->getStDt() <= $today && $campaign->getEndDt() >= $today)*/) {
                return new JsonResponse([
                    'status' => 404,
                    'error' => 'Campaign not found.'
                ], 404);
            }

            $_activeOffersInCampaign = $repo->findAllActiveOffersOnCampaign($campaignId, $campaign->getOverruleSort());
            $activeOffersInCampaign = [];

            $timezone = new \DateTimeZone("Europe/Copenhagen");

            foreach ($_activeOffersInCampaign as $_key => $_offer) {
                $_offer = (object)$_offer;
                $activeOffersInCampaign[] = [
                    'id' => $_offer->id,
                    'dep_st_dt' => !empty($_offer->st_dt) ? $_offer->st_dt->setTimezone($timezone)->format(DateTime::ATOM) : null,
                    'dep_end_dt' => !empty($_offer->end_dt) ? $_offer->end_dt->setTimezone($timezone)->format(DateTime::ATOM) : null,
                    'stay' => $_offer->stay,
                    'dep_cd' => !empty($_offer->dep_cd) ? $_offer->dep_cd : null,
                    'arr_cd' => !empty($_offer->arr_cd) ? $_offer->arr_cd : null,
                    'accom_cd' => !empty($_offer->accom_cd) ? $_offer->accom_cd : null,
                    'rm_cd' => !empty($_offer->rm_cd) ? $_offer->rm_cd : null,
                    'board_cd' => !empty($_offer->bb) ? $_offer->bb : null,
                    'sort' => $_offer->sort,
                    'created_at' => !empty($_offer->created_at) ? $_offer->created_at->setTimezone($timezone)->format(DateTime::ATOM) : null,
                    'updated_at' => !empty($_offer->updated_at) ? $_offer->updated_at->setTimezone($timezone)->format(DateTime::ATOM) : null
                ];
            }
        }
        catch (\Exception $e) {
            return new JsonResponse([
                'status' => 500,
                'error' => 'Unknown error!'
            ], 500);
        }

        return new JsonResponse([
            'status' => 200,
            'total' => count($activeOffersInCampaign),
            'offers' => $activeOffersInCampaign
        ], 200);
    }
}

