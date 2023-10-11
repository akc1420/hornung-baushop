<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\SyncSettings\Listeners;

use Crsw\CleverReachOfficial\Core\BusinessLogic\InitialSynchronization\Tasks\Composite\InitialSyncTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\SyncSettings\Events\EnabledServicesSetEvent;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\ConfigurationManager;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueService;

class InitialSyncTaskEnqueuer
{
    /**
     * Enqueues initial sync after the sync services have been set.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\SyncSettings\Events\EnabledServicesSetEvent $event
     *
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     *
     * @noinspection PhpUnusedParameterInspection
     */
    public static function handle(EnabledServicesSetEvent $event)
    {
        $context = self::getConfigurationManager()->getContext();
        $task = static::getQueue()->findLatestByType('InitialSyncTask', $context);

        if ($task !== null) {
            return;
        }

        $task = static::getInitialSyncTask();
        $configuration = static::getConfigService();
        static::getQueue()->enqueue(
            $configuration->getDefaultQueueName(),
            $task,
            $context
        );
    }

    /**
     * Retrieves initial sync task instance.
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\InitialSynchronization\Tasks\Composite\InitialSyncTask
     */
    protected static function getInitialSyncTask()
    {
        return new InitialSyncTask();
    }

    /**
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Configuration\Configuration
     */
    private static function getConfigService()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(Configuration::CLASS_NAME);
    }

    /**
     * Retrieves queue service.
     *
     * @return QueueService
     */
    private static function getQueue()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(QueueService::CLASS_NAME);
    }

    /**
     * Retrieves configuration manager.
     *
     * @return \Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\ConfigurationManager
     */
    private static function getConfigurationManager()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(ConfigurationManager::CLASS_NAME);
    }
}