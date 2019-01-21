<?php

namespace Packlink\BusinessLogic\WebHook;

use Logeecom\Infrastructure\Http\Exceptions\HttpBaseException;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\BaseService;
use Packlink\BusinessLogic\Http\DTO\Shipment;
use Packlink\BusinessLogic\Http\DTO\Tracking;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\Order\Exceptions\OrderNotFound;
use Packlink\BusinessLogic\Order\Interfaces\OrderRepository;
use Packlink\BusinessLogic\WebHook\Events\ShippingLabelEvent;
use Packlink\BusinessLogic\WebHook\Events\ShippingStatusEvent;
use Packlink\BusinessLogic\WebHook\Events\TrackingInfoEvent;

/**
 * Class WebHookService.
 *
 * @package Packlink\BusinessLogic\WebHook
 */
class WebHookEventHandler extends BaseService
{
    /**
     * Singleton instance of this class.
     *
     * @var static
     */
    protected static $instance;
    /**
     * Order repository instance.
     *
     * @var OrderRepository
     */
    private $orderRepository;
    /**
     * Proxy instance.
     *
     * @var Proxy
     */
    private $proxy;

    /**
     * WebHookService constructor.
     */
    protected function __construct()
    {
        parent::__construct();

        $this->proxy = ServiceRegister::getService(Proxy::CLASS_NAME);
        $this->orderRepository = ServiceRegister::getService(OrderRepository::CLASS_NAME);
    }

    /**
     * Handles web hook shipping label event.
     *
     * @param ShippingLabelEvent $event Web-hook event.
     */
    public function handleShippingLabelEvent(ShippingLabelEvent $event)
    {
        $labels = array();
        $referenceId = $event->referenceId;
        try {
            $labels = $this->proxy->getLabels($referenceId);
            $this->orderRepository->setLabelsByReference($referenceId, $labels);
        } catch (HttpBaseException $e) {
            Logger::logError($e->getMessage(), 'Core', array('referenceId' => $referenceId));
        } catch (OrderNotFound $e) {
            Logger::logError($e->getMessage(), 'Core', array('referenceId' => $referenceId, 'labels' => $labels));
        }
    }

    /**
     * Handles web hook shipping status update event.
     *
     * @param ShippingStatusEvent $event Web-hook event.
     */
    public function handleShippingStatusEvent(ShippingStatusEvent $event)
    {
        $referenceId = $event->referenceId;
        try {
            /** @var Shipment $shipment */
            $shipment = $this->proxy->getShipment($referenceId);
            $this->orderRepository->setShippingStatusByReference($referenceId, $shipment->status);
        } catch (HttpBaseException $e) {
            Logger::logError($e->getMessage(), 'Core', array('referenceId' => $referenceId));
        } catch (OrderNotFound $e) {
            /** @noinspection PhpUndefinedVariableInspection */
            Logger::logError(
                $e->getMessage(),
                'Core',
                array('referenceId' => $referenceId, 'shipment' => $shipment->toArray())
            );
        }
    }

    /**
     * Handles web hook tracking info update event.
     *
     * @param TrackingInfoEvent $event Web-hook event.
     */
    public function handleTrackingInfoEvent(TrackingInfoEvent $event)
    {
        $referenceId = $event->referenceId;
        try {
            /** @var Tracking[] $trackingHistory */
            $trackingHistory = $this->proxy->getTrackingInfo($referenceId);
            $this->orderRepository->updateTrackingInfo($referenceId, $trackingHistory);
        } catch (HttpBaseException $e) {
            Logger::logError($e->getMessage(), 'Core', array('referenceId' => $referenceId));
        } catch (OrderNotFound $e) {
            $trackingAsArray = array();
            /** @noinspection PhpUndefinedVariableInspection */
            foreach ($trackingHistory as $item) {
                $trackingAsArray[] = $item->toArray();
            }

            Logger::logError(
                $e->getMessage(),
                'Core',
                array('referenceId' => $referenceId, 'trackingHistory' => $trackingAsArray)
            );
        }
    }
}