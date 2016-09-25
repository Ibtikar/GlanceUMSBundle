<?php

namespace Ibtikar\GlanceUMSBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Validator\Constraints;
use Ibtikar\GlanceUMSBundle\Controller\UserController;
use Ibtikar\GlanceUMSBundle\Form\VisitorImageType;
use Ibtikar\GlanceUMSBundle\Form\VisitorEditType;
use Ibtikar\GlanceUMSBundle\Form\VisitorInterlist;
use Ibtikar\GlanceUMSBundle\Form\VisitorType;
use Ibtikar\GlanceUMSBundle\Document\Visitor;
use Ibtikar\GlanceUMSBundle\Document\User;
use Ibtikar\GlanceUMSBundle\Document\InvitedFriends;
use Ibtikar\BackendBundle\Document\Staff;
use Ibtikar\AppBundle\Document\Material;
use Ibtikar\AppBundle\Document\Comics;
use Ibtikar\AppBundle\Document\Poll;
use Ibtikar\GlanceUMSBundle\Document\UserDocumentVote;
use Ibtikar\GlanceUMSBundle\Service\SocialNetwork\SocialNetworkConnector;



use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\StreamedResponse;


class VisitorController extends UserController {

    protected $translationDomain = 'visitor';

    /**
     * @author Mahmoud Mostafa <mahmoud.mostafa@ibtikar.net.sa>
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function forgotPasswordAction(Request $request) {
        $session = $request->getSession();
        $error = null;
        $success = null;
        $registeration = null;
        $captchaTrials = $session->get('forgotPasswordTrials', 1);
        $formBuilder = $this->createFormBuilder(null, array(
                    'translation_domain' => $this->translationDomain
                ))
                ->setMethod('POST')
                ->add('email', 'email', array('attr' => array('autocomplete' => 'off', 'class' => 'email'), 'constraints' => array(
                new Constraints\NotBlank(),
                new Constraints\Email()
        )));
        if ($captchaTrials > $this->container->getParameter('captcha_appear_after_failed_attempts')) {
            // the form add needs edit at the end of the function
            $formBuilder->add('captcha', 'genemu_captcha', array('label' => 'captcha', 'attr' => array('autocomplete' => 'off', 'class' => 'captcha')));
        }
        // the form add needs edit at the end of the function
        $formBuilder->add('send', 'submit', array('label' => 'Retrieve my password'));
        $form = $formBuilder->getForm();
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $data = $form->getData();
                $email = $data['email'];
                $forgotPasswordData = $this->get('user_operations')->forgotPassword($email);
                if($forgotPasswordData['status'] === 'success') {
                    // reset the form data and remove the captcha field
                    $session->remove('forgotPasswordTrials');
                    $formBuilder->remove('captcha');
                    $form = $formBuilder->getForm();
                    $success = $forgotPasswordData['message'];
                } else {
                    $error = $forgotPasswordData['message'];
                    $registeration = $forgotPasswordData['registeration'];
                }
            }
            if ($success === null) {
                $captchaTrials++;
                $session->set('forgotPasswordTrials', $captchaTrials);
                // check if this is the first time to show the captcha field
                if ($captchaTrials == ($this->container->getParameter('captcha_appear_after_failed_attempts') + 1)) {
                    $formBuilder->remove('send');
                    // the form add needs edit at the start of the function
                    $formBuilder->add('captcha', 'genemu_captcha', array('label' => 'captcha', 'attr' => array('autocomplete' => 'off', 'class' => 'captcha', 'data-noerror' => '')));
                    $formBuilder->add('send', 'submit', array('label' => 'Retrieve my password'));
                    $form = $formBuilder->getForm();
                    $form->handleRequest($request);
                }
            }
        }
        return $this->render('IbtikarGlanceUMSBundle:Visitor:forgotPassword.html.twig', array(
                    'success' => $success,
                    'error' => $error,
                    'registeration' => $registeration,
                    'form' => $form->createView(),
                    'translationDomain' => $this->translationDomain
        ));
    }


}
