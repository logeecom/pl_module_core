<?php

namespace BusinessLogic\OAuth;

use Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException;
use Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Logeecom\Infrastructure\Http\Exceptions\HttpRequestException;
use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\ORM\Exceptions\EntityClassException;
use Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TestHttpClient;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Http\DTO\OAuthUrlData;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\OAuth\Models\OAuthInfo;
use Packlink\BusinessLogic\OAuth\Models\OAuthState;
use Packlink\BusinessLogic\OAuth\Proxy\OAuthProxy;
use Packlink\BusinessLogic\OAuth\Services\OAuthService;
use Packlink\BusinessLogic\OAuth\Services\TenantDomainProvider;

class OAuthServiceTest extends BaseTestWithServices
{
    /**
     * OAuth service instance.
     *
     * @var OAuthService
     */
    public $service;

    /**
     * @var MemoryRepository
     */
    public $repository;

    /**
     * @var HttpClient
     */
    public $httpClientOauth;

    protected function setUp()
    {
        $this->before();
    }

    protected function before()
    {
        parent::before();

        $this->httpClientOauth = new TestHttpClient();

        $mockResponse = new \Logeecom\Infrastructure\Http\HttpResponse(200, array(), json_encode(array(
                'access_token' => 'mockAccessToken',
                'token_type' => 'bearer',
                'expires_in' => 3600,
                'refresh_token' => 'mockRefreshToken',
            )
        ));
        $this->httpClientOauth->setMockResponses(array($mockResponse));

        $oAuthUrlData = new OAuthUrlData(
            'tenant1',
            'client',
            'www.example.com',
            array('write', 'read'),
            'ES',
            'tenant1state',
            'client_secret'
        );
        $authProxy = new OAuthProxy($oAuthUrlData, $this->httpClientOauth);

        RepositoryRegistry::registerRepository(OAuthInfo::CLASS_NAME, MemoryRepository::getClassName());

        $this->repository = RepositoryRegistry::getRepository(OAuthInfo::CLASS_NAME);

        /**@var Proxy $proxy */
        $proxy = TestServiceRegister::getService(Proxy::CLASS_NAME);

        $this->service = new OAuthService($authProxy, $proxy, $this->repository);
    }

    public function testBuildRedirectUrl()
    {
        $data = new OAuthUrlData(
            'tenant1',
            'client',
            'www.example.com',
            array('write', 'read'),
            'ES',
            'tenant1state',
            'client_secret'
        );

        $expectedParams = http_build_query(array(
            'response_type' => 'code',
            'client_id' => 'client',
            'redirect_uri' => 'www.example.com',
            'scope' => 'write read',
            'state' => 'tenant1state',
        ), '', '&', PHP_QUERY_RFC3986);

        $expectedUrl = 'https://' . TenantDomainProvider::getDomain('ES') . 'auth/oauth2/authorize?' . $expectedParams;

        $actualUrl = $this->service->buildRedirectUrl($data);

        $this->assertEquals($expectedUrl, $actualUrl);
    }

    /**
     * @throws HttpCommunicationException
     * @throws HttpAuthenticationException
     * @throws HttpRequestException
     * @throws QueryFilterInvalidParamException
     * @throws EntityClassException
     */
    public function testConnectStoresTokenAndReturnsApiKey()
    {
        $connectData = new \Packlink\BusinessLogic\Http\OAuthConnectData('code123', 'tenant1');

        $mockResponse = new \Logeecom\Infrastructure\Http\HttpResponse(200, array(), json_encode(array(
                'token' => 'apiKey',
            )
        ));

        $this->httpClient->setMockResponses(array($mockResponse));

        $apiKey = $this->service->connect($connectData);

        $this->assertEquals('apiKey', $apiKey);

        $entities = $this->repository->select();
        $this->assertCount(1, $entities);
    }

    public function testConnectRefreshesTokenOnAuthFailure()
    {
        $connectData = new \Packlink\BusinessLogic\Http\OAuthConnectData('codeXYZ', 'tenant1');

        $invalidResponse = new \Logeecom\Infrastructure\Http\HttpResponse(201, array(), json_encode(array(
            'unexpected_field' => 'notToken',
        )));

        $validResponse = new \Logeecom\Infrastructure\Http\HttpResponse(200, array(), json_encode(array(
                'token' => 'apiKeyAfterRefresh',
            )
        ));

        $this->httpClient->setMockResponses(array($invalidResponse, $validResponse));

        $mockResponse1 = new \Logeecom\Infrastructure\Http\HttpResponse(200, array(), json_encode(array(
                'access_token' => 'mockAccessToken',
                'token_type' => 'bearer',
                'expires_in' => 0,
                'refresh_token' => 'mockRefreshToken',
            )
        ));

        $mockResponse2 = new \Logeecom\Infrastructure\Http\HttpResponse(200, array(), json_encode(array(
                'access_token' => 'newAccessToken',
                'token_type' => 'bearer',
                'expires_in' => 3600,
                'refresh_token' => 'newRefreshToken',
            )
        ));
        $this->httpClientOauth->setMockResponses(array($mockResponse1,$mockResponse2));

        $apiKey = $this->service->connect($connectData);

        $this->assertEquals('apiKeyAfterRefresh', $apiKey);

        $entities = $this->repository->select();
        $this->assertCount(1, $entities);

        $entity = $entities[0];
        $this->assertEquals('tenant1', $entity->getTenantId());
        $this->assertEquals('newAccessToken', $entity->getAccessToken());
        $this->assertEquals('newRefreshToken', $entity->getRefreshToken());
    }

    public function testConnectOnAuthFailureTokenNotExpired()
    {
        $connectData = new \Packlink\BusinessLogic\Http\OAuthConnectData('codeXYZ', 'tenant1');

        $invalidResponse = new \Logeecom\Infrastructure\Http\HttpResponse(201, array(), json_encode(array(
            'unexpected_field' => 'notToken',
        )));

        $validResponse = new \Logeecom\Infrastructure\Http\HttpResponse(200, array(), json_encode(array(
                'token' => 'apiKeyAfterRefresh',
            )
        ));

        $this->httpClient->setMockResponses(array($invalidResponse, $validResponse));

        $mockResponse1 = new \Logeecom\Infrastructure\Http\HttpResponse(200, array(), json_encode(array(
                'access_token' => 'mockAccessToken',
                'token_type' => 'bearer',
                'expires_in' => 3600,
                'refresh_token' => 'mockRefreshToken',
            )
        ));

        $mockResponse2 = new \Logeecom\Infrastructure\Http\HttpResponse(200, array(), json_encode(array(
                'access_token' => 'newAccessToken',
                'token_type' => 'bearer',
                'expires_in' => 3600,
                'refresh_token' => 'newRefreshToken',
            )
        ));
        $this->httpClientOauth->setMockResponses(array($mockResponse1,$mockResponse2));

        try {
            $this->service->connect($connectData);
            $this->fail('Expected HttpAuthenticationException was not thrown.');
        } catch (HttpAuthenticationException $e) {
            $this->assertEquals('Could not retrieve API key.', $e->getMessage());
        }
    }
}