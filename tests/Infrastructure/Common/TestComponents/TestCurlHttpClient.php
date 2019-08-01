<?php

namespace Logeecom\Tests\Infrastructure\Common\TestComponents;

use Logeecom\Infrastructure\Http\CurlHttpClient;
use Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Logeecom\Infrastructure\Http\HttpResponse;

class TestCurlHttpClient extends CurlHttpClient
{
    const REQUEST_TYPE_SYNCHRONOUS = 1;
    const REQUEST_TYPE_ASYNCHRONOUS = 2;
    public $calledAsync = false;
    public $setAdditionalOptionsCallHistory = array();
    /**
     * @var array
     */
    private $responses;
    /**
     * @var array
     */
    private $history;

    /**
     * Set all mock responses.
     *
     * @param array $responses
     */
    public function setMockResponses($responses)
    {
        $this->responses = $responses;
    }

    /**
     * @inheritdoc
     */
    protected function executeSynchronousRequest()
    {
        $this->history[] = array(
            'type' => self::REQUEST_TYPE_SYNCHRONOUS,
            'method' => isset($this->curlOptions[CURLOPT_CUSTOMREQUEST]) ? $this->curlOptions[CURLOPT_CUSTOMREQUEST]
                : 'POST',
            'url' => $this->curlOptions[CURLOPT_URL],
            'headers' => $this->curlOptions[CURLOPT_HTTPHEADER],
            'body' => isset($this->curlOptions[CURLOPT_POSTFIELDS]) ? $this->curlOptions[CURLOPT_POSTFIELDS] : '',
        );

        if (empty($this->responses)) {
            throw new HttpCommunicationException('No response');
        }

        $response = array_shift($this->responses);
        if ($response instanceof HttpResponse) {
            return $response;
        }

        $apiResponse = $this->strip100Header($response['data']);

        return new HttpResponse(
            $response['status'],
            $this->getHeadersFromCurlResponse($apiResponse),
            $this->getBodyFromCurlResponse($apiResponse)
        );
    }

    /**
     * @inheritdoc
     */
    protected function executeAsynchronousRequest()
    {
        $this->calledAsync = true;

        $this->history[] = array(
            'type' => self::REQUEST_TYPE_ASYNCHRONOUS,
            'method' => isset($this->curlOptions[CURLOPT_CUSTOMREQUEST]) ? $this->curlOptions[CURLOPT_CUSTOMREQUEST]
                : 'POST',
            'url' => $this->curlOptions[CURLOPT_URL],
            'headers' => $this->curlOptions[CURLOPT_HTTPHEADER],
            'body' => isset($this->curlOptions[CURLOPT_POSTFIELDS]) ? $this->curlOptions[CURLOPT_POSTFIELDS] : '',
        );
    }

    /**
     * Gets cURL options set for the request.
     *
     * @return array Curl options.
     */
    public function getCurlOptions()
    {
        return $this->curlOptions;
    }

    /**
     * Return call history.
     *
     * @return array
     */
    public function getHistory()
    {
        return $this->history;
    }

    /**
     * @inheritdoc
     */
    protected function setAdditionalOptions($options)
    {
        parent::setAdditionalOptions($options);
        $this->setAdditionalOptionsCallHistory[] = $options;
    }
}
