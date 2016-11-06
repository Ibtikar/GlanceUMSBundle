<?php

namespace Ibtikar\GlanceUMSBundle\Controller;

use Ibtikar\GlanceUMSBundle\Controller\UserController;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Ibtikar\GlanceUMSBundle\Document\Staff;
use Ibtikar\GlanceUMSBundle\Document\Job;
use Ibtikar\GlanceDashboardBundle\Document\Document;
use Ibtikar\GlanceDashboardBundle\Service\ArabicMongoRegex;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Ibtikar\GlanceUMSBundle\Form\StaffType;

class StaffController extends UserController {

    protected $repoClass = 'IbtikarGlanceUMSBundle:Staff';
    protected $loginView = 'IbtikarGlanceUMSBundle:Staff:login.html.twig';
    protected $changePasswordFromEmailView = 'IbtikarGlanceUMSBundle:Staff:changePasswordFromEmail.html.twig';
    protected $changePasswordView = 'IbtikarGlanceUMSBundle:Staff:changePassword.html.twig';
    protected $translationDomain = 'staff';
    private $validationTranslationDomain = 'validators';
    public $oneItem = 'Staff member';

    protected function configureListColumns() {
        $this->allListColumns = array(
            "firstName" => array("searchFieldType"=>"input"),
            "lastName" => array("searchFieldType"=>"input"),
            "email" => array("searchFieldType"=>"input","type"=>'email'),
            "username" => array("searchFieldType"=>"input"),
            "job" => array("isSortable"=>false,"searchFieldType"=>"select"),
            "role" => array("isSortable"=>false,"type"=>"many"),
            "country" => array("isSortable"=>false,"searchFieldType"=>"country"),
            "city" => array("isSortable"=>false,"searchFieldType"=>"city"),
            "editDate" => array("type"=>"date","searchFieldType"=>"date"),
            "createdAt" => array("type"=>"date","searchFieldType"=>"date")
        );
        $this->defaultListColumns = array(
            "username",
            "createdAt",
            "role",
        );
    $this->listViewOptions->setBundlePrefix("ibtikar_glance_ums_");

    }

    protected function configureListParameters(Request $request) {
        $queryBuilder = $this->createQueryBuilder('IbtikarGlanceUMSBundle')
                        ->field('admin')->equals(false)
                        ->field('deleted')->equals(false)
                        ->field('id')->notEqual($this->getUser()->getId());

        $this->configureListColumns();

        $this->listViewOptions->setListQueryBuilder($queryBuilder);

        $this->listViewOptions->setDefaultSortBy("createdAt");
        $this->listViewOptions->setDefaultSortOrder("desc");
        $this->listViewOptions->setActions(array ("Edit","Delete"));
        $this->listViewOptions->setBulkActions(array("Delete"));
        $this->listViewOptions->setTemplate("IbtikarGlanceUMSBundle:Staff:list.html.twig");
    }



    /**
     * @author Ola <ola.ali@ibtikar.net.sa>
     * @param Request $request
     * @return type
     */

