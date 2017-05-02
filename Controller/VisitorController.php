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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Ibtikar\GlanceDashboardBundle\Document\Export;

class VisitorController extends UserController
{

    protected $translationDomain = 'visitor';
    protected $validationTranslationDomain = 'validators';

    protected function configureListColumns()
    {
        $this->allListColumns = array(
            "email" => array("searchFieldType" => "input", "type" => 'email'),
            "nickName" => array("searchFieldType" => "input"),
            "country" => array("isSortable" => false, "searchFieldType" => "country"),
            "city" => array("isSortable" => false, "searchFieldType" => "city"),
            "createdBy" => array("isSortable" => false),
            "gender" => array("type" => "translated"),
            "updatedAt" => array("type" => "date", "searchFieldType" => "date"),
            "createdAt" => array("type" => "date", "searchFieldType" => "date")
        );
        $this->defaultListColumns = array(
            "nickName",
            "createdAt",
        );
        $this->listViewOptions->setBundlePrefix("ibtikar_glance_ums_");
    }

    protected function configureListParameters(Request $request)
    {
        $queryBuilder = $this->createQueryBuilder('IbtikarGlanceUMSBundle')
                ->field('admin')->equals(false)
                ->field('deleted')->equals(false)
                ->field('id')->notEqual($this->getUser()->getId());
        if ($request->get('email')) {
            $queryBuilder->field('email')->equals(new \MongoRegex(('/' . preg_quote($request->get('email')) . '/i')));
        }
        if ($request->get('nickName')) {
            $queryBuilder->field('nickName')->equals(new \MongoRegex(('/' . preg_quote($request->get('nickName')) . '/i')));
        }
        $this->configureListColumns();

        $this->listViewOptions->setListQueryBuilder($queryBuilder);

        $this->listViewOptions->setDefaultSortBy("createdAt");
        $this->listViewOptions->setDefaultSortOrder("desc");
        $this->listViewOptions->setActions(array("Edit", "Delete"));
        $this->listViewOptions->setBulkActions(array("Delete", "Export"));
        $this->listViewOptions->setTemplate("IbtikarGlanceUMSBundle:Visitor:list.html.twig");
    }

