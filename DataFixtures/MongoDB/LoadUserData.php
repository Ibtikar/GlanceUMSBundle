<?php

namespace Ibtikar\GlanceUMSBundle\DataFixtures\MongoDB;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Ibtikar\GlanceUMSBundle\Document\Staff;
use Doctrine\Common\DataFixtures\AbstractFixture;


class LoadUserData extends AbstractFixture implements FixtureInterface, OrderedFixtureInterface {

    public function load(ObjectManager $manager) {

        $adminUser = new Staff();
        $adminUser->setEmployeeId(1);
        $adminUser->setUserPassword('ibtikaradmin123');
        $adminUser->setEmail('ola.ali@ibtikar.net.sa');
        $adminUser->setFirstName('مشرف');
        $adminUser->setLastName('جودى');
        $adminUser->setUsername('goodyAdmin');
        $adminUser->setAdmin(true);
        $adminUser->setEmailVerified(TRUE);
        $adminUser->setMustChangePassword(false);
        $adminUser->setCity($this->getReference("الرياض"));
        $manager->persist($adminUser);

        $testUser = new Staff();
        $testUser->setEmployeeId(2);
        $testUser->setUserPassword('ibtikaradmin123');
        $testUser->setEmail('rana.khaled@ibtikar.net.sa');
        $testUser->setFirstName('test');
        $testUser->setLastName('test');
        $testUser->setUsername('goodyTest');
        $testUser->setAdmin(true);
        $testUser->setEmailVerified(TRUE);
        $testUser->setMustChangePassword(TRUE);
        $testUser->setCity($this->getReference("الرياض"));
        $manager->persist($testUser);



        $manager->flush();
    }

    public function getOrder() {
        return 2;
    }

}