    public function forgotPasswordAction(Request $request) {
        $session = $request->getSession();
        $error = null;
        $success = null;
        $emailRequiredErrorMessage= $this->trans("Please enter your email address",array(), $this->translationDomain);
        $emailvalidateErrorMessage= $this->trans("Please enter your valid email address",array(), $this->translationDomain);

        $formBuilder = $this->createFormBuilder()
                ->setMethod('POST')
                ->add('loginCredentials', EmailType::class, array('attr' => array('autocomplete' => 'off', 'data-msg-required'=> $emailRequiredErrorMessage,'data-msg-email'=>$emailvalidateErrorMessage,'data-rule-email'=>"true"), 'constraints' => array(new Constraints\NotBlank(), new Constraints\Email())));

        $form = $formBuilder->getForm();
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $data = $form->getData();
                $loginCredentials = $data['loginCredentials'];
                $dm = $this->get('doctrine_mongodb')->getManager();
                $staffRepo = $dm->getRepository('IbtikarGlanceUMSBundle:Staff');
                $staff = null;
                // check if the user entered his user name, change the regex in Staff document

                    $staff = $staffRepo->findOneBy(array('email' => strtolower($loginCredentials), 'deleted' => false));
                    if (!$staff) {
                        $error = $this->trans('Email was not found, please provide correct email.',array(),  $this->translationDomain);
                    }
                if ($staff) {
                    if ($staff->getEnabled()) {
                        $staff->refreshForgotPasswordToken();
                        $dm->flush();

                        $emailTemplate = $dm->getRepository('IbtikarGlanceDashboardBundle:EmailTemplate')->findOneByName('staff forgot password');
                        $body = str_replace(
                                array(
                            '%fullname%',
                            '%extraInfo%',
                            '%smallMessage%',
                            '%message%',
                            '%change_password_url%'
                                ), array(
                            $staff->__toString(),
                            '',
                            '',
                            $emailTemplate->getMessage(),
                            $this->generateUrl('ibtikar_glance_ums_staff_change_password_from_email', array('token' => $staff->getChangePasswordToken(), 'email' => $staff->getEmail()), UrlGeneratorInterface::ABSOLUTE_URL)
                                ), str_replace('%extra_content%', $emailTemplate->getTemplate(), $this->get('base_email')->getBaseRender($staff->getPersonTitle(), false))
                        );
                        $mailer = $this->get('swiftmailer.mailer.spool_mailer');
                        $message = \Swift_Message::newInstance()
                                ->setSubject($emailTemplate->getSubject())
                                ->setFrom($this->container->getParameter('mailer_user'))
                                ->setTo($staff->getEmail())
                                ->setBody($body, 'text/html')
                        ;
                        $mailer->send($message);
                        // set success message
                        $success = $this->trans('A message will be sent to your email containing a link to allow you to change your password.',array(),  $this->translationDomain);
                    } else {
                        $error = str_replace(array('%user-title%', '%user-name%'), array($staff->getPersonTitle()->getName(), $staff->__toString()), $this->trans('your account was deactivated by the admin, contact your admin for more information.',array(),  $this->translationDomain));
                    }
                }
            }

        }
        return $this->render('IbtikarGlanceUMSBundle:Staff:forgotPassword.html.twig', array(
                    'success' => $success,
                    'error' => $error,
                    'form' => $form->createView(),
                    'translationDomain' => $this->translationDomain
        ));
    }


    /**
     * @author Ola <ola.ali@ibtikar.net.sa>
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function createAction(Request $request) {
        $menus = array(array('type' => 'create', 'active' => true, 'linkType' => 'add', 'title' => 'add staff'), array('type' => 'list', 'active' => FALSE, 'linkType' => 'list', 'title' => 'list staff'));
        $breadCrumbArray = $this->preparedMenu($menus,'ibtikar_glance_ums_');

        $loggedInUserRoles = $this->getUser()->getRoles();
        $translator = $this->get('translator');
        $ErrorMessage['imageSize'] = $translator->trans('File size must be less than 3mb', array(), $this->validationTranslationDomain);
        $ErrorMessage['imageExtension'] = $translator->trans('picture not correct.', array(), $this->validationTranslationDomain);
        $ErrorMessage['imageDimensions'] = $translator->trans('Image dimension must be more than 200*200', array(), $this->validationTranslationDomain);
        $ErrorMessage['emailvalidateErrorMessage']= $this->trans("Please enter your valid and true email address",array(), $this->validationTranslationDomain);
        $ErrorMessage['mobileError']= $this->trans("Please enter your number",array(), $this->validationTranslationDomain);
        $ErrorMessage['staffUsernameError']= $this->trans("username should contains characters, numbers or dash only",array(), $this->validationTranslationDomain);
        $ErrorMessage['notValid']= $this->trans("not valid");

        $staff = new Staff();
        $securityContext = $this->get('security.authorization_checker');
        $form = $this->createForm(StaffType::class, $staff, array(
            'translation_domain' => $this->translationDomain, 'attr' => array('class' => 'dev-page-main-form dev-js-validation form-horizontal'),
            'validation_groups' => array('create', 'Default'),
            'container' => $this->container,
            'errorMessage' => $ErrorMessage,
            'edit' => FALSE,
            'userImage' => ''
        ));
        $dm = $this->get('doctrine_mongodb')->getManager();
        $countries=$dm->getRepository('IbtikarGlanceDashboardBundle:Country')->findCountrySorted() ->getQuery()->execute();
        $countryArray=array();
        foreach ($countries as $country) {
            $countryArray[strtolower($country->getCountryCode())]=$country->getCountryName();

        }

        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $randPass= $staff->generate_password();
                $staff->setUserPassword($randPass);
                $dm->persist($staff);
                $dm->flush();

                $emailTemplate = $dm->getRepository('IbtikarGlanceDashboardBundle:EmailTemplate')->findOneByName('add backend user');

                $body = str_replace(
                        array(
                    '%smallMessage%',
                    '%extraInfo%',
                    '%fullname%',
                    '%username%',
                    '%password%',
                    '%message%',
                    '%login_url%',
                    '%job%',
                     '%color%',
                        ), array($emailTemplate->getSmallMessage(),$emailTemplate->getExtraInfo(),
                    $staff->__toString(),
                    $staff->getUsername(),
                    $randPass,
                    $emailTemplate->getMessage(),
                    $this->generateUrl('ibtikar_glance_ums_staff_login', array(), UrlGeneratorInterface::ABSOLUTE_URL),
                    $staff->getJob()?$staff->getJob()->getTitle():'',
                    $this->container->getParameter('themeColor')
                        ), str_replace('%extra_content%', $emailTemplate->getTemplate(), $this->get('base_email')->getBaseRender($staff->getPersonTitle()))
                );
                $mailer = $this->get('swiftmailer.mailer.spool_mailer');
                $message = \Swift_Message::newInstance()
                        ->setSubject($emailTemplate->getSubject())
                        ->setFrom($this->container->getParameter('mailer_user'))
                        ->setTo($staff->getEmail())
                        ->setBody($body, 'text/html')
                ;
                $mailer->send($message);
                $this->addFlash('success', $this->get('translator')->trans('done sucessfully'));
                return $this->redirect($request->getUri());
            }
        }
        return $this->render('IbtikarGlanceUMSBundle:Staff:create.html.twig', array(
                'form' => $form->createView(),
                'title' => $this->trans('add staff',array(),  $this->translationDomain),
                'formType'=>'create',
                'breadcrumb'=>$breadCrumbArray,
                'countries' => json_encode($countryArray),
                'countryCodes' => json_encode(array_keys($countryArray)),
                'translationDomain' => $this->translationDomain
        ));
    }

    public function editListAction(Request $request) {
        $dm = $this->get('doctrine_mongodb')->getManager();

        if ($request->getMethod() === 'POST') {
            $securityContext = $this->container->get('security.authorization_checker');
            $users   = $request->get('users', array());
            $groupId = $request->get('id');

            if (!$securityContext->isGranted('ROLE_STAFF_VIEW') && !$securityContext->isGranted('ROLE_ADMIN')) {
                return new JsonResponse(array('message'=>'failed operation','status' => 'error'));
            }

            $actions=array();
            if(!empty($users)){
                foreach($users as $user){
                    $action=$user['action'];
                    if(!isset($action)){
                        $actions[$action]=array();
                    }
                    $actions[$action][]=$user['id'];
                }

                foreach($actions as $action => $ids){
                    $this->staffGroupUpdate($action,$ids,$groupId);
                }

            }
            return new JsonResponse(array('message'=>'done sucessfully','status' => 'success'));
        }

        $limit = $request->get('limit',$this->container->getParameter('per_page_items'));

        $query = $dm->createQueryBuilder('IbtikarGlanceUMSBundle:Staff')
                ->field('admin')->equals(false)
                ->field('deleted')->equals(false)
                ->field('id')->notEqual($this->getUser()->getId())
                ->getQuery();
//        die(var_dump($query->execute()->toArray()));
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
                $query, $this->get('request')->query->get('page', 1)/* page number */, $limit/* limit per page */
        );
        // parameters to template
        return $this->render('IbtikarGlanceUMSBundle:Staff:editList.html.twig', array(
                    'pagination' => $pagination,
                    'paginationData' => $pagination->getPaginationData(),
                    'translationDomain' => $this->translationDomain,
                    'id' => $request->get('id')
        ));
    }

