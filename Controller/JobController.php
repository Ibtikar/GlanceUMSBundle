<?php

namespace Ibtikar\GlanceUMSBundle\Controller;

use Ibtikar\GlanceDashboardBundle\Controller\base\BackendController;
use Ibtikar\GlanceDashboardBundle\Document\Document;
use Ibtikar\GlanceUMSBundle\Document\Job;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type as formType;

class JobController extends BackendController {

    protected $translationDomain = 'job';

    protected function configureListColumns() {
        $this->allListColumns = array(
            "title" => array(),
            "titleEn" => array(),
            "staffMembersCount" => array('type' => 'number'),
            "createdAt" => array("type"=>"date"),
            "updatedAt"=> array("type"=>"date")
        );
        $this->defaultListColumns = array(
            "title",
            "titleEn",
            "staffMembersCount",
            "createdAt",
            "updatedAt"
        );
        $this->listViewOptions->setBundlePrefix("ibtikar_glance_ums_");

    }

    protected function configureListParameters(Request $request) {
        $queryBuilder = $this->createQueryBuilder('IbtikarGlanceUMSBundle');
        $this->listViewOptions->setListQueryBuilder($queryBuilder);
        $this->listViewOptions->setDefaultSortBy("updatedAt");
        $this->listViewOptions->setDefaultSortOrder("desc");
        $this->listViewOptions->setActions(array ("Edit","Delete"));
        $this->listViewOptions->setBulkActions(array("Delete"));
        $this->listViewOptions->setTemplate("IbtikarGlanceUMSBundle:Job:list.html.twig");

    }

    /**
     * @author Ola <ola.ali@ibtikar.net.sa>
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function createAction(Request $request) {
        $menus = array(array('type' => 'create', 'active' => true, 'linkType' => 'add', 'title' => 'Add new job'), array('type' => 'list', 'active' => FALSE, 'linkType' => 'list', 'title' => 'list job'));
        $breadCrumbArray = $this->preparedMenu($menus,'ibtikar_glance_ums_');
        $dm = $this->get('doctrine_mongodb')->getManager();

        $job = new Job();
        $form = $this->createFormBuilder($job, array('translation_domain' => $this->translationDomain,'attr'=>array('class'=>'dev-page-main-form dev-js-validation form-horizontal')))
                ->add('title',formType\TextType::class, array('required' => true,'attr' => array('data-validate-element'=>true,'data-rule-maxlength' => 150,'data-rule-unique' => 'ibtikar_glance_ums_job_check_field_unique','data-name'=>'title','data-msg-unique'=>  $this->trans('not valid'),'data-url'=>$this->generateUrl('ibtikar_glance_ums_job_check_field_unique'))))
                ->add('titleEn',formType\TextType::class, array('required' => true,'attr' => array('data-validate-element'=>true,'data-rule-maxlength' => 150,'data-rule-unique' => 'ibtikar_glance_ums_job_check_field_unique','data-name'=>'titleEn','data-msg-unique'=>  $this->trans('not valid'),'data-url'=>$this->generateUrl('ibtikar_glance_ums_job_check_field_unique'))))
                ->add('save', formType\SubmitType::class)
                ->getForm();

        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $dm->persist($job);
                $dm->flush();
                $this->addFlash('success', $this->get('translator')->trans('done sucessfully'));
                return $this->redirect($request->getUri());
            }
        }
        return $this->render('IbtikarGlanceDashboardBundle::formLayout.html.twig', array(
                    'form' => $form->createView(),
                    'breadcrumb'=>$breadCrumbArray,
                    'title'=>$this->trans('Add new job',array(),  $this->translationDomain),
                    'translationDomain' => $this->translationDomain
        ));
    }

    /**
     * @author Ola <ola.ali@ibtikar.net.sa>
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function editAction(Request $request,$id) {
        $menus = array(array('type' => 'create', 'active' => true, 'linkType' => 'add', 'title' => 'Add new job'), array('type' => 'list', 'active' => FALSE, 'linkType' => 'list', 'title' => 'list job'));
        $breadCrumbArray = $this->preparedMenu($menus,'ibtikar_glance_ums_');
        $dm = $this->get('doctrine_mongodb')->getManager();
        //prepare form
        $job = $dm->getRepository('IbtikarGlanceUMSBundle:Job')->find($id);
        if (!$job) {
            throw $this->createNotFoundException($this->trans('Wrong id'));
        }
        $form = $this->createFormBuilder($job, array('translation_domain' => $this->translationDomain, 'attr' => array('class' => 'dev-page-main-form dev-js-validation form-horizontal')))
                ->add('title', formType\TextType::class, array('required' => true, 'attr' => array('data-validate-element'=>true, 'data-rule-unique' => 'ibtikar_glance_ums_job_check_field_unique', 'data-name' => 'title', 'data-msg-unique' => $this->trans('not valid'), 'data-rule-maxlength' => 150, 'data-url' => $this->generateUrl('ibtikar_glance_ums_job_check_field_unique'))))
                ->add('titleEn', formType\TextType::class, array('required' => true, 'attr' => array('data-validate-element'=>true, 'data-rule-unique' => 'ibtikar_glance_ums_job_check_field_unique', 'data-name' => 'titleEn', 'data-msg-unique' => $this->trans('not valid'), 'data-rule-maxlength' => 150, 'data-url' => $this->generateUrl('ibtikar_glance_ums_job_check_field_unique'))))
                ->add('save', formType\SubmitType::class)
                ->getForm();


        //handle form submission
        if ($request->getMethod() === 'POST') {

            $form->handleRequest($request);

            if ($form->isValid()) {
                $dm->flush();
                $this->addFlash('success', $this->get('translator')->trans('done sucessfully'));

                return $this->redirect($request->getUri());
            }
        }

        //return template
        return $this->render('IbtikarGlanceDashboardBundle::formLayout.html.twig', array(
                    'form' => $form->createView(),
                    'breadcrumb'=>$breadCrumbArray,
                    'title'=>$this->trans('Edit Job',array(),  $this->translationDomain),
                    'translationDomain' => $this->translationDomain
        ));
    }

    /**
     * @author Gehad Mohamed <gehad.mohamed@ibtikar.net.sa>
     * @param Document $document
     * @return string
     */
    protected function validateDelete(Document $document) {
        if(in_array($document->getTitleEn(), Job::$systemEnglishJobTitles)) {
            return $this->get('translator')->trans('failed operation');
        }
        if ($document->getStaffMembersCount() > 0) {
            return $this->trans('Cant deleted,it contain staff members',array(),$this->translationDomain);
        }
    }

}
