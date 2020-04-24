<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Airport\Airport;
use AppBundle\Repository\OfferRepository;
use AppBundle\Service\Atcore;
use Doctrine\ORM\EntityRepository;
use AppBundle\Repository\CampaignRepository;
use DateTime;
use Psy\Util\Json;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

use Symfony\Component\Form\Extension\Core\Type as Form;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

use AppBundle\Entity\Campaign\Campaign;
use AppBundle\Entity\Campaign\Offer;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;

class CampaignController extends Controller
{
    /**
     * @Route("/campaign", name="campaign")
     */
    public function campaignIndexAction(Request $request)
    {
        $promotion = '';
        if ($request->query->has('prom_cd')) {
            $promotion = $request->query->get('prom_cd');
        }

        /**
         * @var CampaignRepository $repo
         */
        $repo = $this->getDoctrine()->getRepository(Campaign::class);
        $campaigns = $repo->findAllActiveCampaigns($promotion);

        return $this->render('campaign/index.html.twig', [
            'search' => $request->query->all(),
            'campaigns' => $campaigns,
            'promotion' => $promotion
        ]);
    }

    /**
     * @Route("/campaign/edit", name="campaign_edit")
     */
    public function campaignEditAction(Request $request)
    {
        $campaignId = $request->query->get('campaign_id', null);
        $originalCampaignId = $campaignId;

        $campaign = new Campaign();
        if ($campaignId) {
            $campaign = $this->getDoctrine()->getRepository(Campaign::class)->find($campaignId);
            if (!$campaign) {
                throw $this->createNotFoundException(
                    'No campaign with id ' . $campaignId
                );
            }
        }

        /**
         * @var CampaignRepository $repo
         */
        $repo = $this->getDoctrine()->getRepository(Campaign::class);
        $promCodeNumbers = $repo->getNewPromCodeNumbers();

        $form = $this->createFormBuilder($campaign)
            ->setAction($this->generateUrl('campaign_edit', ['campaign_id' => $campaignId]))
            ->add('id', Form\HiddenType::class, ['data' => $campaignId, 'mapped' => false])
            ->add('prom_cd', Form\ChoiceType::class, [
                'choices' => [
                    'Bravo Tours' => 'BT',
                    'Solresor' => 'SR',
                    'Matkavekka' => 'LM',
                    'Solia' => 'SO',
                    'Heimsferdir' => 'HF',
                    'Sun Tours' => 'ST',
                    //'Primera Holidays UK' => 'UK'
                ],
                'label' => 'Brand',
                'required' => true,
                'attr' => [
                    'class' => 'campaign'
                ]
            ])
            ->add('promotion_cd', Form\ChoiceType::class, [
                'choices' => [
                    'Cost and Inventory' => 'CCI',
                    'Dynamic Packaging' => 'DP',
                    'Bedbank' => 'BB'
                ],
                'label' => 'Promotion Code',
                'attr' => [
                    'class' => 'campaign',
                    'data-help' => 'The promotion code for the campaign.'
                ]
            ])
            ->add('cd', Form\TextType::class, [
                'label' => 'Campaign code',
                'required' => true,
                'attr' => [
                    'readonly' => 'readonly',
                    'class' => 'campaign_code',
                    'data-help' => 'The campaign code is automatically generated and cannot be edited.'
                ]
            ])
            ->add('st_dt', Form\DateType::class, [
                'label' => 'Campaign Start Date',
                'required' => true,
                'widget' => 'single_text',
                'format' => 'dd-MMM-yyyy',
                'html5' => false,
                'attr' => [
                    'class' => 'dateInput startDate',
                    'data-help' => 'The date of which the campaign offers will be visible.'
                ],
            ])
            ->add('end_dt', Form\DateType::class, [
                'label' => 'Campaign End Date',
                'required' => true,
                'widget' => 'single_text',
                'format' => 'dd-MMM-yyyy',
                'html5' => false,
                'attr' => [
                    'class' => 'dateInput endDate',
                    'data-help' => 'The date of which the campaign offers no longer will be shown.'
                ]
            ])
            ->add('description', Form\TextType::class, [
                'label' => 'Description',
                'required' => false,
            ])
            ->add('overrule_sort', Form\CheckboxType::class, array('required'  => false))
            ->add('save', Form\SubmitType::class, [
                'attr' => ['class' => 'btn btn-primary'],
                'label' => ($campaignId ? 'Update' : 'Create') . ' campaign',
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $campaign->setPromCd($form->get('prom_cd')->getData());
            $campaign->setPromotionCd($form->get('promotion_cd')->getData());
            $campaign->setCd($form->get('cd')->getData());
            $campaign->setStDt($form->get('st_dt')->getData());
            $campaign->setEndDt($form->get('end_dt')->getData());
            $campaign->setDescription($form->get('description')->getData());
            $campaign->setUpdatedAt(new \DateTime('now'));

            if (!$originalCampaignId) {
                $campaign->setCreatedAt(new \DateTime('now'));
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($campaign);
            $em->flush();

            if ($originalCampaignId) {
                return $this->redirectToRoute('campaign');
            } else {
                return $this->redirectToRoute('campaign_offers', ['campaign_id' => $campaign->getId()]);
            }
        }

        return $this->render('campaign/edit.html.twig', [
            'form' => $form->createView(),
            'promCodeNumbers' => $promCodeNumbers,
            'campaignId' => $originalCampaignId
        ]);
    }

    /**
     * @Route("/campaign/edit/validation", name="campaign_validation")
     */
    public function campaignValidation(Request $request) {

        $form = $request->query->get('form');
        $campaignId = $request->query->get('campaign_id', null);

        /**
         * @var CampaignRepository $repo
         */
        $repo = $this->getDoctrine()->getRepository(Campaign::class);
        $campaign = $repo->findDuplicateCode($form['cd'], $campaignId);

        if (count($campaign) > 0) {
            return new JsonResponse([
                'success' => false,
                'messages' => ['cd' => 'Campaign code already taken.']
            ]);
        }

        return new JsonResponse([
            'success' => true
        ]);
    }


    /**
     * @Route("/campaign/delete", name="campaign_delete")
     */
    public function campaignDeleteAction(Request $request)
    {
        $data = json_decode($request->getContent());

        $campaignId = $data->campaign_id;

        $doctrine = $this->getDoctrine();

        if (!$campaignId) {

            return new JsonResponse([
                'success' => false,
                'error' => 'No campaign ID was present. Unable to delete campaign.',
            ], 400);
        }

        /** @var Campaign $campaign */
        $campaign = $doctrine->getRepository(Campaign::class)->find($campaignId);

        if (!$campaign) {

            return new JsonResponse([
                'success' => false,
                'error' => 'Campaign not found! Unable to delete. Campaign ID: '. $campaignId,
            ], 400);
        }

        $campaign->setDeletedAt(new \DateTime('now'));

        $em = $doctrine->getManager();
        $em->persist($campaign);
        $em->flush();

        return new JsonResponse([
            'success' => true
        ]);
    }


    /**
     * @Route("/campaign/offers", name="campaign_offers")
     * @param Request $request
     * @return Response
     */
    public function campaignOffersAction(Request $request)
    {
        $campaignId = $request->query->get('campaign_id', null);
        $campaign = $this->getDoctrine()->getRepository(Campaign::class)->find($campaignId);
        if (!$campaign) {
            throw $this->createNotFoundException(
                'No campaign with id ' . $campaignId
            );
        }

        $airports = $this->getDoctrine()->getRepository(Airport::class)->findBy(
            [],
            ['featured' => 'DESC']
        );

        /*
            BB- breakfast
            HB – half board
            FB – Full board
            AI light – All Inclusive light
            AI – All Inclusive
            AI plus – All Inclusive plus
         */
        $boardtypes = [
            "BB" => "BB - Breakfast",
            "HB" => "HB - Half board",
            "FB" => "FB - Full board",
            "AI light" => "AI light",
            "AI" => "AI",
            "AI plus" => "AI plus"
        ];

        return $this->render('campaign/offers.html.twig', [
            'campaign' => $campaign,
            'airports' => $airports,
            'prom_cd' => $campaign->getPromCd(),
            'promotion_cd' => $campaign->getPromotionCd(),
            'boardtypes' => $boardtypes
        ]);
    }


    /**
     * @Route("/campaign/offers/edit", name="campaign_offers_edit")
     */
    public function campaignOffersEditAction(Request $request)
    {
        $offerId = $request->query->get('offer_id', null);
        $campaignId = $request->query->get('campaign_id', null);
        $doctrine = $this->getDoctrine();
        $offerCampaignId = null;

        if ($campaignId) {
            // Fetch the Campaign, if not found we can't add/edit the offer.
            /**
             * @var $campaign Campaign
             */
            $campaign = $doctrine->getRepository('AppBundle:Campaign\Campaign')->find($campaignId);
            if (!$campaign) {
                throw $this->createNotFoundException('Campaign was not found! Campaign ID: ' . $campaignId);
            }
        }

        // If the offer ID is present fetch the offer, if not, create a new instance.
        if ($offerId) {
            /** @var Offer $offer */
            $offer = $doctrine->getRepository('AppBundle:Campaign\Offer')->find($offerId);
            $offerCampaignId = $offer->getCampaign()->getId();

            if (!$offer) {
                throw $this->createNotFoundException('Offer not found! ID: ' . $offerId);
            }
        } else {
            $offer = new Offer();
        }

        $form = $this->createFormBuilder($offer)
            ->setAction($this->generateUrl('campaign_offers_edit', ['offer_id' => $offerId]))
            ->add('campaign', EntityType::class, [
                'class' => 'AppBundle:Campaign\Campaign',
                'choice_label' => 'cd',
                'query_builder' => function(EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->where('u.deleted_at IS NULL');
                },
                'required' => true,
            ])
            ->add('dep_cd', Form\TextType::class, [
                'label' => 'Dep. Airport',
                'required' => true,
                'attr' => [
                    'class' => 'select2 airports'
                ]
            ])
            ->add('arr_cd', Form\TextType::class, [
                'label' => 'Arr. Airport',
                'required' => true,
                'attr' => [
                    'class' => 'select2 airports arrival'
                ]
            ])
            ->add('st_dt', Form\DateType::class, [
                'label' => 'Start Date',
                'required' => true,
                'widget' => 'single_text',
                'format' => 'dd-MMM-yyyy',
                'html5' => false,
                'attr' => [
                    'class' => 'dateInput startDate'
                ],
            ])
            ->add('end_dt', Form\DateType::class, [
                'label' => 'End Date',
                'required' => true,
                'widget' => 'single_text',
                'format' => 'dd-MMM-yyyy',
                'html5' => false,
                'attr' => [
                    'class' => 'dateInput endDate'
                ]
            ])
            ->add('stay', Form\IntegerType::class, [
                'label' => 'Stay',
                'required' => true,
                'attr' => ['min' => 1]
            ])
            ->add('carr_cd', Form\TextType::class, [
                'label' => 'Carrier Code',
                'required' => false,
            ])
            ->add('stc_stk_cd', Form\TextType::class, [
                'label' => 'Accom. Code',
                'required' => true,
            ])
            ->add('rm_cd', Form\TextType::class, [
                'label' => 'Room Code',
                'required' => false,
            ])
            ->add('bb', Form\TextType::class, [
                'label' => 'Board Basis',
                'required' => false,
            ])
            ->add('save', Form\SubmitType::class, [
                'attr' => ['class' => 'btn btn-primary'],
                'label' => ($offerId ? 'Update' : 'Add') . ' offer',
            ])
            ->add('id', Form\HiddenType::class, ['data' => $offerId, 'mapped' => false])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $campaign = $form->get('campaign')->getData();
            $promotionCd = $campaign->getPromotionCd();

            $offer->setCampaign($campaign);
            $offer->setStDt($form->get('st_dt')->getData());
            $offer->setStay($form->get('stay')->getData());
            $offer->setDepCd($form->get('dep_cd')->getData());
            $offer->setStcStkCd($form->get('stc_stk_cd')->getData());
            $offer->setUpdatedAt(new \DateTime('now'));
            
            if($promotionCd == 'BB') {
                $offer->setRmCd('All');
                $offer->setBb('All');
            }
            else {
                $offer->setRmCd($form->get('rm_cd')->getData());
                $offer->setBb($form->get('bb')->getData());
            }
            
            if(is_null($form->get('carr_cd')->getData()))
                $offer->setCarrCd('All');
            else
                $offer->setCarrCd($form->get('carr_cd')->getData());

            if (!$offerId) {
                $offer->setCreatedAt(new \DateTime('now'));
            }

            // If no sorting value or check if the campaign is changed
            if (!$offer->getSort() || ($offerId && $offerCampaignId != $campaign->getId())) {
                $offer->setSort($campaign->getHighestSort() + 1);
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($offer);
            $em->flush();

            return $this->redirectToRoute('campaign_offers', ['campaign_id' => $campaign->getId()]);
        }

        return $this->render('campaign/offers_edit.html.twig', [
            'form' => $form->createView(),
            'offerId' => $offerId
        ]);
    }

    /**
     * @Route("/campaign/offer/edit/validation", name="campaign_offer_validation")
     */
    public function campaignOfferValidation(Request $request) {

        $form = $request->query->get('form');

        $offerId = $request->query->get('offer_id', null);

        $messages = [];

        /**
         * @var OfferRepository $repo
         */
        $repo = $this->getDoctrine()->getRepository(Offer::class);

        try {
            $rmCd = (isset($form['rm_cd']) && !empty($form['rm_cd'])) ? $form['rm_cd'] : null;
            $bb = isset($form['bb']) && !empty($form['bb']) ? $form['bb'] : null;

            if ($offerId) {
                $offer = $repo->findDuplicateRow(
                    $form['campaign'],
                    new \Datetime($form['st_dt']),
                    new \Datetime($form['end_dt']),
                    $form['stay'],
                    $form['dep_cd'],
                    $form['arr_cd'],
                    $form['stc_stk_cd'],
                    $rmCd,
                    $bb,
                    $offerId);
            } else {
                $offer = $repo->findDuplicateRow(
                    $form['campaign'],
                    new \Datetime($form['st_dt']),
                    new \Datetime($form['end_dt']),
                    $form['stay'],
                    $form['dep_cd'],
                    $form['arr_cd'],
                    $form['stc_stk_cd'],
                    $rmCd,
                    $bb
                );
            }

        } catch (\Exception $ex) {
            return new JsonResponse([
                'success' => false,
                'error' => $ex->getMessage(),
                'trace' => $ex->getTraceAsString(),
            ], 400);
        }

        if (count($offer) > 0) {
            $messages['duplicated'] = "Duplicate entries.";
        }

        if (count($messages) > 0) {

            return new JsonResponse([
                'success' => false,
                'messages' => json_encode($messages)
            ]);
        }

        return new JsonResponse([
            'success' => true
        ]);
    }


    /**
     * @Route("/campaign/offer/delete", name="campaign_offer_delete")
     */
    public function campaignOfferDeleteAction(Request $request)
    {
        $data = json_decode($request->getContent());

        $offerId = $data->offer_id;
        $doctrine = $this->getDoctrine();

        if (!$offerId) {

            return new JsonResponse([
                'success' => false,
                'error' => 'No offer ID was present. Unable to delete offer.',
            ], 400);
        }

        /** @var Offer $offer */
        $offer = $doctrine->getRepository('AppBundle:Campaign\Offer')->find($offerId);

        if (!$offer) {

            return new JsonResponse([
                'success' => false,
                'error' => 'Offer not found! Unable to delete. Offer ID: '. $offerId,
            ], 400);
        }

        $offer->setDeletedAt(new \DateTime('now'));

        $em = $doctrine->getManager();
        $em->persist($offer);
        $em->flush();

        return new JsonResponse([
            'success' => true
        ]);
    }


    /**
     * @Route("/campaign/offers/ajaxsort", name="campaign_offers_ajaxsort")
     */
    public function campaignOffersAjaxSortAction(Request $request)
    {
        $response = new JsonResponse();
        $campaignId = $request->request->get('campaign_id', null);

        if (!$campaignId) {
            return $response->setData([
                'status' => 'ERROR',
                'message' => 'No campaign id was present.',
            ]);
        }

        $campaign = $this->getDoctrine()->getRepository('AppBundle:Campaign\Campaign')->find($campaignId);
        if (!$campaign) {
            return $response->setData([
                'status' => 'ERROR',
                'message' => 'No campaign with id ' . $campaignId . '.',
            ]);
        }

        $sortChanges = $request->request->get('sort_changes', null);

        $em = $this->getDoctrine()->getManager();

        $offers = $campaign->getOffers();

        if (empty($offers)) {
            return $response->setData([
                'status' => 'ERROR',
                'message' => 'No offers found.'
            ]);
        }

        $i = 0;
        foreach ($offers as $offer) {
            if (array_key_exists($offer->getId(), $sortChanges)) {
                $offer->setSort($sortChanges[$offer->getId()]);
                $em->persist($offer);
                $em->flush();

                $i++;
            }
        }

        return $response->setData([
            'status' => 'OK',
            'message' => 'Sort order changed for ' . $i . ' offers.',
        ]);
    }

    /**
     * @Route("/campaign/offers/accom/rooms", name="campaign_offers_accom_rooms")
     */
    public function getAccomRooms(Request $request)
    {
        /**
         * @var Atcore $atcore
         */
        $atcore = $this->get('app.atcore');

        $requestErrors = [];
        $requiredParams = [
            'accom_cd' => 'accommodation',
            'arr_cd' => 'arrival',
            'prom_cd' => 'promotion/brand',
            'stay' => 'stay',
            'st_dt' => 'start date',
            'end_dt' => 'end date'
        ];

        foreach ($requiredParams as $_param => $_paramName) {
            if (!$request->query->has($_param) && empty($request->query->get($_param, ''))) {
                $requestErrors[] = $_paramName;
            }
        }

        $connCd = $request->query->get('conn_cd', null); // Connection code
        $accomCd = $request->query->get('accom_cd', null);

        if (!empty($requestErrors)) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Not all required parameters was set.',
                'missingParams' => $requestErrors,
                'conn_cd'   => $connCd
            ], 400);
        }

