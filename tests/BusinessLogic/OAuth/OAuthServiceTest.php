<?php

namespace BusinessLogic\OAuth;

use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Packlink\BusinessLogic\Http\DTO\OAuthUrlData;
use Packlink\BusinessLogic\OAuth\OAuthService;
use Packlink\BusinessLogic\OAuth\TenantDomainProvider;

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
        $this->service = new OAuthService();
    }

    public function testBuildRedirectUrl()
    {
        $data = new OAuthUrlData('tenant1', 'client', 'www.example.com', array('write','read'),'ES','tenant1state');

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