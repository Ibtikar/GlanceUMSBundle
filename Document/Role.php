<?php

namespace Ibtikar\GlanceUMSBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Bundle\MongoDBBundle\Validator\Constraints\Unique as MongoDBUnique;
use Ibtikar\GlanceDashboardBundle\Document\Document;
use Ibtikar\GlanceUMSBundle\Document\User;

/**
 * @MongoDB\Document(repositoryClass="Ibtikar\GlanceUMSBundle\Document\RoleRepository")
 * @MongoDBUnique(fields="name")
 * @MongoDB\HasLifecycleCallbacks
 * @MongoDB\Index(keys={"name"="asc"})
 */
class Role extends Document {

    /**
     * @MongoDB\Id
     */
    protected $id;

    /**
     * @Assert\NotBlank
     * @MongoDB\String
     * @Assert\Length(
     *      min=3,
     *      minMessage = "Your name must be at least {{ limit }} characters long",
     *      max = 150,
     *      maxMessage = "Your name cannot be longer than {{ limit }} characters long"
     * )
     */
    protected $name;

    /**
     * @MongoDB\Int
     */
    protected $permissionscount;

    /**
     * @MongoDB\String
     * @Assert\Length(
     *      max = 500,
     *      maxMessage = "Your name cannot be longer than {{ limit }} characters long"
     * )
     */
    protected $description;

    /**
     * @MongoDB\Hash
     * @Assert\Count(
     *      min = "1",
     *      minMessage = "You must have at least 1 Permission"
     * )
     */
    protected $permissions = array();

    /**
     * @MongoDB\Increment
     */
    private $staffMembersCount = 0;

    /**
     * @MongoDB\Date
     */
    protected $editAt;

    public function __toString()
    {
        return "$this->name";
    }

    /**
     * @MongoDB\PreUpdate()
     */
    public function setRolePermissionsCount() {
        $this->setPermissionscount(count($this->permissions));
    }

    /**
     * Get id
     *
     * @return id $id
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return self
     */
    public function setName($name) {
        $this->name = $name;
        return $this;
    }

    /**
     * Get name
     *
     * @return string $name
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Set permissionscount
     *
     * @param int $permissionscount
     * @return self
     */
    public function setPermissionscount($permissionscount) {
        $this->permissionscount = $permissionscount;
        return $this;
    }

    /**
     * Get permissionscount
     *
     * @return int $permissionscount
     */
    public function getPermissionscount() {
        return $this->permissionscount;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return self
     */
    public function setDescription($description) {
        $this->description = $description;
        return $this;
    }

    /**
     * Get description
     *
     * @return string $description
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * Set permission
     *
     * @param hash $permissions
     * @return self
     */
    public function setPermissions($permissions) {
        $this->permissions = $permissions;
        return $this;
    }

    /**
     * Get permission
     *
     * @return hash $permission
     */
    public function getPermissions() {
        return $this->permissions;
    }

    /**
     * refine permissions text by removing "role" word and "_" separator and convert it to lower case
     * @return array of permissions after refining its text
     * @author Maisara Khedr <maisara@ibtikar.net.sa>
     */
    public function getPermissionsDisplayText() {
        $permissionArray = array();
        foreach ($this->permissions as $permission) {
            $permissionArray[] = str_replace("_", " ", strtolower(substr($permission, 5)));
        }
        return $permissionArray;
    }

//    /**
//     * @author Mahmoud Mostafa <mahmoud.mostafa@ibtikar.net.sa>
//     * @param \Doctrine\ODM\MongoDB\DocumentManager $dm
//     * @param \Ibtikar\UserBundle\Document\User $user
//     * @param ContainerInterface $container
//     * @param string $deleteOption
//     */
//    public function delete(DocumentManager $dm, User $user = null, ContainerInterface $container = null, $deleteOption = null) {
//        if ($container) {
//            $staffMembersQuery = $dm->createQueryBuilder('IbtikarGlanceDashboardBundle:Staff');
//            $staffMembersQuery->field('role')->equals($this->getId())->field('deleted')->equals(false)
//                    ->addAnd(
//                            $staffMembersQuery
//                            ->expr()
//                            ->addOr(
//                                    $staffMembersQuery
//                                    ->expr()
//                                    ->field('group')
//                                    ->equals(NULL)
//                            )
//                            ->addOr(
//                                    $staffMembersQuery
//                                    ->expr()
//                                    ->field('group')
//                                    ->exists(FALSE)
//            ));
//            if ($deleteOption == 'delete-deactivate') {
//                $staffMembersQuery->field('enabled')->equals(true);
//            }
//            $staffMembers = $staffMembersQuery->getQuery()->execute();
//            if ($deleteOption == 'delete-deactivate') {
//                foreach ($staffMembers as $staffMember) {
//                    $staffMember->changeUserStatus($container, false, $user);
//                }
//            }
//            if ($deleteOption == 'delete-delete') {
//                foreach ($staffMembers as $staffMember) {
//                    $staffMember->delete($dm, $user);
//                }
//            }
//        }
//        $dm->createQueryBuilder('IbtikarGlanceDashboardBundle:Group')
//                ->update()
//                ->multiple(true)
//                ->field('rolescount')->inc(-1)
//                ->field('roles')->equals($this->getId())
//                ->getQuery()->execute();
//        parent::delete($dm, $user);
//    }

    /**
     * Set staffMembersCount
     *
     * @param increment $staffMembersCount
     * @return self
     */
    public function setStaffMembersCount($staffMembersCount) {
        $this->staffMembersCount = $staffMembersCount;
        return $this;
    }

    /**
     * Get staffMembersCount
     *
     * @return increment $staffMembersCount
     */
    public function getStaffMembersCount() {
        return $this->staffMembersCount;
    }


    /**
     * Set editAt
     *
     * @param date $editAt
     * @return self
     */
    public function setEditAt($editAt)
    {
        $this->editAt = $editAt;
        return $this;
    }

    /**
     * Get editAt
     *
     * @return date $editAt
     */
    public function getEditAt()
    {
        return $this->editAt;
    }
}
