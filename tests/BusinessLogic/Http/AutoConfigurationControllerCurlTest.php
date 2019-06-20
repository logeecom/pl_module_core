<?php

namespace Logeecom\Tests\BusinessLogic\Http;

use Logeecom\Infrastructure\Http\Configuration\AutoConfigurationController;
use Logeecom\Infrastructure\Http\CurlHttpClient;
use Logeecom\Infrastructure\Http\DTO\OptionsDTO;
use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\Http\HttpResponse;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TestCurlHttpClient;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;

class AutoConfigurationControllerCurlTest extends BaseTestWithServices
{
    /**
     * @var \Logeecom\Tests\Infrastructure\Common\TestComponents\TestCurlHttpClient
     */
    protected $httpClient;

    /**
     * @throws \Exception
     */
    public function setUp()
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

        $this->shopConfig->setAutoConfigurationUrl('http://example.com');
    }

    /**
     * Test auto-configure to throw exception if auto-configure URL is not set.
     *
     * @expectedException \Logeecom\Infrastructure\Exceptions\BaseException
     */
    public function testAutoConfigureNoUrlSet()
    {
        $this->shopConfig->setAutoConfigurationUrl(null);
        $controller = new AutoConfigurationController($this->shopConfig, $this->httpClient);
        $controller->start();
    }

    /**
     * Test auto-configure to be successful with default options
     */
    public function testAutoConfigureSuccessfullyWithDefaultOptions()
    {
        $response = new HttpResponse(200, array(), '{}');
        $this->httpClient->setMockResponses(array($response));

        $controller = new AutoConfigurationController($this->shopConfig, $this->httpClient);
        $success = $controller->start();

        $this->assertTrue($success, 'Auto-configure must be successful if default configuration request passed.');
        $this->assertCount(
            0,
            $this->httpClient->setAdditionalOptionsCallHistory,
            'Set additional options should not be called'
        );
        $this->assertEmpty($this->shopConfig->getHttpConfigurationOptions(), 'Additional options should remain empty');
        $this->assertEquals(AutoConfigurationController::STATE_SUCCEEDED, $controller->getState());
    }

    /**
     * Test auto-configure to be successful with some combination options set
     */
    public function testAutoConfigureSuccessWithSomeCombination()
    {
        $responses = array(
            new HttpResponse(400, array(), '{}'),
            new HttpResponse(200, array(), '{}'),
        );
        $this->httpClient->setMockResponses($responses);
        $additionalOptionsCombination = array(new OptionsDTO(CurlHttpClient::SWITCH_PROTOCOL, true));

        $controller = new AutoConfigurationController($this->shopConfig, $this->httpClient);
        $success = $controller->start();

        $this->assertTrue($success, 'Auto-configure must be successful if request passed with some combination.');
        $this->assertCount(
            1,
            $this->httpClient->setAdditionalOptionsCallHistory,
            'Set additional options should be called once'
        );
        $this->assertEquals(
            $additionalOptionsCombination,
            $this->shopConfig->getHttpConfigurationOptions(),
            'Additional options should be set to first combination'
        );
        $setOptions = $this->httpClient->getCurlOptions();
        $this->assertEquals('https://example.com', $setOptions[CURLOPT_URL], 'Protocol for URL should be updated.');
    }

    /**
     * Test auto-configure to be successful with some combination options set
     */
    public function testAutoConfigureSuccessWithAllCombination()
    {
        $responses = array(
            new HttpResponse(400, array(), '{}'),
            new HttpResponse(400, array(), '{}'),
            new HttpResponse(400, array(), '{}'),
            new HttpResponse(400, array(), '{}'),
            new HttpResponse(400, array(), '{}'),
            new HttpResponse(400, array(), '{}'),
            new HttpResponse(400, array(), '{}'),
            new HttpResponse(200, array(), '{}'),
        );
        $this->httpClient->setMockResponses($responses);
        $additionalOptionsCombination = array(
            new OptionsDTO(CurlHttpClient::SWITCH_PROTOCOL, true),
            new OptionsDTO(CURLOPT_FOLLOWLOCATION, false),
            new OptionsDTO(CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V6),
        );

        $controller = new AutoConfigurationController($this->shopConfig, $this->httpClient);
        $success = $controller->start();

        $this->assertTrue($success, 'Auto-configure must be successful if request passed with some combination.');
        $this->assertCount(
            7,
            $this->httpClient->setAdditionalOptionsCallHistory,
            'Set additional options should be called seven times'
        );
        $this->assertCount(8, $this->httpClient->getHistory(), 'There should be seven calls');
        $this->assertEquals(
            $additionalOptionsCombination,
            $this->shopConfig->getHttpConfigurationOptions(),
            'Additional options should be set to first combination'
        );
        $setOptions = $this->httpClient->getCurlOptions();
        $this->assertEquals('https://example.com', $setOptions[CURLOPT_URL], 'Protocol for URL should be updated.');
    }

    /**
     * Test auto-configure to be successful with some combination options set
     */
    public function testAutoConfigureFailed()
    {
        $responses = array(
            new HttpResponse(400, array(), '{}'),
            new HttpResponse(400, array(), '{}'),
            new HttpResponse(400, array(), '{}'),
            new HttpResponse(400, array(), '{}'),
            new HttpResponse(400, array(), '{}'),
            new HttpResponse(400, array(), '{}'),
            new HttpResponse(400, array(), '{}'),
        );
        $this->httpClient->setMockResponses($responses);

        $controller = new AutoConfigurationController($this->shopConfig, $this->httpClient);
        $success = $controller->start();

        $this->assertFalse($success, 'Auto-configure must failed if no combination resulted with request passed.');
        $this->assertCount(
            7,
            $this->httpClient->setAdditionalOptionsCallHistory,
            'Set additional options should be called twice'
        );
        $this->assertEmpty(
            $this->shopConfig->getHttpConfigurationOptions(),
            'Reset additional options method should be called and additional options should be empty.'
        );
    }

    /**
     * Test auto-configure to be successful with some combination options set
     */
    public function testAutoConfigureFailedWhenThereAreNoResponses()
    {
        $controller = new AutoConfigurationController($this->shopConfig, $this->httpClient);
        $success = $controller->start();

        $this->assertFalse($success, 'Auto-configure must failed if no combination resulted with request passed.');
        $this->assertCount(
            7,
            $this->httpClient->setAdditionalOptionsCallHistory,
            'Set additional options should be called twice'
        );
        $this->assertEmpty(
            $this->shopConfig->getHttpConfigurationOptions(),
            'Reset additional options method should be called and additional options should be empty.'
        );
    }
}
