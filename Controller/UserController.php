<?php

namespace Ibtikar\GlanceUMSBundle\Controller;

use Ibtikar\GlanceDashboardBundle\Controller\base\BackendController;
use Ibtikar\VisitorBundle\Document\Visitor;
use Ibtikar\GlanceUMSBundle\Document\User;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Validator\Constraints\NotBlank;
use EWZ\Bundle\RecaptchaBundle\Form\Type\EWZRecaptchaType;
use EWZ\Bundle\RecaptchaBundle\Validator\Constraints\IsTrue as RecaptchaTrue;
use \Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use \Symfony\Component\Form\Extension\Core\Type\PasswordType;
use \Symfony\Component\Form\Extension\Core\Type\SubmitType;

class UserController extends BackendController {

    protected $repoClass = 'IbtikarGlanceUMSBundle:Visitor';
    protected $staffRepo = 'IbtikarGlanceUMSBundle:Staff';
    protected $loginView = 'IbtikarGlanceUMSBundle:Visitor:login.html.twig';
    protected $changePasswordFromEmailView = 'IbtikarGlanceUMSBundle:Visitor:changePasswordFromEmail.html.twig';
    protected $changePasswordView = 'IbtikarGlanceUMSBundle:Visitor:changePassword.html.twig';
    protected $mustChangePassword = 'IbtikarGlanceUMSBundle:Staff:changePassword.html.twig';

    public function loginAction(Request $request) {

        $session = $request->getSession();
        if ($request->get('redirectUrl')) {
            $session->set('redirectUrl', $request->get('redirectUrl'));
        }
        $securityTargetPath = $session->get('_security.secured_area.target_path');
        $redirectUrl = $session->get('redirectUrl', '');
        if ((strpos($securityTargetPath, 'backend') !== false || strpos($redirectUrl, 'backend') !== false) && strpos($request->getUri(), 'backend') === false && $session->get('firstTimeRedirected', false) === false) {
            return $this->redirect($this->generateUrl('ibtikar_glance_ums_staff_login'));
        }

        $user = $this->getUser();

        if (!is_null($user) && strpos(get_class($user), 'Visitor') !== false) {
            return $this->redirect($this->generateUrl('visitor_view_profile'));
        }
        $authenticationUtils = $this->get('security.authentication_utils');

        $data = array(
            '_username' => $authenticationUtils->getLastUsername(),
            '_failure_path' => $request->getUri()
        );
        $formBuilder = $this->createFormBuilder($data)
                ->setAction($this->generateUrl('login_check'))
                ->setMethod('POST')
                ->add('_username')
                ->add('_password', PasswordType::class)
                ->add('_failure_path', \Symfony\Component\Form\Extension\Core\Type\HiddenType::class)
                ->add('_remember_me', \Symfony\Component\Form\Extension\Core\Type\CheckboxType::class, array('required' => false));

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();



        $loginTrials = $session->get('loginTrials', 1);
        if ($error || $loginTrials > 1) {
            if ($error) {
                $loginTrials++;
            }
            if ($loginTrials > $this->container->getParameter('captcha_appear_after_failed_attempts')) {
                $session->set('secret', $this->container->getParameter('secret'));
                $formBuilder->add('recaptcha', EWZRecaptchaType::class, array(
                    'language' => 'ar', 'attr' => array('errorMessage'=>  $this->trans('This value should not be blank.', array(), 'login'),
                        'options' => array(
                            'theme' => 'light',
                            'type' => 'image',
                            'size' => 'normal'
                        )
                    ),
                    'mapped' => false,
                    'constraints' => array(
                        new RecaptchaTrue()
                    )
                ));
            }
            $session->set('loginTrials', $loginTrials);
        }
        $form = $formBuilder->getForm();
        $user = $this->getUser();
            return $this->render($this->loginView, array(
                        'form' => $form->createView(),
                        'error' => $error
            ));

    }

    private function getUserByEmailAndChangePasswordToken($email, $token) {
        $user = $this->get('doctrine_mongodb')->getManager()->getRepository($this->repoClass)->findOneBy(array('email' => $email, 'deleted' => false, 'enabled' => true));
        $currentTime = new \DateTime();
        if (!$user || $user->getChangePasswordToken() !== $token || $user->getChangePasswordTokenExpiryDate() < $currentTime) {
            throw $this->createNotFoundException();
        }
        return $user;
    }


