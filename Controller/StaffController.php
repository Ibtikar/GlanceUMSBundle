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
                            '%smallMessage%',
                            '%message%',
                            '%change_password_url%'
                                ), array(
                            $staff->__toString(),
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
        $menus = array(array('type' => 'create', 'active' => true, 'linkType' => 'add', 'title' => 'add staff'));
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
                    '%fullname%',
                    '%username%',
                    '%password%',
                    '%message%',
                    '%login_url%',
                    '%job%',
                     '%color%',
                        ), array('',
                    $staff->__toString(),
                    $staff->getUsername(),
                    $randPass,
                    $emailTemplate->getMessage(),
                    $this->generateUrl('ibtikar_glance_ums_staff_login', array(), UrlGeneratorInterface::ABSOLUTE_URL),
                    $staff->getJob()->getTitle(),
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
//                $imageOperations = $this->get('image_operations');
//                $imageOperations->autoRotate($staff->getAbsolutePath());
//                if ($staff->getImageNeedResize()) {
//                    $imageOperations->SquareImageResize($staff->getAbsolutePath());
//                }
//                $imageOperations->autoRotate($staff->getCoverPhotoAbsolutePath());
                $this->addFlash('success', $this->get('translator')->trans('done sucessfully'));
                return $this->redirect($request->getUri());
            }
        }
        return $this->render('IbtikarGlanceUMSBundle:Staff:create.html.twig', array(
                'form' => $form->createView(),
                'title' => $this->trans('add staff',array(),  $this->translationDomain),
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
                ->field('admin')->equals(false)
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
     * @author Moemen Hussein <momen.shaaban@ibtikar.net.sa>
     */
    public function updateProfileAction(Request $request, $id) {
        $breadcrumbs = $this->get('white_october_breadcrumbs');
        $breadcrumbs->addItem('backend-home', $this->generateUrl('backend_home'));
        $breadcrumbs->addItem('Profile', $this->generateUrl('staff_updateProfile'));
        $securityContext = $this->get('security.authorization_checker');
        $loggedInUser = $this->getUser();
        if (!$loggedInUser) {
            if($request->isXmlHttpRequest()) {
            return new JsonResponse(array('status' => 'login'));
            }
            return $this->redirect($this->generateUrl('staff_login'));
        }
        if($id !== $loggedInUser->getId()){
            $this->createAccessDeniedException();
        }
        return $this->processEditAndRestoreForm($request, $id,"edit", true);
    }

    /**
     * @author Gehad <gehad.mohamed@ibtikar.net.sa>
     * @author Ola <ola.ali@ibtikar.net.sa>
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param type $id
     * @return type
     * @throws type
     */

    public function editAction(Request $request, $id) {
        $breadcrumbs = $this->get("white_october_breadcrumbs");
        $breadcrumbs->addItem('backend-home', $this->generateUrl('backend_home'));
        $breadcrumbs->addItem('List Staff', $this->generateUrl('staff_list'));
        $breadcrumbs->addItem('Edit Staff', $this->generateUrl('staff_edit'));

        return $this->processEditAndRestoreForm($request, $id,"edit");
    }

    private function processEditAndRestoreForm(Request $request, $id, $formType="edit", $isUpdateProfileData=false) {
        $loggedInUserRoles = $this->getUser()->getRoles();
        $translator = $this->get('translator');
        $ErrorMessage['password'] = $translator->trans('The password fields must match.', array(), $this->validationTranslationDomain);
        $ErrorMessage['image'] = $translator->trans('picture not correct.', array(), $this->validationTranslationDomain);
        $dm = $this->get('doctrine_mongodb')->getManager();
        $allowedJobs = $dm->createQueryBuilder('IbtikarGlanceUMSBundle:Job')
                        ->field('title_en')->in(array_values(Job::$systemEnglishJobSortableTitles))
                        ->eagerCursor(true)
                        ->getQuery()->execute();
        $jobTitlesIds = array();
        foreach ($allowedJobs as $job) {
            $jobTitlesIds [] = $job->getId();
        }
        $deleteCondition = ($formType == "restore") ? true : false;
        $staff = $dm->getRepository('IbtikarGlanceUMSBundle:Staff')->findOneBy(array('id' => $id, 'deleted' => $deleteCondition));
        if(!$staff || ($staff->getAdmin() && !$isUpdateProfileData)) {
            throw $this->createNotFoundException($this->trans('Wrong id'));
        }
        if (!$staff->getMobile()) {
            $staff->setMobile(new \Ibtikar\BackendBundle\Document\Phone());
        }

        if(!$staff->getAdmin()) {
            $oldJobTitle=$staff->getJob()->getTitleEn();
            $author=array('writters','reporters');
        }

        $userImage = $staff->getWebPath();
        $userCoverPhoto = $staff->getCoverPhotoWebPath();
        $hobbySelected = $this->getHobbiesForDocument($staff);
        $securityContext = $this->get('security.authorization_checker');
        if($id === $this->getUser()->getId()){
            $form = $this->createForm(new StaffType($loggedInUserRoles, $ErrorMessage, true, $userImage, true, $hobbySelected, $userCoverPhoto, $securityContext), $staff, array(
                'translation_domain' => $this->translationDomain,
                'validation_groups' => array('Edit','Default')
            ));
        }
        else{
            $form = $this->createForm(new StaffType($loggedInUserRoles, $ErrorMessage, true, $userImage, false, $hobbySelected, $userCoverPhoto, $securityContext), $staff, array(
                'translation_domain' => $this->translationDomain,
                'validation_groups' => array('Edit','Default')
            ));
        }
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $dm = $this->get('doctrine_mongodb')->getManager();
                $formData = $request->get('staff_type');
                $hobbies = $formData['hobbies'];
                $staff->setHobbies();
                if ($hobbies) {
                    $hobbyArray = explode(',', $hobbies);
                    $hobbyArray = array_unique($hobbyArray);
                    foreach ($hobbyArray as $hobby) {
                        $hobby = trim($hobby);
                        if (mb_strlen($hobby,'UTF-8')<= 330 && mb_strlen($hobby,'UTF-8')>= 3) {
                            $newHobby = new Hobby();
                            $newHobby->setHobby($hobby);
                            $dm->persist($newHobby);
//                            $dm->flush($NewTag);
                            $staff->addHobby($newHobby);
                        }
                    }
                }
                if (!$securityContext->isGranted('ROLE_STAFF_BOARD') && !$securityContext->isGranted('ROLE_ADMIN')) {
                    $staff->setShowEditorBoard(FALSE);
                }
                else if(!in_array($staff->getJob()->getTitleEn(), array_values(Job::$systemEnglishJobSortableTitles))){
                    if(in_array($staff->getJob()->getTitleEn(), array(Job::$systemEnglishJobTitles['editor'], Job::$systemEnglishJobTitles['deputy editor']))){
                        $staff->setShowEditorBoard(TRUE);
                    }
                    else{
                        $staff->setShowEditorBoard(FALSE);
                    }
                }
                $staff->setValidPassword();
                if($formType == "restore") {
                    $staff->setDeleted(false);
                }
                $dm->persist($staff);
                if(!$staff->getAdmin() && $formType =='edit' && in_array($oldJobTitle, $author) && $oldJobTitle!=  $staff->getJob()->getTitleEn()){
                    $staff->removeAuthorFromMaterial($dm);
                }
                if($id !== $this->getUser()->getId()){
                    $uow = $dm->getUnitOfWork();
                    $uow->computeChangeSets();
                    $changeset = $uow->getDocumentChangeSet($staff);
                    $emailTemplate = $dm->getRepository('IbtikarGlanceUMSBundle:EmailTemplate')->findOneByName($formType . ' staff');
                    $record = $emailTemplate->getEmailDataRecord();
                    $content = '';
                    if ($formType == "edit") {
                        $updateField = array('country', 'city', 'email', 'password', 'firstName', 'lastName', 'fullname', 'enabled', 'job', 'department', 'personTitle'
                            , 'employeeId', 'username', 'gender', 'mobile', 'group');

                        foreach ($changeset as $key => $change) {
                            $staff->updateStaffCountOnEdit($key, $change);
                            $renderer = $this->get("string_utilities");
                            $keyLabel = $renderer->humanize($key);
                            if ($key == 'gender') {
                                $change[1] = $translator->trans(trim($change[1] . PHP_EOL), array(), $this->translationDomain);

                                $content.= str_replace(array('%updatedfield%', '%value%'), array($translator->trans($keyLabel, array(), $this->translationDomain), $key !== "password" ? $change[1] . PHP_EOL : $staff->getUserPassword()), $record);
                            } elseif ($key == 'enabled') {

                                if (($change[0] && $change[1] != 1) || (!$change[0] && $change[1] != 0)) {
                                    if ($change[1]) {
                                        $change[1] = $translator->trans('enabled', array());
                                    } else {
                                        $change[1] = $translator->trans('disabled', array());
                                    }
                                    $content.= str_replace(array('%updatedfield%', '%value%'), array($translator->trans($keyLabel, array(), $this->translationDomain), $key !== "password" ? $change[1] . PHP_EOL : $staff->getUserPassword()), $record);
                                }
                            } elseif ($key != 'enabled' && $key != 'image' && $key != 'coverPhotoImage' && $key != 'city' && $key != 'fullname' && in_array($key, $updateField)) {
                                $content.= str_replace(array('%updatedfield%', '%value%'), array($translator->trans($keyLabel, array(), $this->translationDomain), $key !== "password" ? $change[1] . PHP_EOL : $staff->getUserPassword()), $record);
                            } elseif ($key != 'fullname' && $key != 'image' && $key != 'coverPhotoImage' && in_array($key, $updateField)) {
                                $content.= str_replace(array('%updatedfield%', '%value%'), array($translator->trans($keyLabel, array(), $this->translationDomain), $change[1]), $record);
                            }
                        }
                    } else if ($formType == "restore") {
                        $staff->updateReferencesCounts(1);
                        if ($staff->getUserPassword())
                            $content = str_replace(array('%updatedfield%', '%value%'), array($translator->trans('Password', array(), $this->translationDomain), $staff->getUserPassword()), $record);
                    }
                    $currentTime = new \DateTime();

                    if (strlen($content) > 0  || $formType == "restore") {
                        $currentTime = new \DateTime();
                        $body = str_replace(
                                array(
                            '%fullname%',
                            '%username%',
                            '%message%',
                            '%changed_values%',
                            '%day%',
                            '%date%',
                            '%updated_by%'
                                ), array(
                            $staff->__toString(),
                            $staff->getUsername(),
                            $emailTemplate->getMessage(),
                            $content,
                            $translator->trans($currentTime->format('l')),
                            $currentTime->format('d/m/Y'),
                            $this->getUser()->__toString()
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
                    }
                }
                $dm->flush();

                $imageOperations = $this->get('image_operations');
                $imageOperations->autoRotate($staff->getAbsolutePath());
                if ($staff->getImageNeedResize()) {
                    $imageOperations->SquareImageResize($staff->getAbsolutePath());
                }
                $imageOperations->autoRotate($staff->getCoverPhotoAbsolutePath());

                $userImage = $staff->getWebPath();
                $userCoverPhoto = $staff->getCoverPhotoWebPath();
                $hobbySelected = $this->getHobbiesForDocument($staff);
                if($id === $this->getUser()->getId()){
                    $form = $this->createForm(new StaffType($loggedInUserRoles, $ErrorMessage, true, $userImage, true, $hobbySelected, $userCoverPhoto, $securityContext), $staff, array(
                        'translation_domain' => $this->translationDomain,
                        'validation_groups' => array('Edit','Default')
                    ));
                }
                else{
                    $form = $this->createForm(new StaffType($loggedInUserRoles, $ErrorMessage, true, $userImage, false, $hobbySelected, $userCoverPhoto, $securityContext), $staff, array(
                        'translation_domain' => $this->translationDomain,
                        'validation_groups' => array('Edit','Default')
                    ));
                }
                $this->addFlash('success', $this->get('translator')->trans('done sucessfully'));
                if($formType == "restore")
                    return new JsonResponse(array('status' => 'success'));
            }
            else {
                $dm = $this->get('doctrine_mongodb')->getManager()->refresh($this->getUser());
            }
        }
        return $this->render('IbtikarGlanceUMSBundle:Staff:'.$formType.'.html.twig', array(
                    'form' => $form->createView(),
                    'jobTitlesIds' => json_encode($jobTitlesIds),
                    'translationDomain' => $this->translationDomain
        ));
    }

    public function restoreAction(Request $request, $id) {
        $breadcrumbs = $this->get("white_october_breadcrumbs");
        $breadcrumbs->addItem('backend-home', $this->generateUrl('backend_home'));
        $breadcrumbs->addItem('List Staff', $this->generateUrl('staff_list'));
        $breadcrumbs->addItem('View deleted items', $this->generateUrl('staff_trash'));
        $breadcrumbs->addItem('Restore Staff Member', $this->generateUrl('staff_restore'));


        return $this->processEditAndRestoreForm($request, $id,"restore");
    }

    /**
     * @author Moemen Hussein <momen.shaaban@ibtikar.net.sa>
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function staffAlmostFinishedMaterialAction(Request $request){
        $id = $this->getUser()->getId();
        $dm = $this->get('doctrine_mongodb')->getManager();
//        die(var_dump(new \DateTime()));
//        $material = $dm->getRepository('IbtikarAppBundle:Material')->findOneById('54a510ac7f8b9af3168b5c12');
//        $material->startTimer(1, 2);
//        $dm->flush();

        // check if material is forceAssigned to another user within staf editing material
        $pageRoute = $request->get('pageRoute', false);
        $result = array();
        if($pageRoute && strpos($pageRoute,'-room') !== false && strpos($pageRoute,'/edit/') !== false) {
            $materialId = explode('/', $pageRoute);
            $material = $dm->getRepository('IbtikarAppBundle:Material')->findOneBy(array('id' => $materialId[count($materialId)-1]));
            if($material &&   $material->getRoom() != 'published' && $material->getStatus() != 'autopublish' && $material->getAssignedTo()->getId() != $id)
                $result['forceAssign'] = true;
        }

        $query = $dm->createQueryBuilder('IbtikarAppBundle:Material')
                        ->field('assignedTo')->equals($id)
                        ->field('timerEndTime')->lte(new \DateTime('+35 SECONDS'))
                        ->field('timerEndTime')->gt(new \DateTime('+2 SECONDS'))
                        ->field('timerRunning')->equals(true)
                        ->field('alertSeen')->equals(false)
                        ->getQuery()->execute();
        $roomsTimerSettings = $this->container->get('system_settings')->getSettingsByCategoryAsArray('timer');
        $materials = array();
        $currentTime = new \DateTime();
        foreach ($query as $row) {
            if(count($row->getPath()) > 0 && isset($roomsTimerSettings['room-timer-' . $row->getRoom() . '-enabled']) && $roomsTimerSettings['room-timer-' . $row->getRoom() . '-enabled']) {
                $lastReturn = count($row->getPath()) === 1 ? true : false;
                $materials[] = array('id' => $row->getId(), 'mainTitle' => htmlentities($row->getMainTitle(),ENT_QUOTES | ENT_HTML5, 'UTF-8'), 'timerRemainingExtendTimes' => $row->getTimerRemainingExtendTimes(), 'timerEndTime' => $row->getTimerEndTime()->getTimestamp() - $currentTime->getTimestamp(), 'lastReturn' => $lastReturn);
            }
        }
        $result['status'] = 'success';
        $result['materials'] = $materials;

        return new JsonResponse($result);
    }

    /**
 * used to execute the query on the users based on the action sent to the function
 *
 * @author Gehad Mohamed <gehad.mohamed@ibtikar.net.sa>
 *
 * @param string $action name of the action to be berformed of the users
 * @param array $ids array of user ids
 * @param string $groupId group id
 */
    private function staffGroupUpdate($action,$ids,$groupId){
        $dm = $this->get('doctrine_mongodb')->getManager();
        $query=$dm->createQueryBuilder('IbtikarGlanceUMSBundle:Staff')
                ->update()
                ->field('id')->in($ids)
                ->field('deleted')->equals(false)
                ->field('updatedBy')->set(new \MongoId($this->getUser()->getId()))
                ->field('updatedAt')->set(new \DateTime());

        switch ($action) {
            case 'add':
                $query=$query->field('group')->set(new \MongoId($groupId));
            break;
            case 'remove':
                $query=$query->field('group')->unsetField()->exists(true);
            break;
            case 'deactivate':
                $query=$query->field('group')->unsetField()->exists(true)
                             ->field('enabled')->set(false);
            break;
        }

        $query->multiple(true)
        ->getQuery()
        ->execute();


    }

    /**
     * @author Ola <ola.ali@ibtikar.net.sa>
     */

    public function usersStatisticsAction() {
        $breadcrumbs = $this->get("white_october_breadcrumbs");
        $breadcrumbs->addItem('backend-home', $this->generateUrl('backend_home'));
        $breadcrumbs->addItem('statistics', $this->generateUrl('user_statistics'));
        $em = $this->get('doctrine_mongodb')->getManager();
        $activeStaffMemeber = $em->createQueryBuilder('IbtikarGlanceUMSBundle:Staff')
                        ->field('admin')->equals(FALSE)
                        ->field('deleted')->equals(FALSE)
                        ->field('enabled')->equals(TRUE)
                        ->getQuery()->execute()->count();
        $inActiveStaffMemeber = $em->createQueryBuilder('IbtikarGlanceUMSBundle:Staff')
                        ->field('admin')->equals(FALSE)
                        ->field('enabled')->equals(FALSE)
                        ->field('deleted')->equals(FALSE)
                        ->getQuery()->execute()->count();
        $totalStaffMember = $activeStaffMemeber + $inActiveStaffMemeber;

        $ActiveVisitor = $em->createQueryBuilder('IbtikarVisitorBundle:Visitor')
                        ->field('deleted')->equals(FALSE)
                        ->field('enabled')->equals(TRUE)
                        ->getQuery()->execute()->count();
        $inActiveVisitor = $em->createQueryBuilder('IbtikarVisitorBundle:Visitor')
                        ->field('enabled')->equals(FALSE)
                        ->field('deleted')->equals(FALSE)
                        ->getQuery()->execute()->count();
        $totalVisitor = $ActiveVisitor + $inActiveVisitor;

        return $this->render('IbtikarGlanceUMSBundle:Staff:usersStatistics.html.twig', array(
                    'inActiveStaffMemeber' => $inActiveStaffMemeber,
                    'activeStaffMemeber' => $activeStaffMemeber,
                    'totalStaffMember' => $totalStaffMember,
                    'activeVisitor' => $ActiveVisitor,
                    'inActiveVisitor' => $inActiveVisitor,
                    'totalVisitor' => $totalVisitor,
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

    public function MigrateUserCountsAction(Request $request) {

        $dm = $this->get('doctrine_mongodb')->getManager();
        $jobs = $dm->getRepository('IbtikarGlanceUMSBundle:Job')->findAll();
        foreach ($jobs as $job) {
            $staffCount = $dm->createQueryBuilder('IbtikarGlanceUMSBundle:Staff')
                        ->field('job')->equals($job->getId())
                        ->field('deleted')->equals(FALSE)
                        ->getQuery()->execute()->count();
            $job->setStaffMembersCount($staffCount);
        }

        $departments = $dm->getRepository('IbtikarGlanceUMSBundle:Department')->findAll();
        foreach ($departments as $department) {
            $staffCount = $dm->createQueryBuilder('IbtikarGlanceUMSBundle:Staff')
                        ->field('department')->equals($department->getId())
                        ->field('deleted')->equals(FALSE)
                        ->getQuery()->execute()->count();
            $department->setStaffMembersCount($staffCount);
        }

        $cities = $dm->getRepository('IbtikarGlanceUMSBundle:City')->findAll();
        foreach ($cities as $city) {
            $staffCount = $dm->createQueryBuilder('IbtikarGlanceUMSBundle:Staff')
                        ->field('city')->equals($city->getId())
                        ->field('deleted')->equals(FALSE)
                        ->getQuery()->execute()->count();
            $city->setStaffMembersCount($staffCount);
        }

        $cities = $dm->getRepository('IbtikarGlanceUMSBundle:City')->findAll();
        foreach ($cities as $city) {
            $staffCount = $dm->createQueryBuilder('IbtikarVisitorBundle:Visitor')
                        ->field('city')->equals($city->getId())
                        ->field('deleted')->equals(FALSE)
                        ->getQuery()->execute()->count();
            $city->setVisitorsCount($staffCount);
        }

        $cities = $dm->getRepository('IbtikarGlanceUMSBundle:City')->findAll();
        foreach ($cities as $city) {
            $staffCount = $dm->createQueryBuilder('IbtikarUserBundle:User')
                        ->field('city')->equals($city->getId())
                        ->field('deleted')->equals(FALSE)
                        ->getQuery()->execute()->count();
            $city->setUsersCount($staffCount);
        }

        $titles = $dm->getRepository('IbtikarGlanceUMSBundle:PersonTitle')->findAll();
        foreach ($titles as $title) {
            $staffCount = $dm->createQueryBuilder('IbtikarGlanceUMSBundle:Staff')
                        ->field('personTitle')->equals($title->getId())
                        ->field('deleted')->equals(FALSE)
                        ->getQuery()->execute()->count();
            $title->setStaffMembersCount($staffCount);
        }

        $groups = $dm->getRepository('IbtikarGlanceUMSBundle:Group')->findAll();
        foreach ($groups as $group) {
            $staffCount = $dm->createQueryBuilder('IbtikarGlanceUMSBundle:Staff')
                        ->field('group')->equals($group->getId())
                        ->field('deleted')->equals(FALSE)
                        ->getQuery()->execute()->count();
        }

        $roles = $dm->getRepository('IbtikarGlanceUMSBundle:Role')->findAll();
        foreach ($roles as $role) {
            $staffCount = $dm->createQueryBuilder('IbtikarGlanceUMSBundle:Staff')
                        ->field('role')->equals($role->getId())
                        ->field('deleted')->equals(FALSE)
                        ->getQuery()->execute()->count();
            $role->setStaffMembersCount($staffCount);
        }

        $dm->flush();
        exit();
    }

    protected function validateDelete(Document $document) {
        if ($document->getAdmin()) {
            return $this->trans('failed operation');
        }
    }
}
