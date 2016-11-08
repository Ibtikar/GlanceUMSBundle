<?php

namespace Ibtikar\GlanceUMSBundle\Document;

use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Doctrine\Bundle\MongoDBBundle\Validator\Constraints\Unique as MongoDBUnique;
use Ibtikar\GlanceDashboardBundle\Document\Document;

/**
 * @MongoDB\Document(repositoryClass="Ibtikar\GlanceUMSBundle\Document\CountryRepository")
 * @MongoDBUnique(fields={"countryName"})
 * @MongoDBUnique(fields={"countryCode"})
 * @MongoDB\HasLifecycleCallbacks
 * @MongoDB\Index(keys={"countryName"="asc"}),
 */
class Country extends Document {

    /**
     * @MongoDB\Id
     */
    private $id;

    /**
     * @Assert\NotBlank
     * @MongoDB\String
     */
    private $countryName;

    /**
     * @Assert\NotBlank
     * @MongoDB\String
     */
    private $countryCode;

    /**
     * @MongoDB\Increment
     */
    private $countryUsageCount = 0;

    /**
     * @MongoDB\Int
     */
    private $specialCountrySort = 0;

    public function __toString() {
        return (string) $this->countryName;
    }

//    /**
//     * @MongoDB\PrePersist()
//     * @MongoDB\PreUpdate()
//     */
//    public function setCurrentCountryName() {
//        $this->setCountryName(\Locale::getDisplayRegion('ar_' . $this->countryCode, 'ar'));
//    }

    /**
     * Get id
     *
     * @return id $id
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set countryCode
     *
     * @param string $countryCode
     * @return self
     */
    public function setCountryCode($countryCode) {
        $this->countryCode = $countryCode;
        return $this;
    }

    /**
     * Get countryCode
     *
     * @return string $countryCode
     */
    public function getCountryCode() {
        return $this->countryCode;
    }

    /**
     * Set countryName
     *
     * @param string $countryName
     * @return self
     */
    public function setCountryName($countryName) {
        $this->countryName = $countryName;
        return $this;
    }

    /**
     * Get countryName
     *
     * @return string $countryName
     */
    public function getCountryName() {
        return $this->countryName;
    }

    /**
     * Set countryUsageCount
     *
     * @param int $countryUsageCount
     * @return self
     */
    public function setCountryUsageCount($countryUsageCount) {
        $this->countryUsageCount = $countryUsageCount;
        return $this;
    }

    /**
     * Get countryUsageCount
     *
     * @return int $countryUsageCount
     */
    public function getCountryUsageCount() {
        return $this->countryUsageCount;
    }

    /**
     * Set specialCountrySort
     *
     * @param int $specialCountrySort
     * @return self
     */
    public function setSpecialCountrySort($specialCountrySort) {
        $this->specialCountrySort = $specialCountrySort;
        return $this;
    }

    /**
     * Get specialCountrySort
     *
     * @return int $specialCountrySort
     */
    public function getSpecialCountrySort() {
        return $this->specialCountrySort;
    }
}
