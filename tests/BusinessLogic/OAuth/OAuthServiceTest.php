<?php

namespace BusinessLogic\OAuth;

use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TestHttpClient;
use Packlink\BusinessLogic\Http\DTO\OAuthUrlData;
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

    protected function setUp()
    {
        $this->before();
    }

    protected function before()
    {
        parent::before();

        $this->httpClient = new TestHttpClient();

        $mockResponse = new \Logeecom\Infrastructure\Http\HttpResponse(200, array(), json_encode(array(
            'access_token' => 'mockAccessToken',
            'token_type' => 'bearer',
            'expires_in' => 3600,
            'refresh_token' => 'mockRefreshToken',
        )
        ));
        $this->httpClient->setMockResponses(array($mockResponse));

        $oAuthUrlData = new OAuthUrlData('tenant1', 'client', 'www.example.com', array('write','read'),'ES','tenant1state', 'client_secret');
        $proxy = new OAuthProxy($oAuthUrlData, $this->httpClient);

        $this->service = new OAuthService($proxy);
    }

    public function testBuildRedirectUrl()
    {
        $data = new OAuthUrlData('tenant1', 'client', 'www.example.com', array('write','read'),'ES','tenant1state', 'client_secret');

        $expectedParams = http_build_query(array(
            'response_type' => 'code',
            'client_id'     => 'client',
            'redirect_uri'  => 'www.example.com',
            'scope'         => 'write read',
            'state'         => 'tenant1state',
        ), '', '&', PHP_QUERY_RFC3986);

        $expectedUrl = 'https://' . TenantDomainProvider::getDomain('ES') . 'auth/oauth2/authorize?' . $expectedParams;

        $actualUrl = $this->service->buildRedirectUrl($data);

        $this->assertEquals($expectedUrl, $actualUrl);
    }
}