<?php

namespace Ibtikar\GlanceUMSBundle\Document\Social;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\EmbeddedDocument
 * @MongoDB\Index(keys={"id"="asc"})
 */
class Facebook {

    /**
     * @MongoDB\String
     */
    private $id;

    /**
     * @MongoDB\String
     */
    private $accessToken;

    /**
     * @MongoDB\String
     */
    private $accessTokenSecret;

    /**
     * Set id
     *
     * @param string $id
     * @return self
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get id
     *
     * @return string $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set accessToken
     *
     * @param string $accessToken
     * @return self
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
        return $this;
    }

    /**
     * Get accessToken
     *
     * @return string $accessToken
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * Set accessTokenSecret
     *
     * @param string $accessTokenSecret
     * @return self
     */
    public function setAccessTokenSecret($accessTokenSecret)
    {
        $this->accessTokenSecret = $accessTokenSecret;
        return $this;
    }

    /**
     * Get accessTokenSecret
     *
     * @return string $accessTokenSecret
     */
    public function getAccessTokenSecret()
    {
        return $this->accessTokenSecret;
    }
}
