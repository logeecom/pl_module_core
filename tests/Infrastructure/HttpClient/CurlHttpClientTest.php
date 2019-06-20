<?php

namespace Logeecom\Tests\Infrastructure\HttpClient;

use Logeecom\Infrastructure\Http\CurlHttpClient;
use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\Http\HttpResponse;
use Logeecom\Tests\Infrastructure\Common\BaseInfrastructureTestWithServices;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TestCurlHttpClient;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;

class CurlHttpClientTest extends BaseInfrastructureTestWithServices
{
    /**
     * @var TestCurlHttpClient
     */
    protected $httpClient;

    protected function setUp()
    {
        parent::setUp();

        $this->httpClient = new TestCurlHttpClient();
        $me = $this;
        TestServiceRegister::registerService(
            HttpClient::CLASS_NAME,
            function () use ($me) {
                return $me->httpClient;
            }
        );
    }

    /**
     * Test a sync call.
     */
    public function testSyncCall()
    {
        $responses = array(
            new HttpResponse(200, array(), '{}'),
        );
        $this->httpClient->setMockResponses($responses);

        $success = $this->httpClient->request('POST', 'test.url.com');

        $this->assertTrue($success->isSuccessful(), 'Sync call should pass.');
        $this->assertCount(1, $this->httpClient->getHistory());
        $this->assertNotEmpty($this->httpClient->getCurlOptions(), 'Curl options should be set.');
    }

    /**
     * Test a sync call.
     */
    public function testAsyncCall()
    {
        $responses = array(
            new HttpResponse(200, array(), '{}'),
        );
        $this->httpClient->setMockResponses($responses);

        $this->httpClient->requestAsync('POST', 'test.url.com');

        $this->assertTrue($this->httpClient->calledAsync, 'Async call should pass.');
        $this->assertCount(1, $this->httpClient->getHistory());
        $curlOptions = $this->httpClient->getCurlOptions();
        $this->assertNotEmpty($curlOptions, 'Curl options should be set.');
        $this->assertTrue(isset($curlOptions[CURLOPT_TIMEOUT_MS]), 'Curl timeout should be set for async call.');
        $this->assertEquals(
            CurlHttpClient::DEFAULT_ASYNC_REQUEST_TIMEOUT,
            $curlOptions[CURLOPT_TIMEOUT_MS],
            'Curl default timeout should be set for async call.'
        );
    }
}
