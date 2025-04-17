<?php

namespace Packlink\BusinessLogic\OAuth\Services;

use Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException;
use Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Logeecom\Infrastructure\Http\Exceptions\HttpRequestException;
use Packlink\BusinessLogic\Http\DTO\OAuthUrlData;
use Packlink\BusinessLogic\OAuth\Proxy\OAuthProxy;
use Packlink\BusinessLogic\OAuth\Services\Interfaces\OAuthServiceInterface;

class OAuthService implements OAuthServiceInterface
{
    /** @var OAuthProxy */
    protected $proxy;

    public function __construct(OAuthProxy $proxy)
    {
        $this->proxy = $proxy;
    }

    public function connect($accessToken)
    {
        // TODO: Implement connect() method.
    }

    public function getApiKey()
    {
        // TODO: Implement getApiKey() method.
    }

    public function getToken($accessToken)
    {
        return $this->proxy->getAuthToken($accessToken);
    }

    /**
     * @throws HttpCommunicationException
     * @throws HttpAuthenticationException
     * @throws HttpRequestException
     */
    public function refreshToken($refreshToken)
    {
        return $this->proxy->refreshAuthToken($refreshToken);
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