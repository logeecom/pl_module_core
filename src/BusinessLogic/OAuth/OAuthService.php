<?php

namespace Packlink\BusinessLogic\OAuth;

use Packlink\BusinessLogic\Http\DTO\OAuthUrlData;
use Packlink\BusinessLogic\OAuth\Interfaces\OAuthServiceInterface;

class OAuthService implements OAuthServiceInterface
{
    public function __construct()
    {
    }

    public function connect($accessToken)
    {
        // TODO: Implement connect() method.
    }

    public function getApiKey()
    {
        // TODO: Implement getApiKey() method.
    }

    public function getToken()
    {
        // TODO: Implement getToken() method.
    }

    public function refreshToken($refreshToken)
    {
        // TODO: Implement refreshToken() method.
    }

    public function buildRedirectUrl(OAuthUrlData $data)
    {
        $queryParams = array(
            'response_type' => 'code',
            'client_id'     => $data->getClientId(),
            'redirect_uri'  => $data->getRedirectUri(),
            'scope'         => implode(' ', $data->getScopes()),
            'state'         => $data->getState(),
        );

        $queryString = http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);

        $domain = TenantDomainProvider::getDomain($data->getDomain());

        return 'https://' . rtrim($domain, '/') . 'auth/oauth2/authorize?' . $queryString;
    }
}