    public function autoLoginAction(Request $request, $email, $token) {
        $user = $this->getUserByEmailAndChangePasswordToken($email, $token);
        $dm = $this->get('doctrine_mongodb')->getManager();
        if (!$user->getEmailVerified()) {
            $user->setEmailVerified(true);
            $dm->flush();
        }
        $redirectUrl = $request->get('redirectUrl');
        if ($redirectUrl) {
            $this->get('session')->set('redirectUrl', $redirectUrl);
        }
        return $this->loginUser($user);
    }

    /**
     * @param User $user
     * @return Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function loginUser(User $user) {
        // manually login the user
        try {
            // create the authentication token
            $token = new UsernamePasswordToken($user, null, 'secured_area', $user->getRoles());
            // give it to the security context
            $this->get('security.token_storage')->setToken($token);
            $this->get('session')->set('_security_secured_area', serialize($token));
            $event = new InteractiveLoginEvent($this->get('request_stack')->getCurrentRequest(), $token);
            $this->get('event_dispatcher')->dispatch('security.interactive_login', $event);

        } catch (\Exception $e) {
            // login failed go to the login page
            $this->get('security.token_storage')->setToken(null);
            $this->get('session')->invalidate();
            if ($user instanceof Visitor) {
                return $this->redirect($this->generateUrl('login'));
            }
            return $this->redirect($this->generateUrl('ibtikar_glance_ums_staff_login'));
        }
        return $this->postLoginAction();
    }

    public function postLoginAction() {
        $request=$this->get('request_stack')->getCurrentRequest();
        $session = $request->getSession();
        $cookies = $request->cookies;

        if ($cookies->has('redirectUrl')) {
            $rediretUrl = $cookies->get('redirectUrl');
            setcookie("redirectUrl", "", time() - 3600);
        } else {
            $rediretUrl = $session->get('redirectUrl', FALSE);
        }
        $isStaffMember = false;
        if ($this->container->get('security.authorization_checker')->isGranted('ROLE_STAFF')) {
            $isStaffMember = true;
            $flashBag = $this->get('session')->getFlashBag();
            foreach ($flashBag->keys() as $type) {
                $flashBag->set($type, array());
            }
        }
        $dm = $this->get('doctrine_mongodb')->getManager();
        $dm->flush();

        if ($rediretUrl) {
            $session->remove('redirectUrl');
        } else {
            $rediretUrl = $session->remove('_security.secured_area.target_path');
            if (!$rediretUrl) {
                if ($this->container->get('security.authorization_checker')->isGranted('ROLE_STAFF')) {

                    if ($this->getUser()->getMustChangePassword()) {
                        $rediretUrl = $this->generateUrl('ibtikar_glance_ums_staff_changePassword');
                    } else {
                        $rediretUrl = $this->generateUrl('ibtikar_glance_dashboard_home');
                    }
                } else {
                    if ($this->getUser()->getMustChangePassword()) {
                        $rediretUrl = $this->generateUrl('change_password');
                    } else {
                        $rediretUrl = $this->generateUrl('visitor_view_profile');
                    }
                }
            }
        }
        $redirectResponse = $this->redirect($rediretUrl);

        $redirectResponse->headers->setCookie(new Cookie($this->container->getParameter('logged_cookie_name'), 'true', 0, '/', $this->container->getParameter('cookies_domain')));
        return $redirectResponse;
    }



    private function sendMail($user, $data) {
        $dm = $this->get('doctrine_mongodb')->getManager();

        $admin = $dm->getRepository($this->staffRepo)->findOneBy(array('admin' => true));


        $emailTemplate = $dm->getRepository('IbtikarGlanceUMSBundle:EmailTemplate')->findOneByName('deactive staff message');
        $currentTime = new \DateTime();
        $body = str_replace(
                array(
            '%fullname%',
            '%username%',
            '%message%',
            '%email%',
            '%day%',
            '%date%',
                ), array(
            $admin->__toString(),
            $user->__toString(),
            nl2br($data['message']),
            $user->getEmail(),
            $this->get('translator')->trans($currentTime->format('l')),
            $currentTime->format('d/m/Y')
                )
                , str_replace('%extra_content%', $emailTemplate->getTemplate(), $this->container->get('base_email')->getBaseRender($admin->getPersonTitle(), false)));
        $mailer = $this->container->get('swiftmailer.mailer.spool_mailer');
        $message = \Swift_Message::newInstance()
                ->setSubject($data['subject'] . ' (' . $user->getUsername() . ')')
                ->setFrom($this->container->getParameter('mailer_user'))
                ->setTo($admin->getEmail())
                ->setBody($body, 'text/html')
        ;
        $mailer->send($message);
    }

    /**
     * @author Mahmoud Mostafa <mahmoud.mostafa@ibtikar.net.sa>
     * @throws AccessDeniedException
     */
    public function accessDeniedAction() {
        throw new AccessDeniedException();
    }

