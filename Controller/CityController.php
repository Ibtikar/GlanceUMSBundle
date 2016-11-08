<?php

namespace Ibtikar\GlanceUMSBundle\Controller;

use Ibtikar\GlanceDashboardBundle\Controller\base\BackendController;
use Ibtikar\GlanceDashboardBundle\Document\Document;
use Ibtikar\GlanceUMSBundle\Document\City;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Symfony\Component\Form\Extension\Core\Type as formType;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Locale\Locale;

class CityController extends BackendController {

    protected $translationDomain = 'city';

    protected function configureListColumns() {
        $this->allListColumns = array(
            "name" => array(),
            "nameEn" => array(),
            "country" => array('isSortable' => false),
            "staffMembersCount" => array('type' => 'number'),
            "createdAt" => array("type"=>"date"),
            "updatedAt" => array("type"=>"date")
        );
        $this->defaultListColumns = array(
            "name",
            "country",
            "staffMembersCount",
            "createdAt",
            "updatedAt"

        );
    $this->listViewOptions->setBundlePrefix("ibtikar_glance_ums_");

    }

    protected function configureListParameters(Request $request) {

        $dm = $this->get('doctrine_mongodb')->getManager();

        $this->listViewOptions->setDefaultSortBy("updatedAt");
        $this->listViewOptions->setDefaultSortOrder("desc");

        $queryBuilder = $this->createQueryBuilder('IbtikarGlanceUMSBundle');
//        $this->configureListColumns();

        if($request->get('countryCode')) {
            $queryBuilder = $queryBuilder->field('country')->equals($request->get('countryCode'));
        }
        $this->listViewOptions->setListQueryBuilder($queryBuilder);

        $this->listViewOptions->setActions(array("Edit"));
        $this->listViewOptions->setBulkActions(array('Delete'));
//        $this->listViewOptions->setRestorable(FALSE);
        $this->listViewOptions->setTemplate("IbtikarGlanceUMSBundle:City:list.html.twig");
    }

    protected function doList(Request $request) {
        $renderingParams = parent::doList($request);
        $dm = $this->get('doctrine_mongodb')->getManager();
        $countries = $dm->getRepository('IbtikarGlanceDashboardBundle:Country')->findCountrySorted()->getQuery()->execute();

        $renderingParams['countries'] = $countries;
        $renderingParams['country_selected'] = $request->get('countryCode');

        return $renderingParams;
    }

    /**
     * @author Mahmoud Mostafa <mahmoud.mostafa@ibtikar.net.sa>
     * @param Document $document
     * @return string
     */
    protected function validateDelete(Document $document) {
        if ($document->getStaffMembersCount() > 0) {
            return $this->trans('Can not delete the city as it contains staff members or visitors.');
        }
        if ($document->getVisitorsCount() > 0) {
            return $this->trans('Can not delete the city as it contains staff members or visitors.');
        }
        $dm = $this->get('doctrine_mongodb')->getManager();
        $materialRelatedCity = $dm->createQueryBuilder('IbtikarAppBundle:Material')
                ->field('city')->equals($document->getId())
                ->limit(1)
                ->getQuery()
                ->execute();
        if (count($materialRelatedCity) > 0) {
            return $this->trans('Can not delete the city as it contains news');
        }

        $cityRelatedCity = $dm->createQueryBuilder('IbtikarAppBundle:Comics')
                ->field('city')->equals($document->getId())
                ->limit(1)
                ->getQuery()
                ->execute();
        if (count($cityRelatedCity) > 0) {
            return $this->trans('Can not delete the city as it contains comics');
        }
    }