        $atcore->stDt = new \DateTime($request->query->get('st_dt', null));
        $atcore->endDt = new \DateTime($request->query->get('end_dt', null));
        $atcore->promCd = $request->query->get('prom_cd', null);
        $atcore->stay = $request->query->get('stay', null);
        $atcore->arrCd = $request->query->get('arr_cd', null);
        $atcore->accomCd = $accomCd;

        try {
            $rooms = $atcore->getAvailableRoomTypes();
        }
        catch (\Exception $ex) {

            return new JsonResponse([
                'success' => false,
                'error' => $ex->getMessage(),
                'trace' => $ex->getTraceAsString(),
                'conn_cd'   => $connCd
            ], 400);
        }

        return new JsonResponse([
            'success' => true,
            'accom_cd' => $accomCd,
            'countRooms' => count($rooms),
            'rooms' => $rooms,
            'conn_cd'   => $connCd
        ]);
    }

    /**
     * @Route("/campaign/offers/accoms", name="campaign_offers_accoms")
     */
    public function getAccoms(Request $request)
    {
        $arrCd = $request->query->get('arr_cd', null);
        $promotionCd = $request->query->get('promotion_cd', null);

        if (empty($arrCd)) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Arrival airport was not present.',
                'arr_cd' => $arrCd,
                'promotion_cd' => $promotionCd
            ], 400);
        }

        /**
         * @var Atcore $atcore
         */
        $atcore = $this->get('app.atcore');
        
        $atcore->stDt = new \DateTime($request->query->get('st_dt', null));
        $atcore->endDt = new \DateTime($request->query->get('end_dt', null));
        $atcore->promCd = $request->query->get('prom_cd', null);
        $atcore->stay = $request->query->get('stay', null);
        $atcore->arrCd = $arrCd;
        $atcore->promotionCd = $promotionCd;
        
        try {
            $hotels = ($promotionCd == 'BB') ? $atcore->getHotelsFromMemCache() : $atcore->getHotelsByAirport();
        }
        
        catch (\Exception $ex) {
            return new JsonResponse([
                'success' => false,
                'error' => $ex->getMessage(),
                'trace' => $ex->getTraceAsString(),
                //'conn_cd'   => $connCd
            ], 400);
        }        

        return new JsonResponse([
            'success' => true,
            'arr_cd' => $arrCd,
            'countHotels' => count($hotels),
            'hotels' => $hotels
        ]);
    }
    
    /**
     * @Route("/campaign/offers/flt_carrier", name="campaign_offers_flt_carrier")
     */
    public function getFltCarrier(Request $request)
    {
        $depCd = $request->query->get('dep_cd', null);
        $arrCd = $request->query->get('arr_cd', null);
        $promotionCd = $request->query->get('promotion_cd', null);

        if (empty($arrCd) || empty($depCd)) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Arrival airport or departure airport was not present.',
                'dep_cd' => $depCd,
                'arr_cd' => $arrCd,
                'promotion_cd' => $promotionCd
            ], 400);
        }

        /**
         * @var Atcore $atcore
         */
        $atcore = $this->get('app.atcore');
        $atcore->depCd = $depCd;
        $atcore->arrCd = $arrCd;
        $atcore->stDt = new \DateTime($request->query->get('st_dt', null));
        $atcore->endDt = new \DateTime($request->query->get('end_dt', null));
        $atcore->promCd = $request->query->get('prom_cd', null);
        $atcore->stay = $request->query->get('stay', null);
        $atcore->promotionCd = $promotionCd;
        
        try {
            if($promotionCd == 'CCI' || empty($promotionCd)) {
                $carriers = $atcore->getCarriersByAirport();
            }
            else {
                $carriers = $atcore->getCarriersFromMemCache();
            }
        }
        catch (\Exception $ex) {
            return new JsonResponse([
                'success' => false,
                'error' => $ex->getMessage(),
                'trace' => $ex->getTraceAsString(),
                //'conn_cd'   => $connCd
            ], 400);
        }        

        return new JsonResponse([
            'success' => true,
            'arr_cd' => $arrCd,
            'countCarriers' => count($carriers),
            'carriers' => $carriers
        ]);
    }
}
