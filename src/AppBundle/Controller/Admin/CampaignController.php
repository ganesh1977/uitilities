<?php

namespace AppBundle\Controller\Admin;

use AppBundle\Entity\Campaign\Campaign;
use AppBundle\Repository\CampaignRepository;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class CampaignController extends Controller
{
    /**
     * @Route("/admin/campaigns", name="campaign_admin")
     */
    public function indexAction()
    {
        $this->denyAccessUnlessGranted(new Expression(
            '"ROLE_ADMIN" in roles'
        ));

        /**
         * @var CampaignRepository $repo
         */
        $repo = $this->getDoctrine()->getRepository(Campaign::class);
        $deletedCampaigns = $repo->findAllDeletedCampaigns();

        return $this->render('admin/campaign/index.html.twig', array(
            'campaigns' => $deletedCampaigns
        ));
    }

    /**
     * @Route("/admin/campaigns/{id}/restore", name="campaign_admin_restore")
     */
    public function restoreAction($id)
    {
        $this->denyAccessUnlessGranted(new Expression(
            '"ROLE_ADMIN" in roles'
        ));

        /**
         * @var CampaignRepository $repo
         * @var Campaign           $campaign
         */
        $repo = $this->getDoctrine()->getRepository(Campaign::class);
        $em   = $this->getDoctrine()->getManager();

        $campaign = $repo->find($id);
        $campaign->setDeletedAt(null);

        $em->persist($campaign);
        $em->flush();

        $message = 'Campaign '. $campaign->getCd() .' was restored.';
        $this->addFlash('notice', $message);

        return $this->redirectToRoute('campaign_admin');
    }

}
