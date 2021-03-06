<?php

namespace Ibtikar\GlanceUMSBundle\Document;

use Doctrine\Bundle\MongoDBBundle\Validator\Constraints\Unique as MongoDBUnique;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Ibtikar\GlanceDashboardBundle\Validator\Constraints as CustomAssert;
use Ibtikar\GlanceUMSBundle\Document\User;
use Ibtikar\GlanceDashboardBundle\Document\Document;
use Doctrine\ODM\MongoDB\DocumentManager;
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber as AssertPhoneNumber;

/**
 * @MongoDB\Document(repositoryClass="Ibtikar\GlanceUMSBundle\Document\UserRepository")
 * @MongoDBUnique(fields={"username"}, repositoryMethod="findUserByUsername", groups={"username", "Default"})
 * @CustomAssert\InternationalPhone
 */
class Staff extends User {



    /**
     * @Assert\Length(min=5, groups={"username", "Default"})
     * if you need to change the regex also change it in forgotPasswordAction
     * @Assert\Regex(pattern="/^([^_\W]-*)+$/u", message="username should contains characters, numbers or dash only", groups={"username", "Default"})
     * @Assert\NotBlank(groups={"username", "Default"})
     * @MongoDB\String
     * @Assert\Length(
     *      max = 150,
     *      maxMessage = "Your name cannot be longer than {{ limit }} characters long"
     * )
     */
    private $username;

    /**
     * @MongoDB\String
     */
    private $gender;

    /**
     * @MongoDB\Boolean
     */
    protected $mustChangePassword = true;

    /**
     * @MongoDB\ReferenceOne(targetDocument="Ibtikar\GlanceUMSBundle\Document\Job", simple=true)
     */
    protected $job;

    /**
     * @Assert\NotBlank
     * @MongoDB\ReferenceMany(targetDocument="Ibtikar\GlanceUMSBundle\Document\Role", simple=true)
     */
    protected $role;

    /**
     * @MongoDB\EmbedOne(targetDocument="Ibtikar\GlanceDashboardBundle\Document\Phone")
     */
    private $mobile;

    public $countryCode;

    /**
     * @MongoDB\Boolean
     */
    protected $forceLogout = false;

    /**
     * @MongoDB\Date
     */
    protected $editDate;

    /**
     * @Assert\NotBlank(groups={"firstName", "Default", "api-edit"})
     * @MongoDB\String
     * @Assert\Length(
     *      max = 150,
     *      maxMessage = "Your name cannot be longer than {{ limit }} characters long"
     * )
     */
    private $firstNameEn;

    /**
     * @Assert\NotBlank(groups={"lastName", "Default", "api-edit"})
     * @MongoDB\String
     * @Assert\Length(
     *      max = 150,
     *      maxMessage = "Your name cannot be longer than {{ limit }} characters long"
     * )
     */
    private $lastNameEn;

    /**
     * @MongoDB\String
     */
    protected $fullnameEn;

    /**
     * @return string the object name
     */
    public function __toString()
    {
        return "$this->username";
    }

    public function saveFullNameEn()
    {
        $this->fullnameEn = $this->getFirstNameEn() . " " . $this->getLastNameEn();
    }