    /**
     * @author Ahmad Gamal <a.gamal@ibtikar.net.sa>
     * @throws NotFoundException
     */
    public function frontendNotFoundAction() {
        throw $this->createNotFoundException('Wrong id');
    }

    /**
     * @author Mahmoud Mostafa <mahmoud.mostafa@ibtikar.net.sa>
     * @return Response
     */
    public function backendNotFoundAction() {
        $response = new Response();
        $response->setStatusCode(404);
        return $this->render('IbtikarGlanceUMSBundle:Exception:error.html.twig', array('exception' => new \Exception('Wrong id'), 'status_code' => 404), $response);
    }


    public function changePasswordAction(Request $request) {
//        $breadcrumbs = $this->get('white_october_breadcrumbs');
//        $breadcrumbs->addItem('backend-home', $this->generateUrl('backend_home'));
//        $breadcrumbs->addItem('Change Password', $this->generateUrl('ibtikar_glance_ums_staff_changePassword'));
        $passwordValidateErrorMessage= $this->trans("The Password must be at least {{ limit }} characters and numbers length",array(), 'validators');
        $passwordValidatePasswordMaxErrorMessage= $this->trans("The Password must be {{ limit }} maximum characters and numbers length",array(), 'validators');

        $user = $this->getUser();
        $formBuilder = $this->createFormBuilder($user, array(
                    'validation_groups' => array('change-password', 'old-password'),'attr'=>array('class'=>'dev-js-validation')
                ))
                ->setMethod('POST')
                ->add('oldPassword', PasswordType::class, array(
                    'attr' => array('autocomplete' => 'off', 'data-remove-password-validation' => 'true')
                ))
              ->add('userPassword', RepeatedType::class, array(
                    'type' => PasswordType::class,
                    'invalid_message' => 'The password fields must match.',
                    'required' => true,
                    'first_options' => array('label' => 'Password', 'attr' => array('autocomplete' => 'off', 'data-confirm-password' => '', 'data-rule-passwordMax' => '','data-rule-password'=>true,'data-msg-password'=>$passwordValidateErrorMessage,'data-msg-passwordMax'=>$passwordValidatePasswordMaxErrorMessage)),
                    'second_options' => array('label' => 'Repeat Password', 'attr' => array('autocomplete' => 'off', 'data-rule-equalTo' => 'input[data-confirm-password]', 'data-msg-equalTo' => $this->get('translator')->trans('The password fields must match.', array(), 'validators'), 'data-rule-passwordMax' => '','data-msg-passwordMax'=>$passwordValidatePasswordMaxErrorMessage,'data-rule-password'=>true,'data-msg-password'=>$passwordValidateErrorMessage)),
                ))
                ->add('Change', SubmitType::class);
        $form = $formBuilder->getForm();
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $dm = $this->get('doctrine_mongodb')->getManager();
                $user->setValidPassword();
                $user->setMustChangePassword(false);
                $dm->flush();

                if ($this->get('session')->get('firstLogin')) {
                    $redirectUrl = $this->generateUrl('ibtikar_glance_dashboard_home');
                    $this->get('session')->set('firstLogin', FALSE);
                } else {
                    $this->addFlash('success', $this->get('translator')->trans('done sucessfully'));
                    if ($this->get('security.authorization_checker')->isGranted('ROLE_VISITOR')) {
                        $redirectUrl = $this->generateUrl('visitor_view_profile');
                    } else {
                        $redirectUrl = $request->getUri();
                    }
                }
                return $this->redirect($redirectUrl);
            }
        }

        if ($this->get('security.authorization_checker')->isGranted('ROLE_STAFF') && $user->getMustChangePassword()) {
            $this->changePasswordView = $this->mustChangePassword;
            $this->get('session')->set('firstLogin', true);
        }

        return $this->render($this->changePasswordView, array(
                    'form' => $form->createView(),
                    'translationDomain' => 'messages'
        ));
    }


    public function changePasswordFromEmailAction(Request $request,$email, $token) {
        $user = $this->getUserByEmailAndChangePasswordToken($email, $token);
        $dm = $this->get('doctrine_mongodb')->getManager();
        if (!$user->getEmailVerified()) {
            $user->setEmailVerified(true);
            $dm->flush();
        }
        $emailValidateErrorMessage= $this->trans("The Password must be at least {{ limit }} characters and numbers length",array(), 'validators');
        $emailValidatePasswordMaxErrorMessage= $this->trans("The Password must be {{ limit }} maximum characters and numbers length",array(), 'validators');

        $formBuilder = $this->createFormBuilder($user, array(
                    'validation_groups' => 'change-password'
                ))
                ->setMethod('POST')
                ->add('userPassword', RepeatedType::class, array(
                    'type' => PasswordType::class,
                    'invalid_message' => 'The password fields must match.',
                    'required' => true,
                    'first_options' => array('label' => 'Password', 'attr' => array('autocomplete' => 'off', 'data-confirm-password' => '', 'data-rule-passwordMax' => '','data-rule-password'=>true,'data-msg-password'=>$emailValidateErrorMessage,'data-msg-passwordMax'=>$emailValidatePasswordMaxErrorMessage)),
                    'second_options' => array('label' => 'Repeat Password', 'attr' => array('autocomplete' => 'off', 'data-rule-equalTo' => 'input[data-confirm-password]', 'data-msg-equalTo' => $this->get('translator')->trans('The password fields must match.', array(), 'validators'), 'data-rule-passwordMax' => '','data-msg-passwordMax'=>$emailValidatePasswordMaxErrorMessage,'data-rule-password'=>true,'data-msg-password'=>$emailValidateErrorMessage)),
                ));
//                ->add('Change', SubmitType::class);
        $form = $formBuilder->getForm();
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $user->setValidPassword();
                $user->setMustChangePassword(false);
                $user->refreshForgotPasswordToken();
                $dm->flush();
                $request->getSession()->getFlashBag()->add('success', $this->get('translator')->trans('done sucessfully'));
                // manually login the user
                return $this->loginUser($user);
            }
        }
        return $this->render($this->changePasswordFromEmailView, array(
                    'form' => $form->createView(),
                    'translationDomain' => 'messages'
        ));
    }


    public function checkFieldUniqueAction(Request $request) {

        $securityContext = $this->container->get('security.authorization_checker');

//        $loggedInUser = $this->getUser();
//        if (!$loggedInUser) {
//            return new JsonResponse(array('status' => 'login'));
//        }

//        if (!$securityContext->isGranted('ROLE_ADMIN')) {
//            return new JsonResponse(array('status' => 'denied'));
//        }
        $fieledName = $request->get('fieldName');
        $fieledValue = $request->get('fieldValue');
        $id = $request->get('id');
        $em = $this->get('doctrine_mongodb')->getManager();
        if ($fieledName == 'email') {
            $fieledValue = strtolower($fieledValue);
        }
        $userCount = $em->createQueryBuilder('IbtikarGlanceUMSBundle:User')
                        ->field('deleted')->equals(FALSE)
                        ->field($fieledName)->equals(trim($fieledValue));
        if ($id) {
            $userCount = $userCount->field('id')->notEqual($id);
        }

        $userCount = $userCount->getQuery()->execute()->count();
        if ($userCount > 0) {
            return new JsonResponse(array('status' => 'success', 'unique' => FALSE, 'message' => $this->trans('not valid')));
        } else {
            return new JsonResponse(array('status' => 'success', 'unique' => TRUE, 'message' => $this->trans('valid')));
        }
    }

    public function getUserIdAction(Request $request) {
        return new Response($this->getUser()->getId());
    }

}
