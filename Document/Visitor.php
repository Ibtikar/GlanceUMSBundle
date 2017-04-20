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
     * @MongoDB\String
     */
    private $registeredFrom;


    /**
     * @Assert\Valid
     * @MongoDB\EmbedOne(targetDocument="Ibtikar\GlanceUMSBundle\Document\Social\Facebook")
     */
    private $facebook;


    /**
     * @Assert\Valid
     * @MongoDB\EmbedOne(targetDocument="Ibtikar\GlanceUMSBundle\Document\Social\Twitter")
     */
    private $twitter;

    /**
     * @MongoDB\EmbedOne(targetDocument="Ibtikar\GlanceUMSBundle\Document\Social\Google")
     */
    private $google;


      /**
     * @MongoDB\Boolean
     */
    protected $mustChangePassword = false;


       public function __toString()
    {
        return "$this->nickName";
    }

    public function getPersonTitle()
    {
        if ($this->gender === 'male') {
            return 'mr';
        } else if ($this->gender === 'female') {
            return 'mrs';
        } else {
            return 'mr/ mrs';
        }
    }


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

    /**
     * Set twitter
     *
     * @param string $twitter
     * @return self
     */
    public function setTwitter($twitter) {
        $this->twitter = $twitter;
        return $this;
    }

    /**
     * Get twitter
     *
     * @return string $twitter
     */
    public function getTwitter() {
        return $this->twitter;
    }


    /**
     * Set google
     *
     * @param Ibtikar\GlanceUMSBundle\Document\Social\Google $google
     * @return self
     */
    public function setGoogle(\Ibtikar\GlanceUMSBundle\Document\Social\Google $google)
    {
        $this->google = $google;
        return $this;
    }

    /**
     * Get Google
     *
     * @return Ibtikar\GlanceUMSBundle\Document\Social\Google $google
     */
    public function getGoogle()
    {
        return $this->google;
    }


    /**
     * Set facebook
     *
     * @param Ibtikar\GlanceUMSBundle\Document\Social\Facebook $facebook
     * @return self
     */
    public function setFacebook(\Ibtikar\GlanceUMSBundle\Document\Social\Facebook $facebook) {
        $this->facebook = $facebook;
        return $this;
    }

    /**
     * Get facebook
     *
     * @return Ibtikar\GlanceUMSBundle\Document\Social\Facebook $facebook
     */
    public function getFacebook() {
        return $this->facebook;
    }


}
