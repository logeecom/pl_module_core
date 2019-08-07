<?php

namespace Logeecom\Tests\Infrastructure\AutoTest;

use Logeecom\Infrastructure\AutoTest\AutoTestLogger;
use Logeecom\Infrastructure\Http\DTO\OptionsDTO;
use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use Logeecom\Infrastructure\Logger\LogData;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Logeecom\Infrastructure\TaskExecution\QueueService;
use Logeecom\Infrastructure\TaskExecution\TaskRunnerWakeupService;
use Logeecom\Tests\Infrastructure\Common\BaseInfrastructureTestWithServices;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryQueueItemRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\TestRepositoryRegistry;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestQueueService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestTaskRunnerWakeupService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TestHttpClient;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;

/**
 * Class AutoTestServiceTest.
 *
 * @package Logeecom\Tests\Infrastructure\AutoTest
 */
class AutoTestServiceTest extends BaseInfrastructureTestWithServices
{
    /**
     * @var TestHttpClient
     */
    protected $httpClient;
    /**
     * @var TestHttpClient
     */
    protected $logger;

    /**
     * @throws \Exception
     */
    public function setUp()
    {
        parent::setUp();

        RepositoryRegistry::registerRepository(QueueItem::CLASS_NAME, MemoryQueueItemRepository::getClassName());

        $me = $this;
        $this->httpClient = new TestHttpClient();
        TestServiceRegister::registerService(
            HttpClient::CLASS_NAME,
            function () use ($me) {
                return $me->httpClient;
            }
        );

        $queue = new TestQueueService();
        TestServiceRegister::registerService(
            QueueService::CLASS_NAME,
            function () use ($queue) {
                return $queue;
            }
        );

        $wakeupService = new TestTaskRunnerWakeupService();
        TestServiceRegister::registerService(
            TaskRunnerWakeupService::CLASS_NAME,
            function () use ($wakeupService) {
                return $wakeupService;
            }
        );
    }

    /**
     * @inheritDoc
     */
    public function tearDown()
    {
        parent::tearDown();

        TestRepositoryRegistry::cleanUp();
        AutoTestLogger::resetInstance();
    }

    /**
     * Test setting auto-test mode.
     */
    public function testSetAutoTestMode()
    {
        $service = new TestAutoTestService();
        $service->setAutoTestMode();

        $repo = RepositoryRegistry::getRepository(LogData::getClassName());
        self::assertNotNull($repo, 'Log repository should be registered.');

        $loggerService = ServiceRegister::getService(ShopLoggerAdapter::CLASS_NAME);
        self::assertNotNull($loggerService, 'Logger service should be registered.');
        self::assertInstanceOf(
            '\\Logeecom\\Infrastructure\\AutoTest\\AutoTestLogger',
            $loggerService,
            'AutoTestLogger service should be registered.'
        );

        self::assertTrue($this->shopConfig->isAutoTestMode(), 'Auto-test mode should be set.');
    }

    /**
     * Test successful start of the auto-test.
     */
    public function testStartAutoTestSuccess()
    {
        $this->shopConfig->setHttpConfigurationOptions(array(new OptionsDTO('test', 'value')));

        $service = new TestAutoTestService();
        $queueItemId = $service->startAutoTest();

        self::assertNotNull($queueItemId, 'Test task should be enqueued.');

        $status = $service->getAutoTestTaskStatus($queueItemId);
        self::assertEquals('queued', $status, 'AutoTest tasks should be enqueued.');

        $allLogs = AutoTestLogger::getInstance()->getLogs();

        self::assertNotEmpty($allLogs, 'Starting logs should be added.');
        self::assertEquals('Start auto-test', $allLogs[0]->getMessage(), 'Starting logs should be added.');

        $context = $allLogs[1]->getContext();
        self::assertCount(1, $context, 'Current HTTP configuration options should be logged.');
        self::assertEquals(
            'HTTPOptions',
            $context[0]->getName(),
            'Current HTTP configuration options should be logged.'
        );

        self::assertCount(1, $context[0]->getValue(), 'One HTTP configuration options should be set.');
        self::assertInstanceOf(
            'Logeecom\\Infrastructure\\Http\\DTO\\OptionsDTO',
            current($context[0]->getValue()),
            'Log context should have the instance of OptionsDTOCurrent HTTP configuration options should be logged.'
        );
    }

    /**
     * Tests failure when storage is not available.
     *
     * @expectedException \Logeecom\Infrastructure\Exceptions\StorageNotAccessibleException
     */
    public function testStartAutoTestStorageFailure()
    {
        $service = new TestAutoTestService();
        $service->shouldRegisterLogRepository = false;

        $service->startAutoTest();
    }
}
