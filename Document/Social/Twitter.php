<?php

namespace Ibtikar\GlanceUMSBundle\Document\Social;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Bundle\MongoDBBundle\Validator\Constraints\Unique as MongoDBUnique;

/**
 * @MongoDB\EmbeddedDocument
 * @MongoDB\Index(keys={"id"="asc"})
 */
class Twitter {

    /**
     * @Assert\NotBlank
     * @MongoDB\String
     */
    private $id;

    /**
     * @Assert\NotBlank
     * @MongoDB\String
     */
    private $accessToken;
    /**
     * @Assert\NotBlank
     * @MongoDB\String
     */
    private $accessTokenSecret;


    public function __toString() {
        return $this->id;
    }

    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        $this->id= $id;
        return $this;
    }

    public function getAccessToken() {
        return $this->accessToken;
    }

    public function setAccessToken($accessToken) {
        $this->accessToken = $accessToken;
        return $this;
    }

    public function getAccessTokenSecret() {
        return $this->accessTokenSecret;
    }

    public function setAccessTokenSecret($accessTokenSecret) {
        $this->accessTokenSecret = $accessTokenSecret;
        return $this;
    }

}