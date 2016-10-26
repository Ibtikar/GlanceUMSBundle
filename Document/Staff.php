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
 * @CustomAssert\InternationalPhone
 */
class Staff extends User {



    /**
     * @Assert\Length(min=5, groups={"username", "Default"})
     * if you need to change the regex also change it in forgotPasswordAction
     * @Assert\Regex(pattern="/^([^_\W]-*)+$/u", message="only characters numbers and dashes allowed", groups={"username", "Default"})
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
     * @Assert\NotBlank
     * @MongoDB\ReferenceOne(targetDocument="Ibtikar\GlanceDashboardBundle\Document\Job", simple=true)
     */
    protected $job;

    /**
     * @Assert\NotBlank
     * @MongoDB\ReferenceMany(targetDocument="Ibtikar\GlanceDashboardBundle\Document\Role", simple=true)
     */
    protected $role;

    /**
     * @MongoDB\EmbedOne(targetDocument="Ibtikar\GlanceDashboardBundle\Document\Phone")
     */
    private $mobile;

    /**
     * @MongoDB\String
     */
    private $fakeMobile;


    public $countryCode;



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

    public function getRoles()
    {
        $permissions = parent::getRoles();
        $permissions [] = 'ROLE_STAFF';


        foreach ($this->role as $rolePermissions) {
            $permissions = array_merge($permissions, $rolePermissions->getPermissions());
        }
        return array_unique($permissions);
    }



    /**
     * Set job
     *
     * @param Ibtikar\GlanceDashboardBundle\Document\Job $job
     * @return self
     */
    public function setJob(\Ibtikar\GlanceDashboardBundle\Document\Job $job)
    {
        $this->job = $job;
        return $this;
    }

    /**
     * Get job
     *
     * @return Ibtikar\GlanceDashboardBundle\Document\Job $job
     */
    public function getJob()
    {
        return $this->job;
    }

    /**
     * Add role
     *
     * @param Ibtikar\GlanceDashboardBundle\Document\Role $role
     */
    public function addRole(\Ibtikar\GlanceDashboardBundle\Document\Role $role)
    {
        $this->role[] = $role;
    }

    /**
     * Remove role
     *
     * @param Ibtikar\GlanceDashboardBundle\Document\Role $role
     */
    public function removeRole(\Ibtikar\GlanceDashboardBundle\Document\Role $role)
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
     * Set fakeMobile
     *
     * @param string $fakeMobile
     * @return self
     */
    public function setFakeMobile($fakeMobile)
    {
        $this->fakeMobile = $fakeMobile;
        return $this;
    }

    /**
     * Get fakeMobile
     *
     * @return string $fakeMobile
     */
    public function getFakeMobile()
    {
        return $this->fakeMobile;
    }
}
