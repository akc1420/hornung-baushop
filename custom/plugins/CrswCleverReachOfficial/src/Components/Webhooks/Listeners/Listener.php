<?php


namespace Crsw\CleverReachOfficial\Components\Webhooks\Listeners;

use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueService;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Task;

/**
 * Class Listener
 *
 * @package Crsw\CleverReachOfficial\Components\Webhooks\Listeners
 */
abstract class Listener
{
    /**
     * Enqueues task.
     *
     * @param Task $task
     *
     * @throws QueueStorageUnavailableException
     */
    protected static function enqueue(Task $task): void
    {
        $queueName = static::getConfigService()->getDefaultQueueName();

        static::getQueue()->enqueue($queueName, $task);
    }

    /**
     * @return Configuration
     */
    protected static function getConfigService(): Configuration
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(Configuration::CLASS_NAME);
    }

    /**
     * @return QueueService
     */
    protected static function getQueue(): QueueService
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(QueueService::CLASS_NAME);
    }
}