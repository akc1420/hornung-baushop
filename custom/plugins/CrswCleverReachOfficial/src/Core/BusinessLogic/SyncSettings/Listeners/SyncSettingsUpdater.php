<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\SyncSettings\Listeners;

use Crsw\CleverReachOfficial\Core\BusinessLogic\SyncSettings\Events\EnabledServicesSetEvent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\SyncSettings\Tasks\UpdateSyncSettingsTask;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\ConfigurationManager;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueService;

class SyncSettingsUpdater
{
    const CLASS_NAME = __CLASS__;

    /**
     * Updates sync settings when list of enabled sync services is changed.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\SyncSettings\Events\EnabledServicesSetEvent $event
     *
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     *
     * @noinspection PhpUnusedParameterInspection
     */
    public static function handle(EnabledServicesSetEvent $event)
    {
        $task = new UpdateSyncSettingsTask();
        $config = static::getConfigService();
        $manager = self::getConfigurationManager();

        static::getQueue()->enqueue($config->getDefaultQueueName(), $task, $manager->getContext());
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
        /** @var ConfigurationManager $manager */
        $manager = ServiceRegister::getService(ConfigurationManager::CLASS_NAME);

        return $manager;
    }
}