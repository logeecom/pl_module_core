<?php

namespace Logeecom\Tests\Infrastructure\Http;

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

        $this->successCall($responses);
    }

    /**
     * Test an async call.
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

    /**
     * Test parsing plain text response.
     */
    public function testParsingResponseCR()
    {
        // header 100 should be stripped
        // \r is added because HTTP response string from curl has CRLF line separator
        $this->parsingResponse(
            array(
                array(
                    'status' => 200,
                    'data' => "HTTP/1.1 100 Continue\r
\r
HTTP/1.1 200 OK\r
Cache-Control: no-cache\r
Server: test\r
Date: Wed Jul 4 15:32:03 2019\r
Connection: Keep-Alive:\r
Content-Type: application/json\r
Content-Length: 24860\r
X-Custom-Header: Content: database\r
\r
{\"status\":\"success\"}",
                ),
            )
        );
    }

    /**
     * Test parsing plain text response.
     *
     * @param array $responses
     */
    public function parsingResponse($responses)
    {
        $response = $this->successCall($responses);

        $this->assertEquals(200, $response->getStatus());
        $headers = $response->getHeaders();
        $this->assertCount(8, $headers);
        $this->assertEquals('HTTP/1.1 200 OK', $headers[0]);

        $this->assertTrue(array_key_exists('Cache-Control', $headers));
        $this->assertEquals('no-cache', $headers['Cache-Control']);
        $this->assertTrue(array_key_exists('Server', $headers));
        $this->assertEquals('test', $headers['Server']);
        $this->assertTrue(array_key_exists('Date', $headers));
        $this->assertEquals('Wed Jul 4 15:32:03 2019', $headers['Date']);
        $this->assertTrue(array_key_exists('Connection', $headers));
        $this->assertEquals('Keep-Alive:', $headers['Connection']);
        $this->assertTrue(array_key_exists('Content-Type', $headers));
        $this->assertEquals('application/json', $headers['Content-Type']);
        $this->assertTrue(array_key_exists('Content-Length', $headers));
        $this->assertEquals('24860', $headers['Content-Length']);
        $this->assertTrue(array_key_exists('X-Custom-Header', $headers));
        $this->assertEquals('Content: database', $headers['X-Custom-Header']);

        $body = json_decode($response->getBody(), true);
        $this->assertCount(1, $body);
        $this->assertTrue(array_key_exists('status', $body));
        $this->assertEquals('success', $body['status']);
    }

    private function successCall($responses)
    {
        $this->httpClient->setMockResponses($responses);
        $success = $this->httpClient->request('POST', 'test.url.com');

        $this->assertTrue($success->isSuccessful(), 'Sync call should pass.');
        $this->assertCount(1, $this->httpClient->getHistory());
        $this->assertNotEmpty($this->httpClient->getCurlOptions(), 'Curl options should be set.');

        return $success;
    }
}
