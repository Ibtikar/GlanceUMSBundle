<?php

namespace Ibtikar\GlanceUMSBundle\DataFixtures\MongoDB;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Ibtikar\GlanceDashboardBundle\Document\Role;
use Doctrine\Common\DataFixtures\AbstractFixture;


class LoadRoleData extends AbstractFixture implements FixtureInterface {

    public function load(ObjectManager $manager) {

        $dminRole = new Role();
        $dminRole->setName('Admin');
        $dminRole->setPermissions(array('ROLE_ADMIN'));
        $dminRole->setNotModified(true);
        $manager->persist($dminRole);

        $manager->flush();
    }


}