    /**
     * @author Mahmoud Mostafa <mahmoud.mostafa@ibtikar.net.sa>
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function countryCitiesOptionsAction(Request $request) {
        $countryCode = $request->get('countryCode');
        $search = $request->get('search');
        $responseContent = '';
        if ($countryCode) {
            $translator = $this->get('translator');
            $cities = $this->get('doctrine_mongodb')->getManager()->getRepository('IbtikarGlanceUMSBundle:City')->findBy(array('country' => $countryCode), array('name' => 'ASC'));
            $responseContent='<option value="">' . $translator->trans('Choose City', array(), $this->translationDomain) . '</option>';
            if ($search) {
                $responseContent = '<option value="">' . $translator->trans('All') . '</option>';
            }
            foreach ($cities as $city) {
                $responseContent .= '<option value="' . $city->getId() . '">' . $city->__toString() . '</option>';
            }
        }
        return new Response($responseContent);
    }


    public function createAction(Request $request) {
        $menus = array(array('type' => 'create', 'active' => true, 'linkType' => 'add', 'title' => 'Add new city'), array('type' => 'list', 'active' => FALSE, 'linkType' => 'list', 'title' => 'list city'));
        $breadCrumbArray = $this->preparedMenu($menus,'ibtikar_glance_ums_');
        $dm = $this->get('doctrine_mongodb')->getManager();
        $city = new City();
        $countryAttr = array('data-country' => true, 'class' => 'dev-country select');
        $selectedCountryId = $request->get('selectedCountryId');
        if ($selectedCountryId) {
            $country = $dm->getRepository('IbtikarGlanceDashboardBundle:Country')->find($selectedCountryId);
            if ($country) {
                $city->setCountry($country);
                $countryAttr['readonly'] = true;
            }
        }
        $form = $this->createFormBuilder($city, array('translation_domain' => $this->translationDomain, 'attr' => array('class' => 'dev-page-main-form dev-js-validation form-horizontal')))
            ->add('name', formType\TextType::class, array('attr' => array('data-validate-element'=>true,'data-rule-unique' => 'ibtikar_glance_ums_city_check_field_unique', 'data-name' => 'name', 'data-rule-minlength' => 3, 'data-msg-unique' => $this->trans('not valid'), 'data-rule-maxlength' => 150, 'data-url' => $this->generateUrl('ibtikar_glance_ums_city_check_field_unique'))))
            ->add('nameEn', formType\TextType::class, array('attr' => array('data-validate-element'=>true,'data-rule-unique' => 'ibtikar_glance_ums_city_check_field_unique', 'data-name' => 'nameEn', 'data-rule-minlength' => 3, 'data-msg-unique' => $this->trans('not valid'), 'data-rule-maxlength' => 150, 'data-url' => $this->generateUrl('ibtikar_glance_ums_city_check_field_unique'))))
            ->add('country', \Doctrine\Bundle\MongoDBBundle\Form\Type\DocumentType::class, array('class' => 'IbtikarGlanceDashboardBundle:Country', 'query_builder' => function(DocumentRepository $repo) {

                    return $repo->findCountrySorted();
                }, 'choice_label' => 'countryName', 'required' => true, 'attr' => $countryAttr))
            ->add('save', formType\SubmitType::class)
            ->getForm();

        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $dm->persist($city);
                $dm->flush();

                $this->addFlash('success', $this->get('translator')->trans('done sucessfully'));

                return $this->redirect($request->getUri());
            }
        }

        return $this->render('IbtikarGlanceDashboardBundle::formLayout.html.twig', array(
                'breadcrumb' => $breadCrumbArray,
                'title' => $this->trans('Add new city', array(), $this->translationDomain),
                'form' => $form->createView(),
                'translationDomain' => $this->translationDomain
        ));
    }




    public function editAction(Request $request, $id)
    {
        $menus = array(array('type' => 'create', 'active' => true, 'linkType' => 'add', 'title' => 'Add new city'), array('type' => 'list', 'active' => FALSE, 'linkType' => 'list', 'title' => 'list city'));
        $breadCrumbArray = $this->preparedMenu($menus,'ibtikar_glance_ums_');

        $dm = $this->get('doctrine_mongodb')->getManager();
        $city = $dm->getRepository('IbtikarGlanceUMSBundle:City')->find($id);
        if (!$city) {
            throw $this->createNotFoundException($this->trans('Wrong id'));
        }
        $countryAttr = array('data-country' => true, 'class' => 'dev-country select');

        $form = $this->createFormBuilder($city, array('translation_domain' => $this->translationDomain, 'attr' => array('class' => 'dev-page-main-form dev-js-validation form-horizontal')))
            ->add('name', formType\TextType::class, array('attr' => array('data-validate-element'=>true,'data-rule-unique' => 'ibtikar_glance_ums_city_check_field_unique', 'data-name' => 'name', 'data-rule-minlength' => 3, 'data-msg-unique' => $this->trans('not valid'), 'data-rule-maxlength' => 150, 'data-url' => $this->generateUrl('ibtikar_glance_ums_city_check_field_unique'))))
            ->add('nameEn', formType\TextType::class, array('attr' => array('data-validate-element'=>true,'data-rule-unique' => 'ibtikar_glance_ums_city_check_field_unique', 'data-name' => 'nameEn', 'data-rule-minlength' => 3, 'data-msg-unique' => $this->trans('not valid'), 'data-rule-maxlength' => 150, 'data-url' => $this->generateUrl('ibtikar_glance_ums_city_check_field_unique'))))
            ->add('country', \Doctrine\Bundle\MongoDBBundle\Form\Type\DocumentType::class, array('class' => 'IbtikarGlanceDashboardBundle:Country', 'query_builder' => function(DocumentRepository $repo) {

                    return $repo->findCountrySorted();
                }, 'choice_label' => 'countryName', 'required' => true, 'attr' => $countryAttr))
            ->add('save', formType\SubmitType::class)
            ->getForm();

        if ($request->getMethod() === 'POST') {

            $form->handleRequest($request);

            if ($form->isValid()) {
                $dm->flush();
                $this->addFlash('success', $this->get('translator')->trans('done sucessfully'));
//                if ($formData['submitButton'] == 'add_save') {
//                    return new JsonResponse(array('status' => 'redirect', 'url' => $this->generateUrl('place_create', array('cityId' => $city->getId()))));
//                } else {
//                    return new JsonResponse(array('status' => 'redirect', 'url' => $this->generateUrl('city_list'), array(), true));
//                }
            }
        }

        return $this->render('IbtikarGlanceDashboardBundle::formLayout.html.twig', array(
                'breadcrumb' => $breadCrumbArray,
                'title' => $this->trans('edit city', array(), $this->translationDomain),
                'form' => $form->createView(),
                'translationDomain' => $this->translationDomain
        ));
    }

    public function importAction(Request $request) {


        $dm = $this->get('doctrine_mongodb')->getManager();
        $local = new Locale();
        $enCountries = $local->getDisplayCountries("en_us");
        $enCountriesNameCode = array_flip($enCountries);

        $arCountries = $local->getDisplayCountries("ar_sa");


        $cities = array();
        $mongo = new \MongoClient();
        $db = $this->container->getParameter('mongodb_database');
        $collection = $mongo->$db->City;
        $collection->drop();

        $admin = $dm->getRepository('IbtikarBackendBundle:Staff')->findOneByAdmin(true);
        $objReader = new \PHPExcel_Reader_CSV();
        $objPHPExcel = $objReader->load($this->container->getParameter('xls_temp_path') . "Listofcities.csv");

        $objWorksheet = $objPHPExcel->getSheet();

        foreach ($objWorksheet->getRowIterator() as $row) {

            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(FALSE); // This loops through all cells,
            //    even if a cell value is not set.
            // By default, only cells that have a value
            //    set will be iterated.
            $city = array();
            $valid = true;
            foreach ($cellIterator as $key => $cell) {
                if ($cellIterator == "error") {
                    break;
                }
//        $city = new City();

                $value = $cell->getValue();
                if ($key == 0 && $value != "Country") {
                    $country = $dm->getRepository('IbtikarBackendBundle:Country')->findOneByCountryCode($enCountriesNameCode[ucfirst($value)]);
                    $city['country'] = new \MongoId($country->getId());
                }
                if ($key == 1 && $value != "City") {
                    $city['name_en'] = trim($value);
                }
                if ($key == 3 && $value != "City") {
                    $city['name'] = trim($value);
                }

                if ($key == 4 && $value == "error") {
                    $valid = false;
                }
            }

            if ($valid) {
                $city['staffMembersCount'] = 0;
                $city['visitorsCount'] = 0;
                $city['usersCount'] = 0;
                $city['createdAt'] = new \MongoDate();
                $city['createdBy'] = new \MongoId($admin->getId());
                $city['updatedAt'] = new \MongoDate();
                $city['updatedBy'] = new \MongoId($admin->getId());
                $city['deletedAt'] = new \MongoDate();
                $city['deletedBy'] = new \MongoId($admin->getId());
                $city['deleted'] = false;

                $cities[] = $city;
            }
        }

        $collection->batchInsert($cities);

        $Riyadh = $dm->getRepository('IbtikarBackendBundle:City')->findOneByName('الرياض');


        $dm->createQueryBuilder('IbtikarBackendBundle:Staff')
                ->update()
                ->field('city')->set(new \MongoId($Riyadh->getId()))
                ->multiple(true)
                ->getQuery()
                ->execute();

        $dm->createQueryBuilder('IbtikarVisitorBundle:Visitor')
                ->update()
                ->field('city')->set(new \MongoId($Riyadh->getId()))
                ->multiple(true)
                ->getQuery()
                ->execute();

        $Riyadh->setStaffMembersCount(count($dm->getRepository('IbtikarBackendBundle:Staff')->findByDeleted(false)) - 1);
        $Riyadh->setVisitorsCount(count($dm->getRepository('IbtikarVisitorBundle:Visitor')->findByDeleted(false)));


        $dm->persist($Riyadh);
        $dm->flush();
        die("Importing new cities finished");
    }

}
