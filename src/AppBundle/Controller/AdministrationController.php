<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use AppBundle\Entity\IPWhitelist;

class AdministrationController extends Controller
{
    /**
     * @Route("/admin", name="admin")
     */
    public function indexAction(Request $request)
    {
        return $this->render('admin/index.html.twig', [
        ]);
    }

    /**
     * @Route("/admin/ip", name="admin_ip")
     */
    public function ipAddressAction(Request $request)
    {
        $alerts = [];
        
        $id = $request->query->has('id') ? $request->query->get('id') : null;
        if ($id) {
            $ip = $this->getDoctrine()->getRepository('AppBundle:IPWhitelist')->find($id);
            if (!$ip) {
                throw $this->createNotFoundException(
                    'No ip address found for id ' . $id
                );
            }
        } else {
            $ip = new IPWhitelist();
        }

        $form = $this->createFormBuilder($ip)
            ->add('ipAddress', TextType::class, [
                'required' => true
            ])
            ->add('description', TextType::class, [
                'required' => true
            ])
            ->add('active', CheckboxType::class, [
                'required' => false
            ])
            ->add('save', SubmitType::class, [
                'attr' => ['class' => 'btn btn-primary'],
                'label' => 'Save whitelisted IP address'
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ip->setIpAddress($form->get('ipAddress')->getData());
            $ip->setActive($form->get('active')->getData());
            $ip->setDescription($form->get('description')->getData());

            $em = $this->getDoctrine()->getManager();
            $em->persist($ip);
            $em->flush();

            $alerts[] = array('success', '<strong>Success!</strong> IP address saved to database.');
        }
        
        $whitelistedIps = $this->getDoctrine()->getRepository('AppBundle:IPWhitelist')->findAll();

        return $this->render('admin/ipaddress.html.twig', [
            'form' => $form->createView(),
            'alerts' => $alerts,
            'whitelisted_ips' => $whitelistedIps
        ]);
    }

}
