<?php

namespace Ibtikar\GlanceUMSBundle\Controller;

use Ibtikar\GlanceDashboardBundle\Controller\base\BackendController;
use Ibtikar\GlanceDashboardBundle\Document\Document;
use Ibtikar\GlanceUMSBundle\Document\Role;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type as formType;

class RoleController extends BackendController {

    protected $translationDomain = 'role';
    protected  $tabs=array(
                    'user'=>array(
                        'title'=>'user',
                        'count'=>0,
                        'modules'=>array('STAFF'=>array(),'VISITOR'=>array(),'ROLE'=>array(),'JOB'=>array(),'CITY'=>array()),
                        'permission'=>array()
                        ),
                    'recipeAndCategory'=>array(
                        'title'=>'recipeAndCategory',
                        'count'=>0,
                        'modules'=>array('PRODUCT'=>array(),'SUBPRODUCT'=>array(),'CATEGORY'=>array(),'MAGAZINE'=>array(),'RECIPETAG'=>array(),'CAUSEPAGE'=>array(),'SEASON'=>array()),
                        'permission'=>array()
                        ),
                    'content'=>array(
                        'title'=>'content',
                        'count'=>0,
                        'modules'=>array('RECIPE'=>array(), 'BLOG'=>array(), 'RECIPENEW'=>array(),'RECIPEDELETED'=>array(),'RECIPEAUTOPUBLISH'=>array(),'RECIPEPUBLISH'=>array(),'RECIPEDRAFT'=>array()),
                        'permission'=>array()
                        ),
                    'message'=>array(
                        'title'=>'Goody messages',
                        'count'=>0,
                        'modules'=>array('MESSAGENEW'=>array(),'MESSAGEINPROGRESS'=>array(),'MESSAGECLOSE'=>array()),
                        'permission'=>array()
                        ),
                   'stars'=>array(
                       'title'=>'stars',
                       'count'=>0,
                       'modules'=>array('STARS'=>array(), 'STARSNEW'=>array(),'STARSREJECTED'=>array(),'STARSAPPROVED'=>array()),
                       'permission'=>array()),

//                    'competition'=>array(
//                        'title'=>'Competition',
//                        'count'=>0,
//                        'modules'=>array('COMPETITION'=>array(),'COMPETITIONNEW'=>array(),'COMPETITIONPUBLISH'=>array(),'COMPETITIONUNPUBLISH'=>array()),
//                        'permission'=>array()
//                        )

                );
    protected $tabsnames=array('user','content');
    private $internalPermissions = array(
        'ROLE_ADMIN', 'ROLE_STAFF','ROLE_SUBPRODUCT_VIEW'

        );

    protected $calledClassName = 'Role';

    protected function getObjectShortName() {
        return 'IbtikarGlanceUMSBundle:Role';
    }


    protected function configureListColumns() {
        $this->allListColumns = array(
            "name" => array("isClickable" => TRUE, 'class' => 'dev-role-getPermision'),
            "permissionscount" => array(),
            "createdAt" => array("type"=>"date"),
            "updatedAt" => array("type"=>"date")
        );
        $this->defaultListColumns = array(
            "name",
            "permissionscount",
            "createdAt",
            'updatedAt'
        );
        $this->listViewOptions->setBundlePrefix("ibtikar_glance_ums_");

    }

    protected function configureListParameters(Request $request) {
        $queryBuilder = $this->createQueryBuilder('IbtikarGlanceUMSBundle');
        $this->listViewOptions->setListQueryBuilder($queryBuilder);
        $this->listViewOptions->setActions(array("Edit", "Delete"));
        $this->listViewOptions->setBulkActions(array());
        $this->listViewOptions->setDefaultSortBy("updatedAt");
        $this->listViewOptions->setDefaultSortOrder("desc");
        $this->listViewOptions->setTemplate("IbtikarGlanceUMSBundle:Role:list.html.twig");
    }

