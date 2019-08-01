<?php

namespace Logeecom\Infrastructure\AutoTest;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\Exceptions\StorageNotAccessibleException;
use Logeecom\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ORM\QueryFilter\Operators;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\QueueService;

/**
 * Class AutoTestService.
 *
 * @package Logeecom\Infrastructure\AutoTest
 */
abstract class AutoTestService
{
    /**
     * Configuration service instance.
     *
     * @var Configuration
     */
    private $configService;

    /**
     * Starts the auto-test.
     *
     * @return int The queue item ID.
     */
    public function startAutoTest()
    {
        $this->setAutoTestMode();
        try {
            Logger::logInfo('Start auto-test');
        } catch (\Exception $e) {
            throw new StorageNotAccessibleException('Cannot start the auto-test because storage is not accessible');
        }

        Logger::logInfo(
            'Current HTTP configuration options',
            'Core',
            $this->getConfigService()->getHttpConfigurationOptions()
        );

        /** @var QueueService $queueService */
        $queueService = ServiceRegister::getService(QueueService::CLASS_NAME);
        $queueItem = $queueService->enqueue('Auto-test', new AutoTestTask('DUMMY TEST DATA'));

        return $queueItem->getId();
    }

    /**
     * Activates the auto-test mode and registers the necessary components.
     */
    public function setAutoTestMode()
    {
        $this->registerLogRepository();

        ServiceRegister::registerService(
            ShopLoggerAdapter::CLASS_NAME,
            function () {
                return AutoTestLogger::getInstance();
            }
        );

        $this->getConfigService()->setAutoTestMode(true);
    }

    /**
     * Gets the status of the auto-test task (queue item).
     *
     * @param int $queueItemId
     *
     * @return string The status of the queue item if found; otherwise, an empty string.
     */
    public function getAutoTestTaskStatus($queueItemId)
    {
        $filter = new QueryFilter();
        $filter->where('id', Operators::EQUALS, $queueItemId);

        $item = RepositoryRegistry::getQueueItemRepository()->selectOne($filter);

        return $item ? $item->getStatus() : '';
    }

    /**
     * Registers a log repository.
     */
    abstract protected function registerLogRepository();

    /**
     * Gets the configuration service instance.
     *
     * @return \Logeecom\Infrastructure\Configuration\Configuration Configuration service instance.
     */
    private function getConfigService()
    {
        if ($this->configService === null) {
            $this->configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        }

        return $this->configService;
    }
}
