<?php

namespace Ibtikar\GlanceUMSBundle\Document;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
//use Ibtikar\BackendBundle\Document\Job;

/**
 * UserRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserRepository extends DocumentRepository  implements UserProviderInterface,  UserLoaderInterface {

    /**
     * @author Ola <ola.ali@ibtikar.net.sa>
     * @param array $criteria
     * @return array
     */
    public function findUserByEmail(array $criteria) {
        return $this->dm->createQueryBuilder('IbtikarGlanceUMSBundle:User')
                        ->field('email')->equals($criteria['email'])
                        ->field('deleted')->equals(false)
                        ->getQuery()->execute();
    }

    /**
     * @author Mahmoud Mostafa <mahmoud.mostafa@ibtikar.net.sa>
     * @param array $criteria
     * @return array
     */
    public function findUserByEmployeeId(array $criteria) {
        return $this->dm->createQueryBuilder('IbtikarGlanceUMSBundle:User')
                        ->field('employeeId')->equals($criteria['employeeId'])
                        ->field('deleted')->equals(false)
                        ->getQuery()->execute();
    }

    /**
     * @author Mahmoud Mostafa <mahmoud.mostafa@ibtikar.net.sa>
     * @param array $criteria
     * @return array
     */
    public function findUserByUsername(array $criteria) {
        return $this->dm->createQueryBuilder('IbtikarGlanceUMSBundle:User')
                        ->field('username')->equals($criteria['username'])
                        ->field('deleted')->equals(false)
                        ->getQuery()->execute();
    }

    /**
     * @author Mahmoud Mostafa <mahmoud.mostafa@ibtikar.net.sa>
     * @param array $criteria
     * @return array
     */
    public function findUserByNickName(array $criteria) {
        return $this->dm->createQueryBuilder('IbtikarGlanceUMSBundle:User')
                        ->field('nickName')->equals($criteria['nickName'])
                        ->field('deleted')->equals(false)
                        ->getQuery()->execute();
    }

    public function getStaffExceptAdmins($roomName) {
        $qb = $this->dm->createQueryBuilder('IbtikarGlanceUMSBundle:Staff')
                        ->field('type')->equals('staff')
                        ->field('enabled')->equals(true)
                        ->field('deleted')->equals(false)
                        ->field('admin')->equals(false);
        $qb = $qb->addOr($qb->expr()->field('permissions.'.$roomName)->exists(false));
        $qb = $qb->addOr($qb->expr()->field('permissions.'.$roomName)->equals(array()));
        return $qb->getQuery()->execute();
    }

    public function getMigratedStaff() {
        $qb = $this->dm->createQueryBuilder('IbtikarGlanceUMSBundle:Staff')
                        ->field('type')->equals('staff')
                        ->field('migrationPassword')->exists(TRUE)
                        ->field('migrationPassword')->notEqual("");
        return $qb->getQuery()->execute();
    }

    public function getMigratedVisitor() {
        $qb = $this->dm->createQueryBuilder('IbtikarVisitorBundle:Visitor')
                        ->field('migrationPassword')->exists(TRUE)
                        ->field('migrationPassword')->notEqual("")
                        ->limit(10);
        return $qb->getQuery()->execute();
    }

    /**
     * get all users on the target room
     * @param type $roomName
     * @param type $execludedUserId
     * @param type $sortBy
     * @param type $sortOrder
     * @return type
     * @author Maisara Khedr
     */
    public function getRoomUsers($roomName,$execludedUserId=NULL,$sortBy=NULL,$sortOrder=NULL) {
        $q = $this->dm->createQueryBuilder('IbtikarGlanceUMSBundle:Staff')
                        ->field('type')->equals('staff')
                        ->field('permissions.'.$roomName)->exists(true)
                        ->field('permissions.'.$roomName)->notEqual(array())
                        ->field('enabled')->equals(true)
                        ->field('deleted')->equals(false);
        if ($sortBy && $sortOrder) {
            $q = $q->sort($sortBy, $sortOrder);
        }
        if ($execludedUserId) {
            $q = $q->field('id')->notEqual($execludedUserId);
        }
        return $q->getQuery()->execute();
    }

    /**
     * implementation of loadUserByUsername for UserProviderInterface
     * @param string $username
     * @return type
     * @throws UsernameNotFoundException
     */
    public function loadUserByUsername($username) {

        try {
            $user = $this->findOneBy(array(
                '$or' => array(array('email' => strtolower($username)), array('username' => $username)),
                'deleted' => false,
            ));
            if (!$user) {
                throw new \Exception('User not found');
            }
        } catch (\Exception $e) {
            throw new UsernameNotFoundException(sprintf('Unable to find the specified user: "%s"', $username), 0, $e);
        }
        return $user;
    }

    /**
     * implementation of refreshUser for UserProviderInterface
     * @param UserInterface $user
     * @return type
     * @throws UnsupportedUserException
     */
    public function refreshUser(UserInterface $user) {
        $class = get_class($user);
        if (!$this->supportsClass($class)) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $class));
        }
        $loadedUser = $this->find($user->getId());
        if(!$loadedUser || $loadedUser->getId() !== $user->getId()) {
            throw new UsernameNotFoundException(sprintf('Unable to find the specified user: "%s"', $user->getUsername()));
        }
        return $loadedUser;
    }

    /**
     * implementation of supportsClass for UserProviderInterface
     * @param type $class
     * @return type
     */
    public function supportsClass($class) {
        return $this->getDocumentName() === $class || is_subclass_of($class, $this->getDocumentName());
    }


    /**
     * @author Ahmad Gamal <a.gamal@ibtikar.net.sa>
     * @param string $fieldName
     * @param string $fieldValue
     * @return Boolean
     */
    public function checkFieldUnique($fieldName, $fieldValue) {
        $userCount = $this->dm->createQueryBuilder('IbtikarVisitorBundle:Visitor')
                        ->field($fieldName)->equals($fieldValue)
                        ->field('deleted')->equals(FALSE)
                        ->getQuery()->execute()->count();

        return $userCount == 0;
    }

    function isSocialUserRegistered($userProfile,$provider) {
        if(!$userProfile->identifier)
            return null;
        $queryBuilder = $this->getDocumentManager()->createQueryBuilder('IbtikarGlanceUMSBundle:User')
                        ->field($provider . '.id')->equals(strval($userProfile->identifier))
                        ->field('deleted')->equals(false);
        $query = $queryBuilder->getQuery();
        return $query->getSingleResult();
    }


    public function findPhographers() {
        $job = $this->dm->getRepository('IbtikarGlanceUMSBundle:Job')->findOneBy(array('title_en' => Job::$systemEnglishJobTitles['Photographers']));
        return $this->dm->createQueryBuilder('IbtikarGlanceUMSBundle:User')
                        ->field('job')->equals(new \MongoId($job->getId()))
                        ->field('deleted')->equals(false)
                        ->field('enabled')->equals(true)
                        ->sort('fullname', 'ASC');
    }

    /**
     * @author Mahmoud Mostafa <mahmoud.mostafa@ibtikar.net.sa>
     * @author Ola <ola.ali@ibtikar.net.sa>
     * @param string|null $type
     * @return array
     */
    function getValidAuthor($type = null) {
        if ($type === '1-news') {
            $reportersJob = $this->dm->getRepository('IbtikarGlanceUMSBundle:Job')->findOneBy(array('title_en' => 'reporters'));
            return $this->dm->getRepository('IbtikarGlanceUMSBundle:Staff')->findBy(array('job' => $reportersJob->getId(), 'deleted' => false, 'enabled' => true));
        } elseif ($type === '2-article') {
            $writtersJob = $this->dm->getRepository('IbtikarGlanceUMSBundle:Job')->findOneBy(array('title_en' => 'writters'));
            return $this->dm->getRepository('IbtikarGlanceUMSBundle:Staff')->findBy(array('job' => $writtersJob->getId(), 'deleted' => false, 'enabled' => true));
        } elseif ($type === 'photographer') {
            return $this->findPhographers()->getQuery()->execute();
        }
        $jobs = $this->dm->getRepository('IbtikarGlanceUMSBundle:Job')->findBy(array('title_en' => array('$in' => array('reporters', 'writters'))));
        $jobsIds = array();
        foreach ($jobs as $job) {
            $jobsIds [] = $job->getId();
        }
        return $this->dm->getRepository('IbtikarGlanceUMSBundle:Staff')->findBy(array('job' => array('$in' => $jobsIds), 'deleted' => false, 'enabled' => true));
    }

    /**
     * @author Mahmoud Mostafa <mahmoud.mostafa@ibtikar.net.sa>
     * @param array $permissions
     * @return array
     */
    public function getStaffMembersWithGrantedPermissions(array $permissions) {
        $permissionsString = '"' . implode('" ,"', $permissions) . '"';
        $roles = $this->dm->createQueryBuilder('IbtikarGlanceUMSBundle:Role')
                        ->where("function() { for(permission in this.permissions) { if ([$permissionsString].indexOf(this.permissions[permission]) !== -1) { return true; }  } return false;  }")->getQuery()->execute();
        $rolesId = array();
        foreach ($roles as $role) {
            $rolesId[] = $role->getId();
        }
        if (!empty($rolesId)) {
            $groups = $this->dm->getRepository('IbtikarGlanceUMSBundle:Group')->findBy(array('roles' => array('$in' => $rolesId)));
            $groupId = array();
            foreach ($groups as $group) {
                $groupId[] = $group->getId();
            }
            if (!empty($groupId)) {
                $staffMembersQueryBuilder = $this->dm->createQueryBuilder('IbtikarGlanceUMSBundle:Staff')->field('deleted')->equals(false);
                $queryBuilder = $staffMembersQueryBuilder->addAnd(
                        $staffMembersQueryBuilder
                                ->expr()
                                ->addOr(
                                        $staffMembersQueryBuilder
                                        ->expr()
                                        ->field('role')
                                        ->in($rolesId)
                                )
                                ->addOr(
                                        $staffMembersQueryBuilder
                                        ->expr()
                                        ->field('group')
                                        ->in($groupId)
                ));
            } else {
                $queryBuilder = $this->dm->createQueryBuilder('IbtikarGlanceUMSBundle:Staff')->field('deleted')->equals(false)
                        ->field('role')
                        ->in($rolesId);
            }
            return $queryBuilder->getQuery()->execute();
        }
        return array();
    }

}
