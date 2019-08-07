<?php

namespace Logeecom\Infrastructure\AutoTest;

use Logeecom\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use Logeecom\Infrastructure\Logger\LogData;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\Singleton;

/**
 * Class AutoTestLogger.
 *
 * @package Logeecom\Infrastructure\AutoConfiguration
 */
class AutoTestLogger extends Singleton implements ShopLoggerAdapter
{
    /**
     * Logs a message in system.
     *
     * @param LogData $data Data to log.
     */
    public function logMessage(LogData $data)
    {
        $repo = RepositoryRegistry::getRepository(LogData::CLASS_NAME);
        $repo->save($data);
    }

    /**
     * Gets all log entities.
     *
     * @return LogData[] An array of the LogData entities, if any.
     */
    public function getLogs()
    {
        $repo = RepositoryRegistry::getRepository(LogData::CLASS_NAME);

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $repo->select();
    }
}
