<?php

namespace Packlink\BusinessLogic\Http;

use Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException;
use Logeecom\Infrastructure\Http\Exceptions\HttpRequestException;
use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\Http\HttpResponse;
use Logeecom\Infrastructure\Logger\Logger;
use Packlink\BusinessLogic\Http\DTO\Draft;
use Packlink\BusinessLogic\Http\DTO\DropOff;
use Packlink\BusinessLogic\Http\DTO\LocationInfo;
use Packlink\BusinessLogic\Http\DTO\ParcelInfo;
use Packlink\BusinessLogic\Http\DTO\PostalCode;
use Packlink\BusinessLogic\Http\DTO\Shipment;
use Packlink\BusinessLogic\Http\DTO\ShippingService;
use Packlink\BusinessLogic\Http\DTO\ShippingServiceDetails;
use Packlink\BusinessLogic\Http\DTO\ShippingServiceSearch;
use Packlink\BusinessLogic\Http\DTO\Tracking;
use Packlink\BusinessLogic\Http\DTO\User;
use Packlink\BusinessLogic\Http\DTO\Warehouse;

/**
 * Class Proxy. In charge for communication with Packlink API.
 *
 * @package Packlink\BusinessLogic
 */
class Proxy
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Packlink base API URL.
     */
    const BASE_URL = 'https://api.packlink.com/';
    /**
     * Packlink API version
     */
    const API_VERSION = 'v1/';
    /**
     * Unauthorized HTTP status code.
     */
    const HTTP_STATUS_CODE_UNAUTHORIZED = 401;
    /**
     * HTTP GET method
     */
    const HTTP_METHOD_GET = 'GET';
    /**
     * HTTP POST method
     */
    const HTTP_METHOD_POST = 'POST';
    /**
     * HTTP PUT method
     */
    const HTTP_METHOD_PUT = 'PUT';
    /**
     * HTTP DELETE method
     */
    const HTTP_METHOD_DELETE = 'DELETE';
    /**
     * HTTP Client.
     *
     * @var HttpClient
     */
    private $client;
    /**
     * Authorization token.
     *
     * @var string
     */
    private $token;

    /**
     * Proxy constructor.
     *
     * @param string $token Authorization token.
     * @param HttpClient $client System HTTP client.
     */
    public function __construct($token, HttpClient $client)
    {
        $this->token = $token;
        $this->client = $client;
    }

    /**
     * Returns a list of user parcels information.
     *
     * @return ParcelInfo[] Array of parcels information.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function getUsersParcelInfo()
    {
        $response = $this->call(self::HTTP_METHOD_GET, 'users/parcels');
        $data = $response->decodeBodyAsJson();

        return ParcelInfo::fromArrayBatch($data ?: array());
    }

    /**
     * Returns a list of user warehouses.
     *
     * @return Warehouse[] Array of warehouses.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function getUsersWarehouses()
    {
        $response = $this->call(self::HTTP_METHOD_GET, 'clients/warehouses');
        $data = $response->decodeBodyAsJson();

        return Warehouse::fromArrayBatch($data ?: array());
    }

    /**
     * Returns user info.
     *
     * @return User User info.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function getUserData()
    {
        $response = $this->call(self::HTTP_METHOD_GET, 'clients');

        return User::fromArray($response->decodeBodyAsJson());
    }

    /**
     * Subscribes web-hook callback url.
     *
     * @param string $webHookUrl Web-hook URL.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function registerWebHookHandler($webHookUrl)
    {
        $this->call(self::HTTP_METHOD_POST, 'shipments/callback', array('url' => $webHookUrl));
    }

    /**
     * Returns top ten drop-off locations in postal code area.
     *
     * @param string $serviceId Unique shipping service identifier.
     * @param string $countryCode Country ISO2 code.
     * @param string $postalCode Postal/ZIP code.
     *
     * @return DropOff[] List of all drop off locations near given postal code.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function getLocations($serviceId, $countryCode, $postalCode)
    {
        $response = $this->call(self::HTTP_METHOD_GET, "dropoffs/$serviceId/$countryCode/$postalCode");

        return DropOff::fromArrayBatch($response->decodeBodyAsJson());
    }

    /**
     * Performs search for locations.
     *
     * @param string $platformCountry Country code to search in.
     * @param string $postalZone Postal zone to search in.
     * @param string $query Query to search for.
     *
     * @return \Packlink\BusinessLogic\Http\DTO\LocationInfo[]
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function searchLocations($platformCountry, $postalZone, $query)
    {
        $url = 'locations/postalcodes?' . http_build_query(array(
                'platform' => 'PRO',
                'platform_country' => $platformCountry,
                'postalzone' => $postalZone,
                'q' => $query
            ));

        $response = $this->call(self::HTTP_METHOD_GET, $url);

        return LocationInfo::fromArrayBatch($response->decodeBodyAsJson());
    }

    /**
     * Returns array of PostalCode objects by specified country and specified zip code.
     *
     * @param string $countryCode Two-letter iso code of a country.
     * @param string $zipCode Zip code.
     *
     * @return PostalCode[] PostalCode DTO.
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function getPostalCodes($countryCode, $zipCode)
    {
        $response = $this->call(self::HTTP_METHOD_GET, "locations/postalcodes/$countryCode/$zipCode");

        return PostalCode::fromArrayBatch($response->decodeBodyAsJson());
    }

    /**
     * Gets available shipping services delivery details for given search data.
     *
     * @param ShippingServiceSearch $params Search parameters.
     *
     * @return ShippingServiceDetails[] Found services with details.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function getShippingServicesDeliveryDetails(ShippingServiceSearch $params)
    {
        if (!$params->isValid()) {
            Logger::logDebug('Missing required search parameter(s).', 'Core', $params->toArray());
            throw new HttpRequestException('Missing required search parameter(s).', 400);
        }

        $response = $this->call(self::HTTP_METHOD_GET, 'services?' . http_build_query($params->toArray()));

        $body = $response->decodeBodyAsJson();
        if (empty($body)) {
            return array();
        }

        $shippingDetails = ShippingServiceDetails::fromArrayBatch($body);

        foreach ($shippingDetails as $shippingDetail) {
            $shippingDetail->departureCountry = $params->fromCountry;
            $shippingDetail->destinationCountry = $params->toCountry;
            $shippingDetail->national = $params->toCountry === $params->fromCountry;
        }

        return $shippingDetails;
    }

    /**
     * Gets details about the service.
     *
     * @param int $id Service Id.
     *
     * @return ShippingService Shipping service.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function getShippingServiceDetails($id)
    {
        $response = $this->call(self::HTTP_METHOD_GET, "services/available/$id/details");

        return ShippingService::fromArray($response->decodeBodyAsJson());
    }

    /**
     * Sends shipment draft to Packlink.
     *
     * @param Draft $draft Shipment draft.
     *
     * @return string Shipment reference for uploaded draft.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function sendDraft(Draft $draft)
    {
        $response = $this->call(self::HTTP_METHOD_POST, 'shipments', $draft->toArray());

        $result = $response->decodeBodyAsJson();

        return array_key_exists('reference', $result) ? $result['reference'] : '';
    }

    /**
     * Returns shipment by its reference identifier.
     *
     * @param string $referenceId Packlink shipment reference identifier.
     *
     * @return Shipment|null Shipment DTO if it exists for given reference number; otherwise, null.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function getShipment($referenceId)
    {
        $response = $this->getShipmentData($referenceId);

        return $response !== null ? Shipment::fromArray($response->decodeBodyAsJson()) : null;
    }

    /**
     * Returns list of shipment labels for shipment with provided reference.
     *
     * @param string $referenceId Packlink shipment reference identifier.
     *
     * @return string[] Array of shipment labels.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function getLabels($referenceId)
    {
        $response = $this->getShipmentData($referenceId, 'labels');

        return $response !== null ? $response->decodeBodyAsJson() : array();
    }

    /**
     * Returns tracking information by its reference identifier.
     *
     * @param string $referenceId Packlink shipment reference identifier.
     *
     * @return Tracking[] Tracking DTO.
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function getTrackingInfo($referenceId)
    {
        $response = $this->getShipmentData($referenceId, 'track');

        return $response !== null ? Tracking::fromArrayBatch($response->decodeBodyAsJson()) : array();
    }

    /**
     * Calls shipments endpoint and handles response. Any shipment endpoint can return 404 so this call handles that.
     *
     * @param string $reference Shipment reference number.
     * @param string $endpoint Endpoint to call.
     *
     * @return \Logeecom\Infrastructure\Http\HttpResponse|null Response if API returned it; NULL if 404.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    protected function getShipmentData($reference, $endpoint = '')
    {
        if ($endpoint) {
            $endpoint = '/' . $endpoint;
        }

        try {
            $response = $this->call(self::HTTP_METHOD_GET, "shipments/{$reference}{$endpoint}");
        } catch (HttpRequestException $e) {
            if ($e->getCode() === 404) {
                return null;
            }

            throw $e;
        }

        return $response;
    }

    /**
     * Makes a HTTP call and returns response.
     *
     * @param string $method HTTP method (GET, POST, PUT, etc.).
     * @param string $endpoint Endpoint resource on remote API.
     * @param array $body Request payload body.
     *
     * @return HttpResponse Response from request.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    protected function call($method, $endpoint, array $body = array())
    {
        $bodyStringToSend = '';
        if (in_array(strtoupper($method), array(self::HTTP_METHOD_POST, self::HTTP_METHOD_PUT), true)) {
            $bodyStringToSend = json_encode($body);
        }

        $response = $this->client->request(
            $method,
            static::BASE_URL . static::API_VERSION . ltrim($endpoint, '/'),
            $this->getRequestHeaders(),
            $bodyStringToSend
        );

        $this->validateResponse($response);

        return $response;
    }

    /**
     * Makes asynchronous HTTP call.
     *
     * @param string $method HTTP method (GET, POST, PUT, etc.).
     * @param string $endpoint Endpoint resource on remote API.
     * @param array $body Request payload body.
     */
    protected function callAsync($method, $endpoint, $body = array())
    {
        $bodyStringToSend = '';
        if (in_array(strtoupper($method), array(self::HTTP_METHOD_POST, self::HTTP_METHOD_PUT), true)) {
            $bodyStringToSend = json_encode($body);
        }

        $this->client->requestAsync(
            $method,
            static::BASE_URL . static::API_VERSION . ltrim($endpoint, '/'),
            $this->getRequestHeaders(),
            $bodyStringToSend
        );
    }

    /**
     * Validates HTTP response.
     *
     * @param HttpResponse $response HTTP response returned from API call.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    protected function validateResponse(HttpResponse $response)
    {
        if (!$response->isSuccessful()) {
            $httpCode = $response->getStatus();
            $error = $message = $response->decodeBodyAsJson();
            if (is_array($error)) {
                $message = '';
                if (isset($error['messages']) && is_array($error['messages'])) {
                    $message = implode("\n", array_column($error['messages'], 'message'));
                } elseif (isset($error['message'])) {
                    $message = $error['message'];
                }
            }

            if ($httpCode === 404) {
                $message = '404 Not found.';
            }

            Logger::logInfo($message);
            if ($httpCode === self::HTTP_STATUS_CODE_UNAUTHORIZED) {
                throw new HttpAuthenticationException($message, $httpCode);
            }

            throw new HttpRequestException($message, $httpCode);
        }
    }

    /**
     * Returns headers together with authorization entry.
     *
     * @return array Formatted request headers.
     */
    private function getRequestHeaders()
    {
        return array(
            'accept' => 'Accept: application/json',
            'content' => 'Content-Type: application/json',
            'token' => 'Authorization: ' . $this->token,
        );
    }
}
