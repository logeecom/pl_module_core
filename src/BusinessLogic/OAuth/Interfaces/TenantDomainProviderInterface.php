<?php

namespace Packlink\BusinessLogic\OAuth\Interfaces;

interface TenantDomainProviderInterface
{
    public static function getDomain($tenantCode);
}