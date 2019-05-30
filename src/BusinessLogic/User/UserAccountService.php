<?php

namespace Packlink\BusinessLogic\User;

use Logeecom\Infrastructure\Http\Exceptions\HttpBaseException;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\QueueService;
use Packlink\BusinessLogic\BaseService;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Http\DTO\User;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\Scheduler\Models\HourlySchedule;
use Packlink\BusinessLogic\Scheduler\Models\Schedule;
use Packlink\BusinessLogic\Scheduler\Models\WeeklySchedule;
use Packlink\BusinessLogic\Tasks\UpdateShipmentDataTask;
use Packlink\BusinessLogic\Tasks\UpdateShippingServicesTask;

/**
 * Class UserAccountService.
 *
 * @package Packlink\BusinessLogic\User
 */
class UserAccountService extends BaseService
{
    /**
     * Fully qualified name of this interface.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Singleton instance of this class.
     *
     * @var static
     */
    protected static $instance;
    /**
     * Configuration service instance.
     *
     * @var Configuration
     */
    private $configuration;
    /**
     * Proxy instance.
     *
     * @var Proxy
     */
    private $proxy;

    /**
     * UserAccountService constructor.
     */
    protected function __construct()
    {
        parent::__construct();

        $this->configuration = ServiceRegister::getService(Configuration::CLASS_NAME);
    }

    /**
     * Validates provided API key and initializes user's data.
     *
     * @param string $apiKey API key.
     *
     * @return bool TRUE if login went successfully; otherwise, FALSE.
     *
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function login($apiKey)
    {
        if (empty($apiKey)) {
            return false;
        }

        // set token before calling API
        $this->configuration->setAuthorizationToken($apiKey);

        try {
            $userDto = $this->getProxy()->getUserData();
            $this->initializeUser($userDto);
        } catch (HttpBaseException $e) {
            $this->configuration->resetAuthorizationCredentials();
            Logger::logError($e->getMessage());

            return false;
        }

        $this->createSchedules();

        return true;
    }

    /**
     * Sets default parcel information.
     *
     * @param bool $force Force retrieval of parcel info from Packlink API.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpBaseException
     */
    public function setDefaultParcel($force)
    {
        $parcelInfo = $this->configuration->getDefaultParcel();
        if ($parcelInfo === null || $force) {
            $parcels = $this->getProxy()->getUsersParcelInfo();
            foreach ($parcels as $parcel) {
                if ($parcel->default) {
                    $parcelInfo = $parcel;
                    break;
                }
            }

            if ($parcelInfo !== null) {
                $this->configuration->setDefaultParcel($parcelInfo);
            }
        }
    }

    /**
     * Sets warehouse information.
     *
     * @param bool $force Force retrieval of warehouse info from Packlink API.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpBaseException
     */
    public function setWarehouseInfo($force)
    {
        $warehouse = $this->configuration->getDefaultWarehouse();
        if ($warehouse === null || $force) {
            $usersWarehouses = $this->getProxy()->getUsersWarehouses();
            foreach ($usersWarehouses as $usersWarehouse) {
                if ($usersWarehouse->default) {
                    $warehouse = $usersWarehouse;
                    break;
                }
            }

            $userInfo = $this->configuration->getUserInfo();
            if ($userInfo === null) {
                $userInfo = $this->getProxy()->getUserData();
            }

            if ($warehouse !== null && $userInfo !== null && $warehouse->country === $userInfo->country) {
                $this->configuration->setDefaultWarehouse($warehouse);
            }
        }
    }

    /**
     * Initializes user configuration and subscribes web-hook callback.
     *
     * @param User $user User data.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpBaseException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    protected function initializeUser(User $user)
    {
        $this->configuration->setUserInfo($user);
        $defaultQueueName = $this->configuration->getDefaultQueueName();

        /** @var QueueService $queueService */
        $queueService = ServiceRegister::getService(QueueService::CLASS_NAME);

        $this->setDefaultParcel(true);
        $this->setWarehouseInfo(true);

        $queueService->enqueue($defaultQueueName, new UpdateShippingServicesTask(), $this->configuration->getContext());

        $webHookUrl = $this->configuration->getWebHookUrl();
        if (!empty($webHookUrl)) {
            $this->getProxy()->registerWebHookHandler($webHookUrl);
        }
    }

    /**
     * Creates schedules.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function createSchedules()
    {
        /** @var Configuration $configService */
        $configService = ServiceRegister::getService(Configuration::CLASS_NAME);

        $repository = RepositoryRegistry::getRepository(Schedule::CLASS_NAME);

        // Schedule weekly task for updating services
        $shippingServicesSchedule = new WeeklySchedule(
            new UpdateShippingServicesTask(),
            $configService->getDefaultQueueName()
        );
        $shippingServicesSchedule->setDay(1);
        $shippingServicesSchedule->setHour(2);
        $shippingServicesSchedule->setNextSchedule();
        $repository->save($shippingServicesSchedule);

        // Schedule hourly task for updating shipment info - start at full hour
        $this->setHourlyTask($configService, $repository, 0);

        // Schedule hourly task for updating shipment info - start at half hour
        $this->setHourlyTask($configService, $repository, 30);
    }

    /**
     * Creates hourly task for updating shipment data.
     *
     * @param Configuration $configService Configuration service
     * @param RepositoryInterface $repository Scheduler repository.
     * @param int $minute Starting minute for the task.
     */
    protected function setHourlyTask(Configuration $configService, RepositoryInterface $repository, $minute)
    {
        $shipmentDataHalfHourSchedule = new HourlySchedule(
            new UpdateShipmentDataTask(),
            $configService->getDefaultQueueName()
        );
        $shipmentDataHalfHourSchedule->setMinute($minute);
        $shipmentDataHalfHourSchedule->setNextSchedule();
        $repository->save($shipmentDataHalfHourSchedule);
    }

    /**
     * Gets Proxy.
     *
     * @return \Packlink\BusinessLogic\Http\Proxy Proxy.
     */
    protected function getProxy()
    {
        if ($this->proxy === null) {
            $this->proxy = ServiceRegister::getService(Proxy::CLASS_NAME);
        }

        return $this->proxy;
    }
}
