<?php

namespace Ibtikar\GlanceUMSBundle\Document;

use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use Symfony\Component\Security\Core\Validator\Constraints as SecurityAssert;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use Ibtikar\GlanceUMSBundle\Validator\Constraints as CustomAssert;
use Doctrine\Bundle\MongoDBBundle\Validator\Constraints\Unique as MongoDBUnique;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Ibtikar\GlanceDashboardBundle\Document\Document;

/**
 * @MongoDB\InheritanceType("SINGLE_COLLECTION")
 * @MongoDB\DiscriminatorField("type")
 * @MongoDB\DiscriminatorMap({"visitor"="Ibtikar\GlanceUMSBundle\Document\Visitor", "staff"="Ibtikar\GlanceUMSBundle\Document\Staff"})
 * @MongoDB\Document(repositoryClass="Ibtikar\GlanceUMSBundle\Document\UserRepository")
 * @MongoDB\HasLifecycleCallbacks
 * @MongoDBUnique(fields={"email"}, repositoryMethod="findUserByEmail", groups={"email", "Default", "visitorSignup", "api-edit"})
 * @MongoDB\Indexes({
 *  @MongoDB\Index(keys={"job"="asc", "deleted"="asc", "enabled"="asc", "fullname"="asc"}, options={"name"="staff members select"}),
 *  @MongoDB\Index(keys={"type"="asc"}, options={"name"="staff members"}),
 *  @MongoDB\Index(keys={"email"="asc"}),
 *  @MongoDB\Index(keys={"username"="asc"}),
 *  @MongoDB\Index(keys={"employeeId"="asc"}),
 *  @MongoDB\Index(keys={"nickName"="asc"}),
 *  @MongoDB\Index(keys={"firstName"="asc"}),
 *  @MongoDB\Index(keys={"lastName"="asc"}),
 *  @MongoDB\Index(keys={"fullname"="asc"}),
 *  @MongoDB\Index(keys={"gender"="asc"}),
 *  @MongoDB\Index(keys={"mobile"="asc"}),
 *  @MongoDB\Index(keys={"lastLoginTime"="asc"}),
 *  @MongoDB\Index(keys={"createdAt"="asc"}),
 *  @MongoDB\Index(keys={"isTopAuthor"="asc", "type"="asc"}, options={"name"="Top authors"}),
 *  @MongoDB\Index(keys={"admin"="asc", "deleted"="asc", "id"="asc", "type"="asc", "fullname"="asc"}, options={"name"="staff list"}),
 *  @MongoDB\Index(keys={"enabled"="asc", "deleted"="asc", "type"="asc", "emailVerified"="asc"}, options={"name"="export visitor list"}),
 *  @MongoDB\Index(keys={"deleted"="asc", "enabled"="asc", "emailVerified"="asc", "type"="asc"}),
 *  @MongoDB\Index(keys={"twitter.id"="asc"}),
 *  @MongoDB\Index(keys={"google.id"="asc"}),
 *  @MongoDB\Index(keys={"linkedIn.id"="asc"}),
 *  @MongoDB\Index(keys={"yahoo.id"="asc"}),
 *  @MongoDB\Index(keys={"facebook.id"="asc"}),
 * })
 */
class User extends Document implements AdvancedUserInterface, EquatableInterface
{

    /**
     * @MongoDB\Id
     */
    protected $id;

    /**
     * @MongoDB\ReferenceOne(targetDocument="Ibtikar\GlanceUMSBundle\Document\Country", simple=true)
     */
    protected $country;

    /**
     * @Assert\NotBlank(groups={"city", "Default", "api-edit"})
     * @MongoDB\ReferenceOne(targetDocument="Ibtikar\GlanceUMSBundle\Document\City",  simple=true)
     */
    protected $city;

    /**
     * @Assert\Email(groups={"email", "Default", "visitorSignup", "forgot-password", "api-edit"})
     * @Assert\NotBlank(groups={"email", "Default", "visitorSignup", "forgot-password", "api-edit"})
     * @CustomAssert\EmailExist(groups={"email", "Default", "visitorSignup", "forgot-password", "api-edit"})
     * @MongoDB\String
     * @Assert\Length(
     *      max = 330,
     *      maxMessage = "Your name cannot be longer than {{ limit }} characters long"
     * )
     */
    protected $email;

    /**
     * @MongoDB\Boolean
     */
    protected $emailVerified = false;

    /**
     * @MongoDB\String
     */
    protected $password;

