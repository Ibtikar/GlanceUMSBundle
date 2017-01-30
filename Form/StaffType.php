<?php

namespace Ibtikar\GlanceUMSBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Ibtikar\GlanceUMSBundle\Document\Staff;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type as formType;

class StaffType extends AbstractType
{

    private $userRoles;
    private $errorMessage;
    private $userImage;
    private $edit;
    private $updateProfile;
    private $userCoverPhoto;
    private $container;


     public function __construct(array $options = array())
    {
        $resolver = new \Symfony\Component\OptionsResolver\OptionsResolver();
        $this->configureOptions($resolver);

        $this->options = $resolver->resolve($options);
    }


    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        //

        $builder
            ->add('file', formType\FileType::class, array('required' => false,
                'attr' => array('accept' => 'image/jpg,image/jpeg,image/png', 'data-msg-accept' => $options['errorMessage']['imageExtension'],
                    'data-error-after-selector' => '.uploadCoverImg', 'data-rule-filesize' => 3,
                    'data-msg-filesize' => $options['errorMessage']['imageSize'],
                    'data-rule-dimensions' => '200', 'data-msg-dimensions' => $options['errorMessage']['imageDimensions'],
                    'data-image-url' => $options['userImage'], 'data-remove-label' => true,
                    'data-image-type' => 'profile')))
            ->add('firstName', formType\TextType::class, array('attr' => array('data-validate-element'=>true,'data-rule-maxlength' => 150)))
            ->add('lastName', formType\TextType::class, array('attr' => array('data-validate-element'=>true,'data-rule-maxlength' => 150)))
            ->add('firstNameEn', formType\TextType::class, array('attr' => array('data-validate-element'=>true,'data-rule-maxlength' => 150)))
            ->add('lastNameEn', formType\TextType::class, array('attr' => array('data-validate-element'=>true,'data-rule-maxlength' => 150)))
            ->add('username', formType\TextType::class, array('attr' => array('data-validate-element'=>true,
                'data-rule-minlength' => 5,'data-msg-unique' => $options['errorMessage']['notValid'],
                'data-msg-minlength'=>$options['errorMessage']['staffUsernameError'],
                'data-rule-unique' => 'ibtikar_glance_ums_staff_check_field_unique', 'data-url' => $options['container']->get('router')->generate('ibtikar_glance_ums_staff_check_field_unique'), 'data-rule-unique' => 'ibtikar_glance_ums_staff_check_field_unique', 'data-name' => 'username', 'data-rule-staffUsername' => 'true', 'data-rule-maxlength' => 150,'data-msg-staffUsername'=>$options['errorMessage']['staffUsernameError'])))
            ->add('email', formType\EmailType::class, array('attr' => array('data-msg-unique' => $options['errorMessage']['notValid'],'data-msg-email'=>$options['errorMessage']['emailvalidateErrorMessage'],'data-validate-element'=>true,'data-rule-unique' => 'ibtikar_glance_ums_staff_check_field_unique', 'data-url' => $options['container']->get('router')->generate('ibtikar_glance_ums_staff_check_field_unique'), 'data-name' => 'email', 'data-rule-maxlength' => 330)))
            ->add('mobile', \Ibtikar\GlanceDashboardBundle\Form\Type\PhoneType::class,array('required' => false,'attr'=>array('parent-class'=>'phoneNumber','data-error-message'=>$options['errorMessage']['mobileError'])))
            ->add('job', \Doctrine\Bundle\MongoDBBundle\Form\Type\DocumentType::class, array('required' => FALSE,
                'class' => 'IbtikarGlanceUMSBundle:Job', 'placeholder' => $options['container']->get('translator')->trans('Choose Job',array(),'staff'),
                'attr' => array('class' => 'select', 'data-error-after-selector' => '.select2-container')
        ));

        $builder
            ->add('role', \Doctrine\Bundle\MongoDBBundle\Form\Type\DocumentType::class, array('class' => 'IbtikarGlanceUMSBundle:Role',
                'multiple' => TRUE, 'attr' => array('class' => 'select', 'data-error-after-selector' => '.select2-container')));
        $builder->add('country', \Doctrine\Bundle\MongoDBBundle\Form\Type\DocumentType::class, array('class' => 'IbtikarGlanceUMSBundle:Country', 'query_builder' => function(DocumentRepository $repo) {

                    return $repo->findCountrySorted();
                }, 'choice_label' => 'countryName', 'required' => true, 'attr' => array('data-country' => true, 'class' => 'dev-country select')))
            ->add('city', null, array('required' => true, 'placeholder' => $options['container']->get('translator')->trans('Choose City',array(),'staff'), 'attr' => array('class' => 'select', 'data-error-after-selector' => '.select2-container'
        )));
      if ($options['edit'] ) {
            $builder ->add('userPassword', formType\RepeatedType::class, array(
                    'type' => formType\PasswordType::class,
                    'invalid_message' => 'The password fields must match.',
                    'required' => false,
                    'first_options' => array('label' => 'Password', 'attr' => array('autocomplete' => 'off', 'data-confirm-password' => '', 'data-rule-passwordMax' => '','data-rule-password'=>true,
                        'data-msg-password'=>$options['errorMessage']['passwordValidateErrorMessage'],
                        'data-msg-passwordMax'=>$options['errorMessage']['passwordValidatePasswordMaxErrorMessage'])),
                    'second_options' => array('label' => 'Repeat Password', 'attr' => array('autocomplete' => 'off', 'data-rule-equalTo' => 'input[data-confirm-password]',
                        'data-msg-equalTo' => $options['errorMessage']['passwordMatch'],
                        'data-rule-passwordMax' => '','data-msg-passwordMax'=>$options['errorMessage']['passwordValidatePasswordMaxErrorMessage'],
                        'data-rule-password'=>true,'data-msg-password'=>$options['errorMessage']['passwordValidateErrorMessage'])),
                ));
        }
        $builder->add('gender', formType\ChoiceType::class, array('required' => FALSE,
            'choices' => Staff::getValidGenders(),
            'expanded' => true, 'placeholder' => false, 'empty_data' => null,'choice_translation_domain'=>'staff'
        ));


        $builder
            ->add('save', formType\SubmitType::class);
           $builder->get('mobile')
                   ->addModelTransformer(new CallbackTransformer(
                        function ($phone) {
                    $phoneArray = array('phone' => '', 'countryCode' => '');

                    if ($phone && $phone->getPhone()) {
                        $phoneArray = array('phone' => $phone->getPhone(), 'countryCode' => $phone->getCountryCode());
                        return $phoneArray;
                    }

                    // transform the array to a string
                    return $phoneArray;
                }, function ($phone) {
                    if(isset($phone['phone'])&& $phone['phone'] ){
                        $phoneObject= new \Ibtikar\GlanceDashboardBundle\Document\Phone();
                        $phoneObject->setPhone($phone['phone']);
                        $phoneObject->setCountryCode($phone['countryCode']);
                        return $phoneObject;
                    }
                    return new \Ibtikar\GlanceDashboardBundle\Document\Phone();
                }
            ))
        ;
    }

    public function getName()
    {
        return 'staff_type';
    }

    public function configureOptions( \Symfony\Component\OptionsResolver\OptionsResolver $resolver ) {
    $resolver->setDefaults( [
      'container' => null,
      'errorMessage' => null,
      'edit' => FALSE,
      'userImage' => '',

        ]);
    }

}