    /**
     * Set username
     *
     * @param string $username
     * @return self
     */
    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }

    /**
     * Get username
     *
     * @return string $username
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set gender
     *
     * @param string $gender
     * @return self
     */
    public function setGender($gender)
    {
        $this->gender = $gender;
        return $this;
    }

    /**
     * Get gender
     *
     * @return string $gender
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * Set mustChangePassword
     *
     * @param boolean $mustChangePassword
     * @return self
     */
    public function setMustChangePassword($mustChangePassword)
    {
        $this->mustChangePassword = $mustChangePassword;
        return $this;
    }

    /**
     * Get mustChangePassword
     *
     * @return boolean $mustChangePassword
     */
    public function getMustChangePassword()
    {
        return $this->mustChangePassword;
    }


    public function updateReferencesCounts($value) {
        parent::updateReferencesCounts($value);
        $job = $this->getJob();
        if ($job) {
            $job->setStaffMembersCount($job->getStaffMembersCount() + $value);
        }
    }

    public function getPersonTitle()
    {
        if ($this->gender === 'male') {
            return 'الأستاذ';
        } else if ($this->gender === 'female') {
            return 'الأستاذة';
        } else {
            return 'الأستاذ/ الأستاذة';
        }
    }

    /**
     * @return array
     */
    public static function getValidGenders()
    {
        return array(
            'male' => 'male',
            'female' => 'female'
        );
    }

    public function updateStaffCountOnEdit($fieldName,$changeset) {
        parent::updateUserCountOnEdit($fieldName,$changeset);
        if(in_array($fieldName, array("job"))) {
            $oldObject = $changeset[0];
            if($oldObject) {
                $oldObject->setStaffMembersCount($oldObject->getStaffMembersCount() - 1);
            }
            $newObject = $changeset[1];
            if($newObject) {
                $newObject->setStaffMembersCount($newObject->getStaffMembersCount() + 1);
            }
        }
    }

    public function isEqualTo(UserInterface $user) {
        if (parent::isEqualTo($user)) {
            // Check that the roles are the same, in any order
            $isEqual = count($this->getRoles()) == count($user->getRoles());
            if ($isEqual) {
                foreach ($this->getRoles() as $role) {
                    $isEqual = $isEqual && in_array($role, $user->getRoles());
                }
            }
            return $isEqual;
        }
        return false;
    }



    public function getRoles()
    {
        $permissions = parent::getRoles();
        $permissions [] = 'ROLE_STAFF';

        if ($this->role) {
            foreach ($this->role as $rolePermissions) {
                $permissions = array_merge($permissions, $rolePermissions->getPermissions());
            }
        }
        return array_unique($permissions);
    }

    /**
     * Set job
     *
     * @param Ibtikar\GlanceUMSBundle\Document\Job $job
     * @return self
     */
    public function setJob(\Ibtikar\GlanceUMSBundle\Document\Job $job)
    {
        $this->job = $job;
        return $this;
    }

    /**
     * Get job
     *
     * @return Ibtikar\GlanceUMSBundle\Document\Job $job
     */
    public function getJob()
    {
        return $this->job;
    }

    /**
     * Add role
     *
     * @param Ibtikar\GlanceUMSBundle\Document\Role $role
     */
    public function addRole(\Ibtikar\GlanceUMSBundle\Document\Role $role)
    {
        $this->role[] = $role;
    }

    /**
     * Remove role
     *
     * @param Ibtikar\GlanceUMSBundle\Document\Role $role
     */
    public function removeRole(\Ibtikar\GlanceUMSBundle\Document\Role $role)
    {
        $this->role->removeElement($role);
    }

    /**
     * Get role
     *
     * @return \Doctrine\Common\Collections\Collection $role
     */
    public function getRole()
    {
        return $this->role;
    }




    /**
     * Set mobile
     *
     * @param Ibtikar\GlanceDashboardBundle\Document\Phone $mobile
     * @return self
     */
    public function setMobile(\Ibtikar\GlanceDashboardBundle\Document\Phone $mobile)
    {
        $this->mobile = $mobile;
        return $this;
    }

    /**
     * Get mobile
     *
     * @return Ibtikar\GlanceDashboardBundle\Document\Phone $mobile
     */
    public function getMobile()
    {
        return $this->mobile;
    }





    /**
     * Set forceLogout
     *
     * @param boolean $forceLogout
     * @return self
     */
    public function setForceLogout($forceLogout)
    {
        $this->forceLogout = $forceLogout;
        return $this;
    }

    /**
     * Get forceLogout
     *
     * @return boolean $forceLogout
     */
    public function getForceLogout()
    {
        return $this->forceLogout;
    }

    /**
     * Set editDate
     *
     * @param date $editDate
     * @return self
     */
    public function setEditDate($editDate)
    {
        $this->editDate = $editDate;
        return $this;
    }

    /**
     * Get editDate
     *
     * @return date $editDate
     */
    public function getEditDate()
    {
        return $this->editDate;
    }

    /**
     * Set firstNameEn
     *
     * @param string $firstNameEn
     * @return self
     */
    public function setFirstNameEn($firstNameEn)
    {
        $this->firstNameEn = $firstNameEn;
        $this->saveFullNameEn();

        return $this;
    }

    /**
     * Get firstNameEn
     *
     * @return string $firstNameEn
     */
    public function getFirstNameEn()
    {
        return $this->firstNameEn;
    }

    /**
     * Set lastNameEn
     *
     * @param string $lastNameEn
     * @return self
     */
    public function setLastNameEn($lastNameEn)
    {
        $this->lastNameEn = $lastNameEn;
        $this->saveFullNameEn();

        return $this;
    }

    /**
     * Get lastNameEn
     *
     * @return string $lastNameEn
     */
    public function getLastNameEn()
    {
        return $this->lastNameEn;
    }

    /**
     * Set fullnameEn
     *
     * @param string $fullnameEn
     * @return self
     */
    public function setFullnameEn($fullnameEn)
    {
        $this->fullnameEn = $fullnameEn;
        return $this;
    }

    /**
     * Get fullnameEn
     *
     * @return string $fullnameEn
     */
    public function getFullnameEn()
    {
        return $this->fullnameEn;
    }
}
