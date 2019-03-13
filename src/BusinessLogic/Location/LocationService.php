<?php

namespace Packlink\BusinessLogic\Location;

use Logeecom\Infrastructure\Http\Exceptions\HttpBaseException;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\BaseService;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Http\DTO\BaseDto;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\Location\Exceptions\PlatformCountryNotSupportedException;
use Packlink\BusinessLogic\ShippingMethod\ShippingCostCalculator;
use Packlink\BusinessLogic\ShippingMethod\ShippingMethodService;

/**
 * Class LocationService
 *
 * @package Packlink\BusinessLogic\Location
 */
class LocationService extends BaseService
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Postal zone map.
     *
     * @var array
     */
    protected $postalZoneMap = array(
        'DE' => array('3', '248', '249'),
        'ES' => array('65', '68', '69'),
        'IT' => array('113', '114', '115'),
        'FR' => array('76', '77')
    );
    /**
     * Singleton instance of this class.
     *
     * @var static
     */
    protected static $instance;
    /**
     * Configuration instance.
     *
     * @var Configuration
     */
    private $configuration;
    /**
     * Shipping method service.
     *
     * @var ShippingMethodService
     */
    private $shippingMethodService;
    /**
     * Proxy instance.
     *
     * @var Proxy
     */
    private $proxy;

    /**
     * LocationService constructor.
     */
    protected function __construct()
    {
        parent::__construct();

        $this->proxy = ServiceRegister::getService(Proxy::CLASS_NAME);
        $this->shippingMethodService = ServiceRegister::getService(ShippingMethodService::CLASS_NAME);
        $this->configuration = ServiceRegister::getService(Configuration::CLASS_NAME);
    }

    /**
     * Returns array of locations for this shipping method.
     *
     * @param int $shippingMethodId Shipping method identifier.
     * @param string $toCountry Country code to where package is shipped.
     * @param string $toPostCode Post code to where package is shipped.
     *
     * @return array Locations.
     */
    public function getLocations($shippingMethodId, $toCountry, $toPostCode)
    {
        $warehouse = $this->configuration->getDefaultWarehouse();
        $method = $this->shippingMethodService->getShippingMethod($shippingMethodId);
        if ($warehouse === null || $method === null) {
            return array();
        }

        try {
            $cheapestService = ShippingCostCalculator::getCheapestShippingService(
                $method,
                $warehouse->country,
                $warehouse->postalCode,
                $toCountry,
                $toPostCode
            );

            $locations = $this->proxy->getLocations($cheapestService->serviceId, $toCountry, $toPostCode);

            return $this->transformCollectionToResponse($locations);
        } catch (\InvalidArgumentException $e) {
            return array();
        } catch (HttpBaseException $e) {
            return array();
        }
    }

    /**
     * Performs search for locations.
     *
     * @param $platformCountry
     * @param $query
     *
     * @return \Packlink\BusinessLogic\Http\DTO\LocationInfo[]
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     *
     * @throws \Packlink\BusinessLogic\Location\Exceptions\PlatformCountryNotSupportedException
     */
    public function searchLocations($platformCountry, $query)
    {
        if (!isset($this->postalZoneMap[$platformCountry])) {
            throw new PlatformCountryNotSupportedException('Platform country not supported');
        }

        $result = array();

        foreach ($this->postalZoneMap[$platformCountry] as $postalZone) {
            $partial = $this->proxy->searchLocations($platformCountry, $postalZone, $query);

            if (!empty($partial)) {
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $result = array_merge($result, $partial);
            }
        }

        return $result;
    }

    /**
     * Transforms collection of DTOs to an array response.
     *
     * @param BaseDto[] $collection
     *
     * @return array
     */
    protected function transformCollectionToResponse($collection)
    {
        $result = array();

        foreach ($collection as $element) {
            $result[] = $element->toArray();
        }

        return $result;
    }
}