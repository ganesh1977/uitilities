<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class AvailabilityXmlController extends Controller
{    
    /**
     * @Route("/avlabl/status", name="avlabl_status")
     */
    public function avlablStatusAction(Request $request)
    {
        $avlabl = $this->get('app.avlabl_xml');
        $files = $avlabl->checkFiles();
        return $this->render('avlabl/status.html.twig', [
            'files' => $files
        ]);
    }

    /**
     * @Route("/avlabl/backup/modal", name="avlabl_backup_modal")
     */
    public function avlablBackupModalAction(Request $request)
    {
        $files = [];
        
        if ($request->query->has('file')) {
            $file = $request->query->get('file');
            $avlabl = $this->get('app.avlabl_xml');
            $files = $avlabl->getBackupFiles($file);
        }

        return $this->render('avlabl/modal.html.twig', [
            'files' => $files,
        ]);
    }
    
}