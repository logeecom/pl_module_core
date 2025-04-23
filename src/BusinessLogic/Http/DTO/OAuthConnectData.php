<?php

namespace Packlink\BusinessLogic\Http\DTO;

class OAuthConnectData
{
    /** @var string */
    private $authorizationCode;

    /** @var string */
    private $tenantId;

    /*** @var string */
    private $state;

    public function __construct($authorizationCode, $tenantId, $state)
    {
        $this->authorizationCode = $authorizationCode;
        $this->tenantId = $tenantId;
        $this->state = $state;
    }

    public function getState()
    {
        return $this->state;
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