    /**
     * @SecurityAssert\UserPassword(groups={"old-password"})
     * @Assert\NotBlank(groups={"old-password"})
     */
    protected $oldPassword;

    /**
     * @var string
     * @Assert\Length(min=8, max=4096, maxMessage="The Password must be {{ limit }} maximum characters and numbers length", minMessage="The Password must be at least {{ limit }} characters and numbers length", groups={"Default", "change-password", "visitorSignup", "api-edit"})
     * @Assert\Regex(pattern="/[\D+]+/u", message="The Password must be at least {{ limit }} characters and numbers length", groups={"Default", "change-password", "visitorSignup", "api-edit"})
     * @Assert\Regex(pattern="/\d+/u", message="The Password must be at least {{ limit }} characters and numbers length", groups={"Default", "change-password", "visitorSignup", "api-edit"})
     */
    protected $userPassword;

    /**
     * @MongoDB\String
     */
    protected $changePasswordToken;

    /**
     * @MongoDB\Date
     */
    protected $changePasswordTokenExpiryDate;

    /**
     * @MongoDB\String
     */
    protected $token;

    /**
     * @MongoDB\String
     */
    protected $lastLoginIp;

    /**
     * @MongoDB\Date
     */
    protected $lastLoginTime;

    /**
     * @MongoDB\String
     */
    protected $lastLoginFrom;

    /**
     * @Assert\NotBlank(groups={"firstName", "Default", "visitorSignup", "api-edit"})
     * @MongoDB\String
     * @Assert\Length(
     *      max = 150,
     *      maxMessage = "Your name cannot be longer than {{ limit }} characters long"
     * )
     */
    private $firstName;

    /**
     * @Assert\NotBlank(groups={"lastName", "Default", "visitorSignup", "api-edit"})
     * @MongoDB\String
     * @Assert\Length(
     *      max = 150,
     *      maxMessage = "Your name cannot be longer than {{ limit }} characters long"
     * )
     */
    private $lastName;

    /**
     * @MongoDB\String
     */
    protected $fullname;

    /**
     * @MongoDB\Boolean
     */
    protected $admin = false;

    /**
     * @MongoDB\Boolean
     */
    private $enabled = true;

    /**
     * @MongoDB\String
     */
    protected $salt;

    /**
     * @MongoDB\Date
     */
    protected $lastSeen;

    /**
     * @MongoDB\String
     */
    protected $image;

    /**
     * a temp variable for storing the old image name to delete the old image after the update
     */
    private $temp;

    /**
     * a temp variable used to know if we need to resize the image
     */
    private $imageNeedResize = false;

    /**
     * @Assert\NotBlank(groups={"image-required"})
     * @Assert\Image(minWidth=200, minHeight=200, minWidthMessage="Image dimension must be more than 200*200", minHeightMessage="Image dimension must be more than 200*200", mimeTypes={"image/jpeg", "image/pjpeg", "image/png"}, groups={"image", "Default"}, mimeTypesMessage="picture not correct.")
     * @Assert\File(maxSize="3145728", maxSizeMessage="File size must be less than 3mb", groups={"image", "Default"})
     * @CustomAssert\ImageValid(groups={"image", "Default"})
     * @CustomAssert\ImageExtensionValid(groups={"image", "Default"}, extensions={"jpg", "jpeg", "png"})
     * @var UploadedFile
     */
    private $file;

    /**
     * @var array
     */
    private $defaultImages = array(
        "unspecified" => "profile.jpg",
        "male" => "profile-man.jpg",
        "female" => "profile-girl.jpg"
    );

    public function getOnlineStatus()
    {
        $interval = $this->lastSeen->diff(new \DateTime());
        $intervalInSec = ($interval->s) + ($interval->i * 60) + ($interval->h * 60 * 60) + ($interval->d * 60 * 60 * 24) + ($interval->m * 60 * 60 * 24 * 30) + ($interval->y * 60 * 60 * 24 * 365);
        if ($intervalInSec > 40) {
            return false;
        }
        return true;
    }

