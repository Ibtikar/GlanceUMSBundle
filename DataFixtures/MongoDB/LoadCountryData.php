<?php

namespace Ibtikar\GlanceUMSBundle\DataFixtures\MongoDB;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Ibtikar\GlanceUMSBundle\Document\Country;
use Symfony\Component\Intl\Intl;


class LoadCountryData extends AbstractFixture implements FixtureInterface , OrderedFixtureInterface {

    public function load(ObjectManager $manager) {
        \Locale::setDefault('ar');
        $countries = Intl::getRegionBundle()->getCountryNames();

        foreach ($countries as $countryCode => $name) {

            // ignore cities that we do not have translation for
            if (in_array($countryCode, array('AC', 'BL', 'BQ', 'CW', 'DG', 'EA', 'GG', 'IC', 'SS', 'SX', 'TA'))) {
                continue;
            }
            if($countryCode !== 'XK'){
                $country = new Country();
                $country->setCountryCode($countryCode);
                $country->setCountryName($name);
                if($countryCode === 'SA'){
                    $country->setSpecialCountrySort(1);

                }
            }
            $manager->persist($country);
            if ($countryCode === 'SA') {
                $saudiCities = array("Mecca" => "مكة المكرمة", "Jeddah" => "جدة", "Madinah"=> "المدينة المنورة", "Riyadh" => "الرياض", "Abha" => "أبها");
                foreach ($saudiCities as $cityEnglishName => $cityName) {
                    $city = new \Ibtikar\GlanceUMSBundle\Document\City();
                    $city->setCountry($country);
                    $city->setName($cityName);
                    $city->setNameEn($cityEnglishName);
                    $city->setSlug($cityEnglishName);
                    $city->setLat(null);
                    $city->setLong(null);
                    $manager->persist($city);
                    if($cityName == "الرياض") {
                        $this->addReference("الرياض",$city);
                    }
                }
            }
        }



        $manager->flush();
    }

    public function getOrder() {
        return 1;
    }

}
