<?php

namespace Ibtikar\GlanceUMSBundle\Document;

use Doctrine\Bundle\MongoDBBundle\Validator\Constraints\Unique as MongoDBUnique;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Ibtikar\GlanceUMSBundle\Document\User;

/**
 *  @MongoDB\Document(repositoryClass="Ibtikar\GlanceUMSBundle\Document\UserRepository")
 */
class Visitor extends User {

    
    /**
     * @Assert\NotBlank(groups={"Default", "visitorSignup", "api-edit"})
     * @MongoDB\String
     * @Assert\Length(min=5, groups={"visitorSignup", "Default"})
     * @Assert\Length(
     *      max = 150,
     *      maxMessage = "Your name cannot be longer than {{ limit }} characters long"
     * )
     */
    private $nickName;
    
    
    /**
     * @Assert\Length(min=5, groups={"username", "visitorSignup", "Default"})
     * if you need to change the regex also change it in forgotPasswordAction
     * @Assert\Regex(pattern="/^([^_\W]-*)+$/u", message="username should contains characters, numbers or dash only", groups={"username", "visitorSignup", "Default"})
     * @Assert\NotBlank(groups={"username", "Default"})
     * @MongoDB\String
     * @Assert\Length(
     *      max = 150,
     *      maxMessage = "Your name cannot be longer than {{ limit }} characters long"
     * )
     */
    private $username;
    
    /**
     * @Assert\NotBlank(groups={"Default", "visitorSignup", "api-edit"})
     * @MongoDB\String
     */
    private $gender = 'male';

    /**
     * @MongoDB\String
     */
    private $registeredFrom;


      /**
     * @MongoDB\Boolean
     */
    protected $mustChangePassword = false;

    /**
     * Set nickName
     *
     * @param string $nickName
     * @return self
     */
    public function setNickName($nickName)
    {
        $this->nickName = $nickName;
        return $this;
    }

    /**
     * Get nickName
     *
     * @return string $nickName
     */
    public function getNickName()
    {
        return $this->nickName;
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
     * Set registeredFrom
     *
     * @param string $registeredFrom
     * @return self
     */
    public function setRegisteredFrom($registeredFrom)
    {
        $this->registeredFrom = $registeredFrom;
        return $this;
    }

    /**
     * Get registeredFrom
     *
     * @return string $registeredFrom
     */
    public function getRegisteredFrom()
    {
        return $this->registeredFrom;
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

  
}
