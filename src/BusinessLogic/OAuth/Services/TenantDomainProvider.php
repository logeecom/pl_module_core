<?php

namespace Packlink\BusinessLogic\OAuth\Services;

use Packlink\BusinessLogic\OAuth\Services;

class TenantDomainProvider implements Services\Interfaces\TenantDomainProviderInterface
{
    /**
     * @var array
     */
    private static $TENANT_DOMAINS = array(
        'ES' => 'pro.packlink.es',
        'FR' => 'pro.packlink.fr',
        'DE' => 'pro.packlink.de',
        'IT' => 'pro.packlink.it',
    );

    /**
     * @var string[]
     */
    private static $ALLOWED_COUNTRIES = array('ES', 'FR', 'DE', 'IT');

    /**
     * @var string
     */
    private static $DEFAULT_DOMAIN = 'pro.packlink.fr';

    /**
     * @param string $tenantCode
     *
     * @return string
     */
    public static function getDomain($tenantCode)
    {
        if (isset(self::$TENANT_DOMAINS[$tenantCode])) {
            return self::$TENANT_DOMAINS[$tenantCode];
        }

        return self::$DEFAULT_DOMAIN;
    }

    /**
     * Returns all allowed country codes.
     *
     * @return array
     */
    public static function getAllowedCountries()
    {
        return self::$ALLOWED_COUNTRIES;
    }
}