    public function updateUserCountOnEdit($fieldName, $changeset) {
        if (in_array($fieldName, array("country"))) {
            $oldObject = $changeset[0];
            if ($oldObject) {
                $oldObject->setCountryUsageCount($oldObject->getCountryUsageCount() - 1);
            }
            $newObject = $changeset[1];
            if ($newObject) {
                $newObject->setCountryUsageCount($newObject->getCountryUsageCount() + 1);
            }
        }
//        if (in_array($fieldName, array("city"))) {
//            $oldObject = $changeset[0];
//            if ($oldObject) {
//                $oldObject->setUsersCount($oldObject->getUsersCount() - 1);
//            }
//            $newObject = $changeset[1];
//            if ($newObject) {
//                $newObject->setUsersCount($newObject->getUsersCount() + 1);
//            }
//        }
    }

    /**
     * Set image
     *
     * @param string $image
     * @return User
     */
    public function setImage($image)
    {
        $this->image = $image;
        return $this;
    }

    /**
     * Get image
     *
     * @return string
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Set file
     *
     * @param UploadedFile $file
     * @return User
     */
    public function setFile($file)
    {
        $this->file = $file;
        //check if we have an old image
        if ($this->image) {
            //store the old name to delete on the update
            $this->temp = $this->image;
            $this->image = NULL;
        } else {
            $this->image = 'initial';
        }
        return $this;
    }

    /**
     * Get file
     *
     * @return UploadedFile
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * this function is used to delete the current image
     * the deleting of the current object will also delete the image and you do not need to call this function
     * if you call this function before you remove the object the image will not be removed
     */
    public function removeImage()
    {
        //check if we have an old image
        if ($this->image) {
            //store the old name to delete on the update
            $this->temp = $this->image;
            //delete the current image
            $this->image = NULL;
        }
    }

    /**
     * create the the directory if not found
     * @param string $directoryPath
     * @throws \Exception if the directory can not be created
     */
    protected function createDirectory($directoryPath)
    {
        if (!@is_dir($directoryPath)) {
            $oldumask = umask(0);
            $success = @mkdir($directoryPath, 0755, TRUE);
            umask($oldumask);
            if (!$success) {
                throw new \Exception("Can not create the directory $directoryPath");
            }
        }
    }

    /**
     * @MongoDB\PrePersist()
     * @MongoDB\PreUpdate()
     */
    public function preUpload()
    {
        if (NULL !== $this->file && (NULL === $this->image || 'initial' === $this->image)) {
            //get the image extension
            if ($this->file instanceof UploadedFile) {
                $extension = strtolower($this->file->getClientOriginalExtension());
            } else {
                $extension = $this->file->guessExtension();
            }
            //generate a random image name
            $img = uniqid();
            //get the image upload directory
            $uploadDir = $this->getUploadRootDir();
            $this->createDirectory($uploadDir);
            //check that the file name does not exist
            while (@file_exists("$uploadDir/$img.$extension")) {
                //try to find a new unique name
                $img = uniqid();
            }
            //set the image new name
            $this->image = "$img.$extension";
        }
    }

    /**
     * @MongoDB\PostPersist()
     * @MongoDB\PostUpdate()
     */
    public function upload()
    {
        if (NULL !== $this->file) {
            // you must throw an exception here if the file cannot be moved
            // so that the entity is not persisted to the database
            // which the UploadedFile move() method does
            $this->file->move($this->getUploadRootDir(), $this->image);
            $this->imageNeedResize = true;
            //remove the file as you do not need it any more
            $this->file = NULL;
        }
        //check if we have an old image
        if ($this->temp) {
            //try to delete the old image
            @unlink($this->getUploadRootDir() . '/' . $this->temp);
            //clear the temp image
            $this->temp = NULL;
        }
    }

    /**
     * @MongoDB\PostRemove()
     */
    public function postRemove()
    {
        //check if we have an image
        if ($this->image) {
            //try to delete the image
            @unlink($this->getAbsolutePath());
        }
    }

    /**
     * @return string the path of image starting of root
     */
    public function getAbsolutePath()
    {
        return $this->getUploadRootDir() . '/' . $this->image;
    }

    /**
     * @return string the relative path of image starting from web directory
     */
    public function getWebPath($getDefault = false)
    {
        return NULL === $this->image ? NULL : $this->getUploadDir() . '/' . $this->image;

    }

    /**
     * @return return only default image based on gender
     */
    public function getDefaultImageWebPath()
    {

        if ($this->getGender() === "male" || $this->getGender() === "female") {
            $image = $this->defaultImages[$this->getGender()];
        } else {
            $image = $this->defaultImages['unspecified'];
        }

        return NULL === $image ? NULL : $this->getUploadDir() . '/' . $image;
    }

