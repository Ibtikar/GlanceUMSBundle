<?php

namespace Ibtikar\GlanceUMSBundle\Document;

use Doctrine\Bundle\MongoDBBundle\Validator\Constraints\Unique as MongoDBUnique;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Ibtikar\GlanceUMSBundle\Validator\Constraints as CustomAssert;
use Ibtikar\GlanceUMSBundle\Document\User;
use Ibtikar\AppBundle\Document\Material;
use Ibtikar\GlanceDashboardBundle\Document\Document;
use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * @MongoDB\Document(repositoryClass="Ibtikar\GlanceUMSBundle\Document\UserRepository")
 */
class Staff extends User {

      /**
     * @Assert\NotBlank
     * @MongoDB\String
     */
    private $employeeId;

    /**
     * @Assert\Length(min=5, groups={"username", "Default"})
     * if you need to change the regex also change it in forgotPasswordAction
     * @Assert\Regex(pattern="/^([^_\W]-*)+$/u", message="only characters numbers and dashes allowed", groups={"username", "Default"})
     * @Assert\NotBlank(groups={"username", "Default"})
     * @MongoDB\String
     * @Assert\Length(
     *      max = 330,
     *      maxMessage = "Your name cannot be longer than {{ limit }} characters long"
     * )
     */
    private $username;


     /**
     * @Assert\NotBlank(groups={"gender", "Default", "api-edit"})
     * @MongoDB\String
     */
    private $gender = 'male';

    /**
     * @MongoDB\Boolean
     */
    protected $mustChangePassword = true;


    /**
     * Set employeeId
     *
     * @param string $employeeId
     * @return self
     */
    public function setEmployeeId($employeeId)
    {
        $this->employeeId = $employeeId;
        return $this;
    }

    /**
     * Get employeeId
     *
     * @return string $employeeId
     */
    public function getEmployeeId()
    {
        return $this->employeeId;
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

    public function getPersonTitle() {
        if ($this->gender === 'male') {
            return 'الأستاذ';
        } else if ($this->gender === 'female') {
            return 'الأستاذة';
        } else {
            return 'الأستاذ/ الأستاذة';
        }
    }

}
