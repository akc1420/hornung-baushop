<?php

namespace Crsw\CleverReachOfficial\Core\Infrastructure\AutoTest;

use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\LogData;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\RepositoryRegistry;
use Crsw\CleverReachOfficial\Core\Infrastructure\Singleton;

/**
 * Class AutoTestLogger.
 *
 * @package Crsw\CleverReachOfficial\Core\Infrastructure\AutoConfiguration
 */
class AutoTestLogger extends Singleton implements ShopLoggerAdapter
{
    /**
     * Singleton instance of this class.
     *
     * @var static
     */
    protected static $instance;
    /**
     * Logs a message in system.
     *
     * @param LogData $data Data to log.
     *
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
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
     *
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function getLogs()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return RepositoryRegistry::getRepository(LogData::CLASS_NAME)->select();
    }

    /**
     * Transforms logs to the plain array.
     *
     * @return array An array of logs.
     *
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function getLogsArray()
    {
        $result = array();
        foreach ($this->getLogs() as $log) {
            $result[] = $log->toArray();
        }

        return $result;
    }
}