    /**
     * @return string the relative path of image starting from web directory
     */
    public function getRealWebPath()
    {
        $image = $this->image;
        return NULL === $image ? NULL : $this->getUploadDir() . '/' . $image;
    }

    /**
     * @return string the path of upload directory starting of root
     */
    public function getUploadRootDir()
    {
        // the absolute directory path where uploaded documents should be saved
        return __DIR__ . '/../../../../web/' . $this->getUploadDir();
    }

    /**
     * @return string the document upload directory path starting from web folder
     */
    protected function getUploadDir()
    {
        return 'uploads/users-profile-images';
    }

    /**
     * initialize the main default attributes
     */
    public function __construct()
    {
        $this->token = md5(uniqid(rand()));
        $this->salt = md5(time());
        $this->lastSeen = new \DateTime();
    }

    /**
     * @return string the object name
     */
    public function __toString()
    {
        return "$this->firstName $this->lastName";
    }

    /**
     * this function will set the valid password for the user
     * @MongoDB\PrePersist()
     * @MongoDB\PreUpdate()
     * @return User
     */
    public function setValidPassword()
    {
        //check if we have a password
        if ($this->getUserPassword()) {
            //hash the password
            $this->setPassword($this->hashPassword($this->getUserPassword()));
        }
        return $this;
    }

    /**
     * this function will hash a password and return the hashed value
     * the encoding has to be the same as the one in the project security.yml file
     * @param string $password the password to return it is hash
     */
    private function hashPassword($password)
    {
        //create an encoder object
        $encoder = new MessageDigestPasswordEncoder('sha512', true, 10);
        //return the hashed password
        return $encoder->encodePassword($password, $this->getSalt());
    }

    /**
     * Set userPassword
     *
     * @param string $password
     * @return User
     */
    public function setUserPassword($password)
    {
        $this->userPassword = $password;
        return $this;
    }

    /**
     * @return string the user password
     */
    public function getUserPassword()
    {
        return $this->userPassword;
    }

    /**
     * Implementation of getRoles for the UserInterface.
     *
     * @return array An array of Roles
     */
    public function getRoles()
    {
        $permissions = array('ROLE_LOGGED_IN');
        if ($this->admin) {
            $permissions [] = 'ROLE_ADMIN';
        }
        return $permissions;
    }

    /**
     * Implementation of eraseCredentials for the UserInterface.
     */
    public function eraseCredentials()
    {
        $this->oldPassword = null;
    }

    /**
     * Implementation of getPassword for the UserInterface.
     * @return string the hashed user password
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Implementation of getSalt for the UserInterface.
     * @return string the user salt
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * Implementation of getUsername for the UserInterface.
     * check security.yml to know the used column by the firewall
     * @return string the user name used by the firewall configurations.
     */
    public function getUsername()
    {
        return $this->email;
    }

    /**
     * @param \Symfony\Component\Security\Core\User\UserInterface $user
     * @return boolean
     */
    public function isEqualTo(UserInterface $user)
    {
        if (!is_a($user, get_class($this))) {
            return false;
        }
        if ($this->enabled !== $user->getEnabled()) {
            return false;
        }
        if ($this->getDeleted() !== $user->getDeleted()) {
            return false;
        }
        if ($this->id !== $user->getId()) {
            return false;
        }
        return true;
    }

    /**
     * Implementation of isAccountNonExpired for the AdvancedUserInterface.
     * @return boolean
     */
    public function isAccountNonExpired()
    {
        return TRUE;
    }

    /**
     * Implementation of isCredentialsNonExpired for the AdvancedUserInterface.
     * @return boolean
     */
    public function isCredentialsNonExpired()
    {
        return $this->enabled && !$this->getDeleted();
    }

    /**
     * Implementation of isAccountNonLocked for the AdvancedUserInterface.
     * @return boolean
     */
    public function isAccountNonLocked()
    {
        return TRUE;
    }

    /**
     * Implementation of isEnabled for the AdvancedUserInterface.
     * @return boolean
     */
    public function isEnabled()
    {
        return TRUE;
    }

    /**
     * Get id
     *
     * @return id $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return self
     */
    public function setEmail($email)
    {
        $this->email = strtolower($email);
        return $this;
    }

    /**
     * Get email
     *
     * @return string $email
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set password
     *
     * @param string $password
     * @return self
     */
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    /**
     * Set token
     *
     * @param string $token
     * @return self
     */
    public function setToken($token)
    {
        $this->token = $token;
        return $this;
    }