    protected function doList(Request $request) {
        $configParams = parent::doList($request);
        $configParams['deletePopoverConfig'] = array(
            "question" => "You are about to remove that role",
            "buttons" => array(
                [
                    "text" => "Remove that role and the users assgined to it",
                    "class" => "dev-delete-btn btn-block btn-danger",
                    "callback" => "callUrl",
                    "callback-param" => [
                        "data-href"
                    ]
                ],
                [
                    "text" => "Remove that role and deactivate the users assgined to it",
                    "class" => "dev-delete-btn btn-block btn-danger",
                ],
            ),
            "translationDomain" => $this->translationDomain
        );
        return $configParams;
    }

    /**
     * @author Mahmoud Mostafa <mahmoud.mostafa@ibtikar.net.sa>
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $id
     * @return type
     */
    public function editAction(Request $request, $id) {
        $menus = array(array('type' => 'create', 'active' => true, 'linkType' => 'add', 'title' => 'Add new role'), array('type' => 'list', 'active' => FALSE, 'linkType' => 'list', 'title' => 'list role'));
        $breadCrumbArray = $this->preparedMenu($menus,'ibtikar_glance_ums_');

        $loggedInUser = $this->getUser();
        if (!$loggedInUser) {
            return new JsonResponse(array('status' => 'login'));
        }

        $permissions = $this->customPermissionsArray($this->container->getParameter('permissions'));
        $dm = $this->get('doctrine_mongodb')->getManager();
        $role = $dm->getRepository('IbtikarGlanceUMSBundle:Role')->find($id);
        if(!$role || $role->getNotModified()) {
            throw $this->createNotFoundException($this->trans('Wrong id'));
        }
        $permissionSelected=$role->getPermissions();

        $form = $this->buildForm($role, $permissions);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
             $formData = $request->get('form');
            $permissionSelected=array_merge($permissionSelected,$formData['permissions']);
            if ($form->isValid()) {
                $role->setEditAt( new \DateTime());
                $dm->flush();
                $this->addFlash('success', $this->get('translator')->trans('done sucessfully'));
                return $this->redirect($request->getUri());
            }
        }
        return $this->render('IbtikarGlanceUMSBundle:Role:create.html.twig', array(
                    'form' => $form->createView(),
                    'breadcrumb'=>$breadCrumbArray,
                    'title'=>$this->trans('edit role',array(),  $this->translationDomain),
                    'tabs'=> $this->tabs,
                    'permissionSelected'=>$permissionSelected,
                    'translationDomain' => $this->translationDomain
        ));
    }

    public function createAction(Request $request) {

        $menus = array(array('type' => 'create', 'active' => true, 'linkType' => 'add', 'title' => 'Add new role'), array('type' => 'list', 'active' => FALSE, 'linkType' => 'list', 'title' => 'list role'));
        $breadCrumbArray = $this->preparedMenu($menus,'ibtikar_glance_ums_');


        $permissions = $this->container->getParameter('permissions');
        $permissions = $this->customPermissionsArray($permissions);
        $role = new Role();

        $form = $this->buildForm($role, $permissions);
        $permissionSelected=array();

        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            $formData = $request->get('form');
            $permissionSelected=$formData['permissions'];
            if ($form->isValid()) {
                $dm = $this->get('doctrine_mongodb')->getManager();
                $role->setPermissionscount(count($role->getPermissions()));
                $dm->persist($role);
                $dm->flush();
                $this->addFlash('success', $this->get('translator')->trans('done sucessfully'));

                return $this->redirect($request->getUri());
            }
        }

        return $this->render('IbtikarGlanceUMSBundle:Role:create.html.twig', array(
                    'form' => $form->createView(),
                    'breadcrumb'=>$breadCrumbArray,
                    'title'=>$this->trans('Add new role',array(),  $this->translationDomain),
                    'tabs'=> $this->tabs,
                    'permissionSelected'=>$permissionSelected,
                    'translationDomain' => $this->translationDomain
        ));
    }

    private function customPermissionsArray(Array $array) {
        $validArray = array();

        foreach ($this->internalPermissions as $permission) {
            unset($array[$permission]);
        }
        $translator = $this->get('translator');
        $module=array();
        $this->tabsnames= array_keys($this->tabs);
        foreach ($array as $key => $value) {
            $permission = explode(" ",str_replace("_", " ", strtolower(substr($key, 5))));
            foreach ($this->tabsnames as $tabsName ) {
            if(isset($this->tabs[$tabsName]['modules'][strtoupper($permission[0])])){
                if(!in_array($permission[1],$this->tabs[$tabsName]['permission'])){
                   $this->tabs[$tabsName]['permission'][]= strtoupper($permission[1]);
                   $this->tabs[$tabsName]['permission']=  array_unique($this->tabs[$tabsName]['permission']);


                }
                $this->tabs[$tabsName]['modules'][strtoupper($permission[0])][]=$key;
                $this->tabs[$tabsName]['count']++;
            }
             }
            $value = $translator->trans($permission[0], array("wordByWord"=>true,"flip"=>true), $this->translationDomain)." ".$permission[1];
            $validArray[$key] = $value;

        }

        return array_keys($validArray);
    }

    private function adminPermissions($permissions)
    {
        $permissionArray = array();

      foreach ($this->internalPermissions as $permission) {
            unset($permissions[$permission]);
        }
        foreach ($permissions as $key=> $value) {
             $permissionArray[] = str_replace("_", " ", strtolower(substr($key, 5)));

        }
        return $permissionArray;
    }

    private function buildForm($role, $permissions)
    {
        return $this->createFormBuilder($role, array('translation_domain' => $this->translationDomain, 'attr' => array('class' => 'dev-page-main-form dev-js-validation form-horizontal')))
                ->add('name', formType\TextType::class, array('required' => true, 'attr' => array('data-validate-element'=>true,'data-rule-unique' => 'ibtikar_glance_ums_role_check_field_unique', 'data-msg-unique' => $this->trans('not valid'), 'data-name' => 'name',
                        'data-rule-maxlength' => 150,
                        'data-rule-minlength' => 3,
                        'data-url' => $this->generateUrl('ibtikar_glance_ums_role_check_field_unique'),
                        'placeholder' => '')))
                ->add('description', formType\TextareaType::class, array('required' => false, 'attr' => array('data-validate-element'=>true,'data-rule-maxlength' => 500)))
                ->add('permissions', formType\ChoiceType::class, array(
                    'choices' => $permissions,
                    'multiple' => true,
                    'expanded' => true,
                    'attr' => array(
                        'parent-class'=>'hidden',
                        "data-msg-mincheck" => $this->get('translator')->trans('You must have at least 1 Permission', array(), $this->translationDomain),
                        "data-rule-mincheck" => "1",
                        "data-error-after-selector" => ".dev-page-main-form .table-responsive"
                    )
                ))
                ->add('save', formType\SubmitType::class)
                ->getForm();
    }

    public function showRolePermissionAction(Request $request){

        $id= $request->get('id');

        $role = $this->get('doctrine_mongodb')->getManager()->getRepository('IbtikarGlanceUMSBundle:Role')->find($id);
        $permissionSelected=array();
        if(!$role){

        }
        $admin=FALSE;
        if($role->getName()=='Admin'){
//        $permissionSelected = $this->adminPermissions($allPermissions);
        $admin=true;
        }else{
            $permissionSelected= $role->getPermissions();
        }
        $permissions = $this->container->getParameter('permissions');
        $permissions = $this->customPermissionsArray($permissions);

        return $this->render('IbtikarGlanceUMSBundle:Role:rolePermision.html.twig', array('tabs'=> $this->tabs,"permisions" => $permissions,'permissionSelected'=>$permissionSelected,'translationDomain' => $this->translationDomain,'roleCount'=>$role->getPermissionscount(),'admin'=>$admin));

    }

}
