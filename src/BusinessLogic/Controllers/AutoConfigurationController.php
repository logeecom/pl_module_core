<?php

namespace Packlink\BusinessLogic\Controllers;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\Exceptions\BaseException;
use Logeecom\Infrastructure\Http\AutoConfiguration;
use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\ORM\QueryFilter\Operators;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerWakeup;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Logeecom\Infrastructure\TaskExecution\QueueService;
use Logeecom\Infrastructure\Utility\TimeProvider;
use Packlink\BusinessLogic\Tasks\UpdateShippingServicesTask;

/**
 * Class AutoConfigurationController.
 *
 * @package Packlink\BusinessLogic\Controllers
 */
class AutoConfigurationController
{
    /**
     * Starts the auto-configuration process.
     *
     * @param bool $enqueueTask Indicates whether to enqueue the update services task after
     *  the successful configuration.
     *
     * @return bool TRUE if the process completed successfully; otherwise, FALSE.
     */
    public function start($enqueueTask = false)
    {
        /** @var Configuration $configService */
        $configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        /** @var \Logeecom\Infrastructure\Http\HttpClient $httpService */
        $httpService = ServiceRegister::getService(HttpClient::CLASS_NAME);
        $service = new AutoConfiguration($configService, $httpService);

        try {
            $success = $service->start();
            if ($success) {
                if ($enqueueTask) {
                    $this->enqueueUpdateServicesTask($configService);
                }

                /** @var TaskRunnerWakeup $wakeup */
                $wakeup = ServiceRegister::getService(TaskRunnerWakeup::CLASS_NAME);
                $wakeup->wakeup();
            }
        } catch (BaseException $e) {
            $success = false;
        }

        return $success;
    }

    /**
     * Checks the status of the task responsible for getting services.
     *
     * @return bool TRUE if the task is alive or completed successfully; otherwise, FALSE.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     */
    public function isGettingServicesTaskSuccessful()
    {
        $repo = RepositoryRegistry::getQueueItemRepository();
        $filter = new QueryFilter();
        $filter->where('taskType', Operators::EQUALS, 'UpdateShippingServicesTask');
        $filter->orderBy('queueTime', 'DESC');

        $item = $repo->selectOne($filter);
        if ($item) {
            $status = $item->getStatus();
            if ($status === QueueItem::FAILED) {
                return false;
            }

            if ($status === QueueItem::COMPLETED) {
                return true;
            }

            /** @var TimeProvider $timeProvider */
            $timeProvider = ServiceRegister::getService(TimeProvider::CLASS_NAME);
            $currentTimestamp = $timeProvider->getCurrentLocalTime()->getTimestamp();
            $taskTimestamp = $item->getLastUpdateTimestamp() ?: $item->getQueueTimestamp();
            $expired = $taskTimestamp + $item->getTask()->getMaxInactivityPeriod() < $currentTimestamp;

            return !$expired;
        }

        return false;
    }

    /**
     * @param Configuration $configService
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    protected function enqueueUpdateServicesTask(Configuration $configService)
    {
        $repo = RepositoryRegistry::getQueueItemRepository();
        $filter = new QueryFilter();
        $filter->where('taskType', Operators::EQUALS, 'UpdateShippingServicesTask');
        $filter->where('status', Operators::EQUALS, QueueItem::QUEUED);
        $item = $repo->selectOne($filter);
        if ($item) {
            $repo->delete($item);
        }

        // enqueue the task for updating shipping services
        /** @var QueueService $queueService */
        $queueService = ServiceRegister::getService(QueueService::CLASS_NAME);
        $queueService->enqueue($configService->getDefaultQueueName(), new UpdateShippingServicesTask());
    }
}