    /**
     * Get token
     *
     * @return string $token
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set firstName
     *
     * @param string $firstName
     * @return self
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
        $this->saveFullName();
        return $this;
    }

    /**
     * Get firstName
     *
     * @return string $firstName
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Set lastName
     *
     * @param string $lastName
     * @return self
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
        $this->saveFullName();
        return $this;
    }

    /**
     * Get lastName
     *
     * @return string $lastName
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * Set enabled
     *
     * @param boolean $enabled
     * @return self
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
        return $this;
    }

    /**
     * Get enabled
     *
     * @return boolean $enabled
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * Set salt
     *
     * @param string $salt
     * @return self
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;
        return $this;
    }

    /**
     * Set admin
     *
     * @param boolean $admin
     * @return self
     */
    public function setAdmin($admin)
    {
        $this->admin = $admin;
        return $this;
    }

    /**
     * Get admin
     *
     * @return boolean $admin
     */
    public function getAdmin()
    {
        return $this->admin;
    }

    /**
     * Set lastLoginIp
     *
     * @param string $lastLoginIp
     * @return self
     */
    public function setLastLoginIp($lastLoginIp)
    {
        $this->lastLoginIp = $lastLoginIp;
        return $this;
    }

    /**
     * Get lastLoginIp
     *
     * @return string $lastLoginIp
     */
    public function getLastLoginIp()
    {
        return $this->lastLoginIp;
    }

    /**
     * Set lastLoginTime
     *
     * @param date $lastLoginTime
     * @return self
     */
    public function setLastLoginTime($lastLoginTime)
    {
        $this->lastLoginTime = $lastLoginTime;
        return $this;
    }

    /**
     * Get lastLoginTime
     *
     * @return date $lastLoginTime
     */
    public function getLastLoginTime()
    {
        return $this->lastLoginTime;
    }

    /**
     * Set oldPassword
     *
     * @param string $oldPassword
     * @return self
     */
    public function setOldPassword($oldPassword)
    {
        $this->oldPassword = $oldPassword;
        return $this;
    }

    /**
     * Get oldPassword
     *
     * @return string $oldPassword
     */
    public function getOldPassword()
    {
        return $this->oldPassword;
    }

    /**
     * Set fullname
     *
     * @param string $fullname
     * @return self
     */
    public function setFullname($fullname)
    {
        $this->fullname = $fullname;
        return $this;
    }

    /**
     * Get fullname
     *
     * @return string $fullname
     */
    public function getFullname()
    {
        return $this->fullname;
    }

    public function saveFullName()
    {
        $this->fullname = $this->getFirstName() . " " . $this->getLastName();
    }

    /**
     * Set changePasswordToken
     *
     * @param string $changePasswordToken
     * @return self
     */
    public function setChangePasswordToken($changePasswordToken)
    {
        $this->changePasswordToken = $changePasswordToken;
        return $this;
    }

    /**
     * Get changePasswordToken
     *
     * @return string $changePasswordToken
     */
    public function getChangePasswordToken()
    {
        return $this->changePasswordToken;
    }

    /**
     * Set changePasswordTokenExpiryDate
     *
     * @param date $changePasswordTokenExpiryDate
     * @return self
     */
    public function setChangePasswordTokenExpiryDate($changePasswordTokenExpiryDate)
    {
        $this->changePasswordTokenExpiryDate = $changePasswordTokenExpiryDate;
        return $this;
    }

    /**
     * Get changePasswordTokenExpiryDate
     *
     * @return date $changePasswordTokenExpiryDate
     */
    public function getChangePasswordTokenExpiryDate()
    {
        return $this->changePasswordTokenExpiryDate;
    }

    /**
     * Set emailVerified
     *
     * @param boolean $emailVerified
     * @return self
     */
    public function setEmailVerified($emailVerified)
    {
        $this->emailVerified = $emailVerified;
        return $this;
    }

    /**
     * Get emailVerified
     *
     * @return boolean $emailVerified
     */
    public function getEmailVerified()
    {
        return $this->emailVerified;
    }

    /**
     * Set lastSeen
     *
     * @param \DateTime $lastSeen
     * @return User
     */
    public function setLastSeen($lastSeen)
    {
        $this->lastSeen = $lastSeen;
        return $this;
    }

    /**
     * Get lastSeen
     *
     * @return \DateTime
     */
    public function getLastSeen()
    {
        return $this->lastSeen;
    }

