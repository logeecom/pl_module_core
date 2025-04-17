<?php

namespace Packlink\BusinessLogic\Http;

class OAuthConnectData
{
    /** @var string */
    private $authorizationCode;

    /** @var string */
    private $tenantId;

    public function __construct($authorizationCode, $tenantId)
    {
        $this->authorizationCode = $authorizationCode;
        $this->tenantId = $tenantId;
    }

    /**
     * @return string
     */
    public function getAuthorizationCode()
    {
        return $this->authorizationCode;
    }

    /**
     * @return string
     */
    public function getTenantId()
    {
        return $this->tenantId;
    }
}