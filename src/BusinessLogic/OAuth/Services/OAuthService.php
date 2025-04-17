<?php

namespace Packlink\BusinessLogic\OAuth\Services;

use Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException;
use Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Logeecom\Infrastructure\Http\Exceptions\HttpRequestException;
use Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface;
use Packlink\BusinessLogic\Http\DTO\OAuthUrlData;
use Packlink\BusinessLogic\Http\OAuthConnectData;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\OAuth\Models\OAuthInfo;
use Packlink\BusinessLogic\OAuth\Proxy\Interfaces\OAuthProxyInterface;
use Packlink\BusinessLogic\OAuth\Proxy\OAuthProxy;
use Packlink\BusinessLogic\OAuth\Services\Interfaces\OAuthServiceInterface;

class OAuthService implements OAuthServiceInterface
{
    /** @var OAuthProxy */
    protected $proxy;

    /**
     * @var Proxy
     */
    protected $packlinkProxy;

    /**
     * @var RepositoryInterface
     */
    protected $repository;

    public function __construct(OAuthProxyInterface $proxy, Proxy $packlinkProxy, RepositoryInterface $repository)
    {
        $this->proxy = $proxy;
        $this->packlinkProxy = $packlinkProxy;
        $this->repository = $repository;
    }

    /**
     *  Connects the user by exchanging the authorization code for an access token,retrieves the API key using the access token, and handles token refresh if needed.
     * @param OAuthConnectData $data
     *
     * @return string
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function connect(OAuthConnectData $data)
    {
        $token = $this->getToken($data->getAuthorizationCode());

        $entity = new OAuthInfo();
        $entity->setTenantId($data->getTenantId());
        $entity->setAccessToken($token->getAccessToken());
        $entity->setRefreshToken($token->getRefreshToken());
        $entity->setExpiresIn($token->getExpiresIn());
        $entity->setCreatedAt(time());

        $this->repository->save($entity);

        try {
            $apiKey = $this->getApiKey($token->getAccessToken());
        } catch (HttpAuthenticationException $e) {
            if(!$this->isTokenExpired($entity)) {
                throw $e;
            }

            $refreshedToken = $this->refreshToken($token->getRefreshToken());
            $entity->setAccessToken($refreshedToken->getAccessToken());
            $entity->setRefreshToken($refreshedToken->getRefreshToken());
            $entity->setExpiresIn($refreshedToken->getExpiresIn());
            $entity->setCreatedAt(time());

            $this->repository->update($entity);

            $apiKey = $this->getApiKey($refreshedToken->getAccessToken());
        }

        return $apiKey;
    }

    /**
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function getApiKey($accessToken)
    {
        return $this->packlinkProxy->getApiKeyWithToken($accessToken);
    }

    /**
     * @param $authorizationCode
     *
     * @return \Packlink\BusinessLogic\Http\DTO\OAuthToken
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function getToken($authorizationCode)
    {
        return $this->proxy->getAuthToken($authorizationCode);
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

    /**
     * @param OAuthUrlData $data
     *
     * @return string
     */
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

    private function isTokenExpired(OAuthInfo $tokenEntity)
    {
        return (time() >= ($tokenEntity->getCreatedAt() + $tokenEntity->getExpiresIn()));
    }
}