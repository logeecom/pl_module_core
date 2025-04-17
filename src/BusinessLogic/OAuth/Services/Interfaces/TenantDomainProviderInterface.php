<?php

namespace Packlink\BusinessLogic\OAuth\Services\Interfaces;

interface TenantDomainProviderInterface
{
    public static function getDomain($tenantCode);
}