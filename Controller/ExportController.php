<?php

namespace Ibtikar\GlanceUMSBundle\Controller;

use Ibtikar\GlanceDashboardBundle\Controller\base\BackendController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class ExportController extends BackendController
{

    public function downloadAction(Request $request,$id, $filename, $filesavename)
    {
        $securityContext = $this->container->get('security.authorization_checker');
        $files = glob($this->container->getParameter('xls_temp_path') . $filename . "\[*\].*");
        if (!$this->getUser()) {
            $request->getSession()->set('redirectUrl', $request->getRequestUri());
            return $this->redirect($this->generateUrl('ibtikar_glance_ums_staff_login'));
        }

        if ($this->getUser()->getId()!= $id &&!$securityContext->isGranted('ROLE_ADMIN') ) {
            throw $this->createNotFoundException('Access Denied');
        }
        if (count($files) <= 0) {
            throw $this->createNotFoundException();
        }

        $file = $files[0];

        preg_match("/\[.*?\]/", $file, $dateBlock);

        if (file_exists($file)) {
            $fileInfo = pathinfo($file);
            $response = new BinaryFileResponse($file);
            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filesavename . $dateBlock[0] . '.' . $fileInfo['extension'], 'spreadsheet');
        }
        return $response;
    }
}