    /**
     * @author Mahmoud Mostafa <mahmoud.mostafa@ibtikar.net.sa>
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function forgotPasswordAction(Request $request)
    {
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
                if ($forgotPasswordData['status'] === 'success') {
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

    public function createAction(Request $request)
    {
        $menus = array(array('type' => 'create', 'active' => true, 'linkType' => 'add', 'title' => 'add visitor'), array('type' => 'list', 'active' => FALSE, 'linkType' => 'list', 'title' => 'list visitor'));
        $breadCrumbArray = $this->preparedMenu($menus, 'ibtikar_glance_ums_');

        $loggedInUserRoles = $this->getUser()->getRoles();
        $translator = $this->get('translator');
        $ErrorMessage['imageSize'] = $translator->trans('File size must be less than 2mb', array(), $this->validationTranslationDomain);
        $ErrorMessage['imageExtension'] = $translator->trans('picture not correct.', array(), $this->validationTranslationDomain);
        $ErrorMessage['imageDimensions'] = $translator->trans('Image dimension must be more than 200*200', array(), $this->validationTranslationDomain);
        $ErrorMessage['emailvalidateErrorMessage'] = $this->trans("Please enter your valid and true email address", array(), $this->validationTranslationDomain);
        $ErrorMessage['mobileError'] = $this->trans("Please enter your number", array(), $this->validationTranslationDomain);
        $ErrorMessage['staffUsernameError'] = $this->trans("username should contains characters, numbers or dash only", array(), $this->validationTranslationDomain);
        $ErrorMessage['notValid'] = $this->trans("not valid");
        $ErrorMessage['emailvalidateErrorMessage'] = $this->trans("Invalid email address", array(), $this->validationTranslationDomain);

        $ErrorMessage['visitorNicknameMinError'] = $this->trans("Nickname should be more than 5 characters", array(), $this->translationDomain);
        $ErrorMessage['visitorNicknameMaxError'] = $this->trans("Nickname should be less than 50 characters", array(), $this->translationDomain);
        $ErrorMessage['notValid'] = $this->trans("not valid");
        $ErrorMessage['passwordValidateErrorMessage'] = $this->trans("The Password must be at least {{ limit }} characters and numbers length", array(), $this->validationTranslationDomain);
        $ErrorMessage['passwordValidatePasswordMaxErrorMessage'] = $this->trans("The Password must be {{ limit }} maximum characters and numbers length", array(), $this->validationTranslationDomain);

        $visitor = new Visitor();
        $securityContext = $this->get('security.authorization_checker');
        $form = $this->createForm(VisitorType::class, $visitor, array(
            'translation_domain' => $this->translationDomain, 'attr' => array('class' => 'dev-page-main-form dev-js-validation form-horizontal'),
            'validation_groups' => array('create', 'Default'),
            'container' => $this->container,
            'errorMessage' => $ErrorMessage,
            'edit' => FALSE,
            'userImage' => ''
        ));
        $dm = $this->get('doctrine_mongodb')->getManager();
        $countries = $dm->getRepository('IbtikarGlanceUMSBundle:Country')->findCountrySorted()->getQuery()->execute();
        $countryArray = array();
        foreach ($countries as $country) {
            $countryArray[strtolower($country->getCountryCode())] = $country->getCountryName();
        }

        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $randPass = $visitor->generate_password();
                $visitor->setUserPassword($randPass);
                $dm->persist($visitor);
                $dm->flush();

                $emailTemplate = $dm->getRepository('IbtikarGlanceDashboardBundle:EmailTemplate')->findOneByName('add frontent user');

                $body = str_replace(
                    array(
                    '%user-name%',
                    '%email%',
                    '%password%',
                    '%loginUrlAr%',
                    '%loginUrlEn%',
                    ), array(
                    $visitor->getNickName(),
                    $visitor->getEmail(),
                    $randPass,
                    $this->generateUrl('ibtikar_goody_frontend_login', array('_locale' => 'en'), UrlGeneratorInterface::ABSOLUTE_URL),
                    ), str_replace('%message%', $emailTemplate->getTemplate(), $this->container->get('frontend_base_email')->getBaseRender2($visitor->getPersonTitle(), false))
                );
                $mailer = $this->get('swiftmailer.mailer.spool_mailer');
                $message = \Swift_Message::newInstance()
                    ->setSubject($emailTemplate->getSubject())
                    ->setFrom($this->container->getParameter('mailer_user'))
                    ->setTo($visitor->getEmail())
                    ->setBody($body, 'text/html')
                ;
                $mailer->send($message);
                $this->addFlash('success', $this->get('translator')->trans('done sucessfully'));
                return $this->redirect($request->getUri());
            }
//            else {
//
//                \Doctrine\Common\Util\Debug::dump((string) $form->getErrors(true, false));
//                exit;
//            }
        }
        return $this->render('IbtikarGlanceUMSBundle:Visitor:create.html.twig', array(
                'form' => $form->createView(),
                'title' => $this->trans('add visitor', array(), $this->translationDomain),
                'formType' => 'create',
                'breadcrumb' => $breadCrumbArray,
                'countries' => json_encode($countryArray),
                'translationDomain' => $this->translationDomain
        ));
    }

    public function editAction(Request $request, $id)
    {
        $menus = array(array('type' => 'create', 'active' => true, 'linkType' => 'add', 'title' => 'add visitor'), array('type' => 'list', 'active' => FALSE, 'linkType' => 'list', 'title' => 'list visitor'));
        $breadCrumbArray = $this->preparedMenu($menus, 'ibtikar_glance_ums_');

        $loggedInUserRoles = $this->getUser()->getRoles();
        $translator = $this->get('translator');
        $ErrorMessage['imageSize'] = $translator->trans('File size must be less than 2mb', array(), $this->validationTranslationDomain);
        $ErrorMessage['imageExtension'] = $translator->trans('picture not correct.', array(), $this->validationTranslationDomain);
        $ErrorMessage['imageDimensions'] = $translator->trans('Image dimension must be more than 200*200', array(), $this->validationTranslationDomain);
        $ErrorMessage['emailvalidateErrorMessage'] = $this->trans("Please enter your valid and true email address", array(), $this->validationTranslationDomain);
        $ErrorMessage['mobileError'] = $this->trans("Please enter your number", array(), $this->validationTranslationDomain);
        $ErrorMessage['staffUsernameError'] = $this->trans("username should contains characters, numbers or dash only", array(), $this->validationTranslationDomain);
        $ErrorMessage['notValid'] = $this->trans("not valid");
        $ErrorMessage['emailvalidateErrorMessage'] = $this->trans("Invalid email address", array(), $this->validationTranslationDomain);

        $ErrorMessage['visitorNicknameMinError'] = $this->trans("Nickname should be more than 5 characters", array(), $this->translationDomain);
        $ErrorMessage['visitorNicknameMaxError'] = $this->trans("Nickname should be less than 50 characters", array(), $this->translationDomain);
        $ErrorMessage['notValid'] = $this->trans("not valid");
        $ErrorMessage['passwordValidateErrorMessage'] = $this->trans("The Password must be at least {{ limit }} characters and numbers length", array(), $this->validationTranslationDomain);
        $ErrorMessage['passwordValidatePasswordMaxErrorMessage'] = $this->trans("The Password must be {{ limit }} maximum characters and numbers length", array(), $this->validationTranslationDomain);
        $ErrorMessage['passwordMatch'] = $this->trans('The password fields must match.', array(), $this->validationTranslationDomain);
        $dm = $this->get('doctrine_mongodb')->getManager();

        $visitor = $dm->getRepository('IbtikarGlanceUMSBundle:Visitor')->find($id);
        if (!$visitor || $visitor->getDeleted()) {
            throw $this->createNotFoundException($this->trans('Wrong id'));
        }

        $userImage = $visitor->getWebPath();
        $userImageAlt = $visitor->__toString();
        $securityContext = $this->get('security.authorization_checker');
        $form = $this->createForm(VisitorType::class, $visitor, array(
            'translation_domain' => $this->translationDomain, 'attr' => array('class' => 'dev-page-main-form dev-js-validation form-horizontal'),
            'validation_groups' => array('create', 'Default'),
            'container' => $this->container,
            'errorMessage' => $ErrorMessage,
            'edit' => TRUE,
            'userImage' => ''
        ));

        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {

                $visitor->setValidPassword();
                $dm->flush();

                $userImage = $visitor->getWebPath();
                $form = $this->createForm(VisitorType::class, $visitor, array(
                    'translation_domain' => $this->translationDomain, 'attr' => array('class' => 'dev-page-main-form dev-js-validation form-horizontal'),
                    'validation_groups' => array('create', 'Default'),
                    'container' => $this->container,
                    'errorMessage' => $ErrorMessage,
                    'edit' => true,
                    'userImage' => $userImage
                ));
                $this->addFlash('success', $this->get('translator')->trans('done sucessfully'));
            } else {
                $dm = $this->get('doctrine_mongodb')->getManager()->refresh($this->getUser());
            }
        }
        return $this->render('IbtikarGlanceUMSBundle:Visitor:create.html.twig', array(
                'form' => $form->createView(),
                'title' => $this->trans('edit visitor', array(), $this->translationDomain),
                'breadcrumb' => $breadCrumbArray,
                'formType' => 'edit',
                'translationDomain' => $this->translationDomain
        ));
    }

    public function getUsersNamesAction(Request $request)
    {

        $loggedInUser = $this->getUser();
        if (!$loggedInUser) {
            return new JsonResponse(array('status' => 'login'));
        }


        $dm = $this->get('doctrine_mongodb')->getManager();
        $names = array();
        $query = $dm->createQueryBuilder('IbtikarGlanceUMSBundle:Visitor')
                ->select($request->get('type', 'nickName'))
                ->field('deleted')->equals(FALSE)
                ->field($request->get('type', 'nickName'))->equals(new \MongoRegex('/' . preg_quote(trim($request->get('name'))) . '/i'))
                ->limit(5)->hydrate(false);



        $result = $query->getQuery()->execute();

        foreach ($result->toArray() as $row) {
            if (isset($row[$request->get('type', 'nickName')])) {
                $names[] = $row[$request->get('type', 'nickName')];
            }
        }
        return new JsonResponse($names);
    }

    protected function postDelete($ids)
    {

        if (!is_array($ids)) {
            $ids = array($ids);
        }

        $dm = $this->get('doctrine_mongodb')->getManager();

        $emailTemplate = $dm->getRepository('IbtikarGlanceDashboardBundle:EmailTemplate')->findOneByName('visitor delete');

        $dm->getFilterCollection()->disable('soft_delete');

        $users = $dm->createQueryBuilder('IbtikarGlanceUMSBundle:Visitor')
                ->field('admin')->equals(false)
                ->field('deleted')->equals(true)
                ->field('id')->in($ids)
                ->getQuery()->execute();

        foreach ($users as $visitor) {
            $body = str_replace(
                array(
                '%user-name%',
                ), array(
                $visitor,
                ), str_replace('%message%', $emailTemplate->getTemplate(), $this->container->get('frontend_base_email')->getBaseRender2($visitor->getPersonTitle(), false))
            );
            $mailer = $this->get('swiftmailer.mailer.spool_mailer');
            $message = \Swift_Message::newInstance()
                ->setSubject($emailTemplate->getSubject())
                ->setFrom($this->container->getParameter('mailer_user'))
                ->setTo($visitor->getEmail())
                ->setBody($body, 'text/html')
            ;
            $mailer->send($message);
        }
    }

    public function exportAction(Request $request)
    {


        $this->listViewOptions = $this->get("list_view");
        $this->listViewOptions->setListType("list");
        $renderingParams = $this->doList($request);
        $securityContext = $this->container->get('security.authorization_checker');

        $loggedInUser = $this->getUser();
        if (!$loggedInUser) {
            return new JsonResponse(array('status' => 'login'));
        }

        if (!$securityContext->isGranted('ROLE_ADMIN') && !$securityContext->isGranted('ROLE_VISITOR_EXPORT')) {
            return new JsonResponse(array('status' => 'denied'));
        }

        $ids = $request->get('ids', array());

        $params = $request->query->all();
        if (!empty($ids)) {
            $params['ids'] = $ids;
        }
//
//        $ext = $params['ext'];
//        unset($params['ext']);

        $export = new Export();
        $export->setName(uniqid("visitors-"));
        $export->setParams($params);
        $export->setExtension('xls');
        $export->setFields($this->getCurrentColumns('ibtikar_glance_ums_visitor_list'));
        $export->setState(Export::READY);
        $export->setType(Export::VISITORS);

        $dm = $this->get('doctrine_mongodb')->getManager();
        $dm->persist($export);
        $dm->flush();

        return new JsonResponse(array('status' => 'success', 'message' => $this->get('translator')->trans('file export will take sometime', array(), $this->translationDomain)));
    }
}
