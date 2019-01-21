<?php

namespace Logeecom\Tests\Common\TestComponents;

class TestShopConfiguration extends \Packlink\BusinessLogic\Configuration
{
    private $callbackUrl = 'https://some-shop.test/callback?a=1&b=abc';
    private $servicePointEnabled = true;
    /**
     * Singleton instance of this class.
     *
     * @var static
     */
    protected static $instance;

    public function __construct()
    {
        parent::__construct();

        static::$instance = $this;
    }

    /**
     * Returns current system identifier.
     *
     * @return string Current system identifier.
     */
    public function getCurrentSystemId()
    {
        return 'test';
    }

    /**
     * Returns callback url, if it is sub-shop it should return its specific url.
     * Urls must have "token" query parameter, and should have "shop_code" if system supports multiple shops.
     *
     * @return string
     */
    public function getCallbackUrl()
    {
        return $this->callbackUrl;
    }
    /**
     * Returns service point enabled flag
     *
     * @return bool
     */
    public function isServicePointEnabled()
    {
        return $this->servicePointEnabled;
    }

    /**
     * Sets service point enabled flag
     *
     * @param $enabled
     */
    public function setServicePointEnabled($enabled)
    {
        $this->servicePointEnabled = $enabled;
    }

    /**
     * Returns scheduler time threshold between checks
     *
     * @return int
     */
    public function getSchedulerTimeThreshold()
    {
        return 60;
    }

    /**
     * Returns scheduler queue name
     *
     * @return string
     */
    public function getSchedulerQueueName()
    {
        return 'Test Scheduler Queue';
    }

    /**
     * Returns web-hook callback URL for current system.
     *
     * @return string Web-hook callback URL.
     */
    public function getWebHookUrl()
    {
        return 'https://example.com';
    }

    /**
     * Retrieves integration name.
     *
     * @return string Integration name.
     */
    public function getIntegrationName()
    {
        return $this->getConfigValue('integrationName', 'test-system');
    }

    /**
     * Sets integration name.
     *
     * @param string $name Integration name.
     */
    public function setIntegrationName($name)
    {
        $this->saveConfigValue('integrationName', $name);
    }

    /**
     * Returns async process starter url, always in http.
     *
     * @param string $guid Process identifier.
     *
     * @return string Formatted URL of async process starter endpoint.
     */
    public function getAsyncProcessUrl($guid)
    {
        return str_replace('https://', 'http://', $this->callbackUrl . '&guid=' . $guid);
    }
}