/**
 * @author Gehad Mohamed <gehad.mohamed@ibtikar.net.sa>
 *
 * @param \Symfony\Component\HttpFoundation\Request $request
 * @return type
 */

    public function exportAction(Request $request) {
            $this->configureListColumns();
        $securityContext = $this->container->get('security.authorization_checker');

        $loggedInUser = $this->getUser();
        if (!$loggedInUser) {
            return new JsonResponse(array('status' => 'login'));
        }

        if (!$securityContext->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(array('status' => 'denied'));
        }

        $ids = $request->get('ids', array());

        $dm = $this->get('doctrine_mongodb')->getManager();

        $queryBuilder = $dm->createQueryBuilder('IbtikarGlanceUMSBundle:Staff')
                ->field('id')->notEqual($this->getUser()->getId())
                ->field('deleted')->equals(false);

//        if ($request->get('group')) {
//            $queryBuilder = $queryBuilder->field('group')->equals($request->get('group'));
//        }
//        if ($request->get('name')) {
//            $queryBuilder = $queryBuilder->field('fullname')->equals(new \MongoRegex(('/' . $request->get('name') . '/i')));
//        }
//        if ($request->get('job')) {
//            $queryBuilder = $queryBuilder->field('job')->equals($request->get('job'));
//        }
//        if ($request->get('status')) {
//            $enabledValue = ($request->get('status') == 'active') ? true : false;
//            $queryBuilder = $queryBuilder->field('enabled')->equals($enabledValue);
//        }
        $parameters = $request->query->all();
        $this->configureListColumns();
        $columns = $this->allListColumns;
        if ($parameters) {
            $columnsList = array_keys($this->allListColumns);
            foreach ($parameters as $columnName => $ColumnValue) {
                    if (in_array($columnName, $columnsList)) {
                    if ($columns[$columnName]['searchFieldType'] == 'input' && $request->get($columnName)) {
                        $queryBuilder = $queryBuilder->field($columnName)->equals(new \MongoRegex(('/' . preg_quote(trim($ColumnValue)) . '/i')));
                    } elseif ($columns[$columnName]['searchFieldType'] == 'select' && $request->get($columnName)) {
                        $queryBuilder = $queryBuilder->addAnd($queryBuilder->expr()->field($columnName)->equals($ColumnValue));
                    } elseif ($columns[$columnName]['searchFieldType'] == 'country' && $request->get($columnName)) {
                        $queryBuilder = $queryBuilder->addAnd($queryBuilder->expr()->field($columnName)->equals($ColumnValue));
                    } elseif ($columns[$columnName]['searchFieldType'] == 'city' && $request->get($columnName)) {
                        $queryBuilder = $queryBuilder->addAnd($queryBuilder->expr()->field($columnName)->equals($ColumnValue));
                    } elseif ($columns[$columnName]['searchFieldType'] == 'gender' && $request->get($columnName)) {
                        $queryBuilder = $queryBuilder->addAnd($queryBuilder->expr()->field($columnName)->equals($ColumnValue));
                    } elseif ($columns[$columnName]['searchFieldType'] == 'phone' && $request->get($columnName)) {
                        $queryBuilder = $queryBuilder->field('mobile.phone')->equals(new \MongoRegex(('/' . preg_quote(trim($ColumnValue)) . '/i')));
                    }
                }
            }

             if ($request->get('name')) {
                $queryBuilder = $queryBuilder->field('fullname')->equals(new \MongoRegex(('/' .  preg_quote(trim($request->get('name'))) . '/i')));
            }
            if ($request->get('status')) {
                $status = ($request->get('status') == 'active') ? true : false;
                $queryBuilder = $queryBuilder->field('enabled')->equals($status);
            }
        }
        if(count($ids) > 0) {
            $queryBuilder = $queryBuilder->field('id')->in($ids);
        }

        $queryBuilder = $queryBuilder->sort($request->get('sort','firstName'), $request->get('direction','asc'));

        $result = $queryBuilder->getQuery()->execute();
        $createExcel= $this->get('create_excel');
        $createExcel->setCollection($result);
        $createExcel->setFields(array_reverse($this->getCurrentColumns('staff_list')));
        $createExcel->setTitle($this->get('translator')->trans('exported staff file', array(), $this->translationDomain));
        $createExcel->setTranslationDomain($this->translationDomain);


        return $createExcel->createFileResponse();
    }


    protected function validateActivate(Document $document, $status) {
        $errorMessage = parent::validateActivate($document, $status);
        if($document->getAdmin()) {
            return $this->trans('failed operation');
        }
        if ($errorMessage) {
            return $errorMessage;
        }
        if (!$document->getGroup() && !$document->getRole()) {
            return $this->trans('Missing group and role.');
        }
    }

    protected function validateDeactivate(Document $document, $status) {
        if($document->getAdmin()) {
            return $this->trans('failed operation');
        }
    }



  /**
   * @author Ola <ola.ali@ibtikar.net.sa>
   * @param Request $request
   * @param type $id
   * @return type
   */
    public function editAction(Request $request, $id) {
        $menus = array(array('type' => 'create', 'active' => true, 'linkType' => 'add', 'title' => 'add staff'), array('type' => 'list', 'active' => FALSE, 'linkType' => 'list', 'title' => 'list staff'));
        $breadCrumbArray = $this->preparedMenu($menus, 'ibtikar_glance_ums_');
        $loggedInUserRoles = $this->getUser()->getRoles();
        $translator = $this->get('translator');
        $ErrorMessage['imageSize'] = $translator->trans('File size must be less than 3mb', array(), $this->validationTranslationDomain);
        $ErrorMessage['imageExtension'] = $translator->trans('picture not correct.', array(), $this->validationTranslationDomain);
        $ErrorMessage['imageDimensions'] = $translator->trans('Image dimension must be more than 200*200', array(), $this->validationTranslationDomain);
        $ErrorMessage['emailvalidateErrorMessage'] = $this->trans("Please enter your valid and true email address", array(), $this->validationTranslationDomain);
        $ErrorMessage['mobileError'] = $this->trans("Please enter your number", array(), $this->validationTranslationDomain);
        $ErrorMessage['staffUsernameError'] = $this->trans("username should contains characters, numbers or dash only", array(), $this->validationTranslationDomain);
        $ErrorMessage['notValid'] = $this->trans("not valid");
        $ErrorMessage['passwordValidateErrorMessage'] = $this->trans("The Password must be at least {{ limit }} characters and numbers length", array(), $this->validationTranslationDomain);
        $ErrorMessage['passwordValidatePasswordMaxErrorMessage'] = $this->trans("The Password must be {{ limit }} maximum characters and numbers length", array(), $this->validationTranslationDomain);
        $ErrorMessage['passwordMatch'] = $this->trans('The password fields must match.', array(), $this->validationTranslationDomain);

        $dm = $this->get('doctrine_mongodb')->getManager();

        $staff = $dm->getRepository('IbtikarGlanceUMSBundle:Staff')->find($id);
        if (!$staff) {
            throw $this->createNotFoundException($this->trans('Wrong id'));
        }

        $userImage = $staff->getWebPath();
        $userImageAlt = $staff->__toString();
        $securityContext = $this->get('security.authorization_checker');
        $form = $this->createForm(StaffType::class, $staff, array(
            'translation_domain' => $this->translationDomain, 'attr' => array('class' => 'dev-page-main-form dev-js-validation form-horizontal'),
            'validation_groups' => array('edit', 'Default'),
            'container' => $this->container,
            'errorMessage' => $ErrorMessage,
            'edit' => true,
            'userImage' => &$userImage
        ));
        $countries = $dm->getRepository('IbtikarGlanceDashboardBundle:Country')->findCountrySorted()->getQuery()->execute();
        $countryArray = array();
        foreach ($countries as $country) {
            $countryArray[strtolower($country->getCountryCode())] = $country->getCountryName();
        }
        $oldMobile='';
        if($staff->getMobile()->getPhone()){
         $oldMobile=$staff->getMobile()->getPhone();
        }
        $oldRoles = array();
        foreach ($staff->getRole() as $role) {
            $oldRoles[] = $role->getId();
        }
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $formData = $request->get('staff');

                $staff->setValidPassword();
                $forceLogout=FALSE;


                if ($id !== $this->getUser()->getId()) {
                    $uow = $dm->getUnitOfWork();
                    $uow->computeChangeSets();
                    $changeset = $uow->getDocumentChangeSet($staff);
//                     \Doctrine\Common\Util\Debug::dump($changeset);
//                     exit;
                    $emailTemplate = $dm->getRepository('IbtikarGlanceDashboardBundle:EmailTemplate')->findOneByName('edit staff');

                    $record = $emailTemplate->getEmailDataRecord();

                    $content = '';
                    $updateField = array('country', 'city', 'email', 'password', 'firstName', 'lastName', 'fullname', 'job'
                        , 'username', 'gender');
                    $i = 1;
                    foreach ($changeset as $key => $change) {

                        $staff->updateStaffCountOnEdit($key, $change);
//                            $renderer = $this->get("string_utilities");
                        if (in_array($key, array('email', 'password'))) {
                            $forceLogout = true;
                        }
                        if ($i % 2 == 0) {
                            $tdColor = '#f8f8f8';
                        } else {
                            $tdColor = '';
                        }
                        if ($key == 'gender') {
                            $change[1] = $translator->trans(trim($change[1] . PHP_EOL), array(), $this->translationDomain);

                            $content.= str_replace(array('%updatedfield%', '%value%'), array($translator->trans($key, array(), $this->translationDomain), $key !== "password" ? $change[1] . PHP_EOL : $staff->getUserPassword()), $record);
                        } elseif ($key == 'mobile') {
                            if ($change[1]->getPhone() != $oldMobile) {
                                $content.= str_replace(array('%updatedfield%', '%value%', '%tdColor%'), array($translator->trans($key, array(), $this->translationDomain), $change[1], $tdColor), $record);
                            }
                        } elseif ($key != 'fullname' && $key != 'image' && in_array($key, $updateField)) {
                            $content.= str_replace(array('%updatedfield%', '%value%', '%tdColor%'), array($translator->trans($key, array(), $this->translationDomain), $key !== "password" ? $change[1] . PHP_EOL : $staff->getUserPassword(), $tdColor), $record);
                        }
                        $i++;
                    }

//                    $staff->setForceLogout($forceLogout);
                    $staff->setEditDate(new \DateTime());
                    $dm->flush();
                    $newRoles=array();
                    $newRolesNames=array();
                    foreach ($staff->getRole() as $role) {
                        $newRoles[] = $role->getId();
                        $newRolesNames[] = $role->__toString();
                    }
                    if (count(array_diff($oldRoles, $newRoles)) > 0) {

                        $content.= str_replace(array('%updatedfield%', '%value%'), array($translator->trans('Role', array(), $this->translationDomain), implode(',', $newRolesNames) . PHP_EOL), $record);
                    }


                    if (strlen($content) > 0) {
                        $body = str_replace(
                            array(
                            '%smallMessage%',
                            '%extraInfo%',
                            '%color%',
                            '%fullname%',
                            '%username%',
                            '%message%',
                            ), array($emailTemplate->getSmallMessage(),
                            $emailTemplate->getExtraInfo(),
                            $this->container->getParameter('themeColor'),
                            $staff->__toString(),
                            $staff->getUsername(),
                            $emailTemplate->getMessage(),
                            ), str_replace('%extra_content%', $content, $this->get('base_email')->getBaseRender($staff->getPersonTitle()))
                        );

                        $mailer = $this->get('swiftmailer.mailer.spool_mailer');
                        $message = \Swift_Message::newInstance()
                            ->setSubject($emailTemplate->getSubject())
                            ->setFrom($this->container->getParameter('mailer_user'))
                            ->setTo($staff->getEmail())
                            ->setBody($body, 'text/html')
                        ;
                        $mailer->send($message);
                    }
                }

//                $imageOperations = $this->get('image_operations');
//                $imageOperations->autoRotate($staff->getAbsolutePath());
//                if ($staff->getImageNeedResize()) {
//                    $imageOperations->SquareImageResize($staff->getAbsolutePath());
//                }
//                $imageOperations->autoRotate($staff->getCoverPhotoAbsolutePath());

                $userImage = $staff->getWebPath();
                $form = $this->createForm(StaffType::class, $staff, array(
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
        return $this->render('IbtikarGlanceUMSBundle:Staff:create.html.twig', array(
                'form' => $form->createView(),
                'title' => $this->trans('edit staff', array(), $this->translationDomain),
                'breadcrumb' => $breadCrumbArray,
                'countries' => json_encode($countryArray),
                'formType'=>'edit',
                'countryCodes' => json_encode(array_keys($countryArray)),
                'translationDomain' => $this->translationDomain
        ));
    }

    /**
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @author Maisara Khedr
     */
    public function getNamesAction(Request $request) {
        $securityContext = $this->container->get('security.authorization_checker');

        $loggedInUser = $this->getUser();
        if (!$loggedInUser) {
            return new JsonResponse(array('status' => 'login'));
        }

        if (!$securityContext->isGranted('ROLE_STAFF_VIEW') && !$securityContext->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(array('status' => 'denied'));
        }

        $dm = $this->get('doctrine_mongodb')->getManager();
        $names = array();
        $query = $dm->createQueryBuilder('IbtikarGlanceUMSBundle:Staff')
                ->select('fullname')
                ->field('admin')->equals(false)
                ->field('deleted')->equals(false)
                ->field('fullname')->equals( new \MongoRegex('/' . $request->get('name') . '/i'))
                ->field('id')->notEqual($this->getUser()->getId())
                ->limit($this->container->getParameter('autoCompelet_display_item'))
                ->hydrate(false)
                ->getQuery();
        $result = $query->execute();

        foreach ($result->toArray() as $row) {
            if (isset($row["fullname"])) {
                $names[] = $row["fullname"];
            }
        }
        return new JsonResponse($names);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @author Ahmad Gamal <a.gamal@ibtikar.net.sa>
     */
    public function getUsersNamesAction(Request $request) {
        $securityContext = $this->container->get('security.authorization_checker');

        $loggedInUser = $this->getUser();
        if (!$loggedInUser) {
            return new JsonResponse(array('status' => 'login'));
        }

//        if (!$securityContext->isGranted('ROLE_STAFF_VIEW') && !$securityContext->isGranted('ROLE_ADMIN')) {
//            return new JsonResponse(array('status' => 'denied'));
//        }

        $dm = $this->get('doctrine_mongodb')->getManager();
        $names = array();
        $query = $dm->createQueryBuilder('IbtikarUserBundle:User')
                ->select('fullname')
                ->field('fullname')->equals( new \MongoRegex('/' . preg_quote(trim($request->get('name'))) . '/i'))
                ->limit($this->container->getParameter('autoCompelet_display_item'))
                ->hydrate(false);

        if($request->get('type') == 'staff')
            $query->field('type')->equals('staff');

        $result = $query->getQuery()->execute();

        foreach ($result->toArray() as $row) {
            if (isset($row["fullname"])) {
                $names[] = $row["fullname"];
            }
        }
        return new JsonResponse($names);
    }


    public function deleteImageAction(Request $request, $id) {
        $dm = $this->get('doctrine_mongodb')->getManager();

        $staff = $dm->getRepository('IbtikarGlanceUMSBundle:Staff')
                ->findOneById($id);
        $staff->removeImage();
        $dm->flush();
        return new JsonResponse(array('status' => 'success', 'message' => $this->trans('valid')));
    }


    protected function validateDelete(Document $document) {
        if ($document->getAdmin()) {
            return $this->trans('failed operation');
        }
    }
}