    public function refreshForgotPasswordToken()
    {
        $this->changePasswordToken = MD5(uniqid());
        $this->changePasswordTokenExpiryDate = new \DateTime('+1 day');
    }

    /**
     * @Assert\Callback(groups={"old-password"})
     */
    public function isCurrentPasswordValid(ExecutionContextInterface $context)
    {
        $encryptedCurrentPassword = $this->hashPassword($this->getUserPassword());
        if ($encryptedCurrentPassword === $this->getPassword()) {
            $context->addViolation('Current password is not valid');
        }
    }


    /**
     * {@inheritDoc}
     */
    public function updateReferencesCounts($value) {
        parent::updateReferencesCounts($value);
        $city = $this->getCity();
        if ($city) {
            $city->setStaffMembersCount($city->getStaffMembersCount() + $value);
        }
//        $country = $this->getCountry();
//        if ($country) {
//            $country->setCountryUsageCount($country->getCountryUsageCount() + $value);
//        }
    }
//    /**
//     *
//     * @param type $fieldName
//     * @param type $changeset
//     */
//    public function updateUserCountOnEdit($fieldName, $changeset) {
//        if (in_array($fieldName, array("country"))) {
//            $oldObject = $changeset[0];
//            if ($oldObject) {
//                $oldObject->setCountryUsageCount($oldObject->getCountryUsageCount() - 1);
//            }
//            $newObject = $changeset[1];
//            if ($newObject) {
//                $newObject->setCountryUsageCount($newObject->getCountryUsageCount() + 1);
//            }
//        }
//        if (in_array($fieldName, array("city"))) {
//            $oldObject = $changeset[0];
//            if ($oldObject) {
//                $oldObject->setUsersCount($oldObject->getUsersCount() - 1);
//            }
//            $newObject = $changeset[1];
//            if ($newObject) {
//                $newObject->setUsersCount($newObject->getUsersCount() + 1);
//            }
//        }
//    }

    public function getImageFromUrl($url, $gender = null)
    {
        if ($url == "")
            return;

        $imageName = "";

        $imageTempPath = tempnam(null, null);

        // fix for facebook image return from hybridauth object, so it pass the dimensions validation
        $url = str_replace("width=150&height=150", "width=200&height=200", $url);
        try {
//        echo $url.'<br />';
            file_put_contents($imageTempPath, file_get_contents($url));

            $imageInfo = getimagesize($imageTempPath);

            if (in_array($imageInfo["mime"], array("image/jpeg", "image/pjpeg", "image/png")) && $imageInfo[0] >= 200 && $imageInfo[1] >= 200) {
                try {
                    $mime = explode('/', $imageInfo["mime"]);
                    $extension = substr(array_pop($mime), -4);
                    $imageName = uniqid() . "." . $extension;
//                echo $imageTempPath.'<br />';
                    $rename = rename($imageTempPath, $this->getUploadRootDir() . "/" . $imageName);
                } catch (\Exception $e) {
//                echo "there is a problem";
                }

                if ($imageName != "") {
                    $this->image = $imageName;
                }

                return $imageName;
            }
        } catch (\Exception $e) {
//            echo "there is aproblem with image get content";
            return;
        }
    }

    /**
     * Set lastLoginFrom
     *
     * @param string $lastLoginFrom
     * @return self
     */
    public function setLastLoginFrom($lastLoginFrom)
    {
        $this->lastLoginFrom = $lastLoginFrom;
        return $this;
    }

    /**
     * Get lastLoginFrom
     *
     * @return string $lastLoginFrom
     */
    public function getLastLoginFrom()
    {
        return $this->lastLoginFrom;
    }

    /**
     * Set country
     *
     * @param Ibtikar\GlanceUMSBundle\Document\Country $country
     * @return self
     */
    public function setCountry(\Ibtikar\GlanceUMSBundle\Document\Country $country)
    {
        $this->country = $country;
        return $this;
    }

    /**
     * Get country
     *
     * @return Ibtikar\GlanceUMSBundle\Document\Country $country
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Set city
     *
     * @param Ibtikar\GlanceUMSBundle\Document\City $city
     * @return self
     */
    public function setCity(\Ibtikar\GlanceUMSBundle\Document\City $city)
    {
        $this->city = $city;
        return $this;
    }

    /**
     * Get city
     *
     * @return Ibtikar\GlanceUMSBundle\Document\City $city
     */
    public function getCity()
    {
        return $this->city;
    }
}
