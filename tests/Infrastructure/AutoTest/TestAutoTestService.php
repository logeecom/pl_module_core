<?php

namespace Logeecom\Tests\Infrastructure\AutoTest;

use Logeecom\Infrastructure\AutoTest\AutoTestService;
use Logeecom\Infrastructure\Logger\LogData;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;

class TestAutoTestService extends AutoTestService
{
    /**
     * @var bool
     */
    public $shouldRegisterLogRepository = true;

    /**
     * Registers a log repository.
     */
    protected function registerLogRepository()
    {
        if ($this->shouldRegisterLogRepository) {
            RepositoryRegistry::registerRepository(LogData::getClassName(), MemoryRepository::getClassName());
        }
    }
}