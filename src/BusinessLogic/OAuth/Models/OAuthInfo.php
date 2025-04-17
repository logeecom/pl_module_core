<?php

namespace Packlink\BusinessLogic\OAuth\Models;

use Logeecom\Infrastructure\ORM\Configuration\EntityConfiguration;
use Logeecom\Infrastructure\ORM\Configuration\IndexMap;
use Logeecom\Infrastructure\ORM\Entity;

class OAuthInfo extends Entity
{
    const CLASS_NAME = __CLASS__;

    /**
     * @var array
     */
    protected $fields = array(
        'id',
        'tenantId',
        'encryptedAccessToken',
        'encryptedRefreshToken',
        'expiresIn',
        'createdAt'
    );

    /** @var string */
    protected $id;

    /** @var string */
    protected $tenantId;

    /** @var string */
    protected $encryptedAccessToken;

    /** @var string */
    protected $encryptedRefreshToken;

    /** @var int */
    protected $expiresIn;

    /** @var int */
    protected $createdAt;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getTenantId()
    {
        return $this->tenantId;
    }

    public function setTenantId($tenantId)
    {
        $this->tenantId = $tenantId;
    }

    public function getEncryptedAccessToken()
    {
        return $this->encryptedAccessToken;
    }

    public function setEncryptedAccessToken($token)
    {
        $this->encryptedAccessToken = $token;
    }

    public function getEncryptedRefreshToken()
    {
        return $this->encryptedRefreshToken;
    }

    public function setEncryptedRefreshToken($token)
    {
        $this->encryptedRefreshToken = $token;
    }

    public function getExpiresIn()
    {
        return $this->expiresIn;
    }

    public function setExpiresIn($expiresIn)
    {
        $this->expiresIn = $expiresIn;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setCreatedAt($timestamp)
    {
        $this->createdAt = $timestamp;
    }

    /**
     * @return EntityConfiguration
     */
    public function getConfig()
    {
        $indexMap = new IndexMap();
        $indexMap->addStringIndex('tenantId');

        return new EntityConfiguration($indexMap, 'OAuthInfo');
    }
}
