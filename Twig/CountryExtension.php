<?php
    namespace Ibtikar\GlanceUMSBundle\Twig;
    use Symfony\Component\Intl\Intl;


    class CountryExtension extends \Twig_Extension {
        public function getFilters()
        {
            return array(
                new \Twig_SimpleFilter('country', array($this, 'countryFilter')),
            );
        }

        public function countryFilter($countryCode, $locale = "en"){
//            $c = \Symfony\Component\Locale\Locale::getDisplayCountries($locale);

//            return $countryCode;

            return Intl::getRegionBundle()->getCountryName($countryCode);
        }

        public function getName()
        {
            return 'country_extension';
        }
    }