<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\SyncSettings\Listeners;

use Crsw\CleverReachOfficial\Core\BusinessLogic\SecondarySynchronization\Tasks\Composite\SecondarySyncTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\SyncSettings\Events\EnabledServicesSetEvent;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\ConfigurationManager;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueItem;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueService;

class SecondarySyncTaskEnqueuer
{
    const CLASS_NAME = __CLASS__;

    /**
     * Enqueues Secondary sync task.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\SyncSettings\Events\EnabledServicesSetEvent $event
     *
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    public static function handle(EnabledServicesSetEvent $event)
    {
        $oldServices = static::getServicesIds($event->getPreviousServices());
        $newServices = static::getServicesIds($event->getNewServices());

        $addedServices = array_diff($newServices, $oldServices);
        if (empty($addedServices)) {
            return;
        }

        $manager = self::getConfigurationManager();
        $initialSync = static::getQueue()->findLatestByType('InitialSyncTask', $manager->getContext());
        if ($initialSync === null || $initialSync->getStatus() !== QueueItem::COMPLETED) {
            return;
        }

        $task = static::getSecondarySyncTask();
        static::enqueue($task);
    }

    /**
     * Retrieves secondary sync.
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\SecondarySynchronization\Tasks\Composite\SecondarySyncTask
     */
    protected static function getSecondarySyncTask()
    {
        return new SecondarySyncTask();
    }

    /**
     * Enqueues secondary sync task.
     *
     * @param $task
     *
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    protected static function enqueue($task)
    {
        $config = static::getConfigService();
        $manager = self::getConfigurationManager();
        static::getQueue()->enqueue($config->getDefaultQueueName(), $task, $manager->getContext());
    }

    /**
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Configuration\Configuration
     */
    protected static function getConfigService()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(Configuration::CLASS_NAME);
    }

    /**
     * Retrieves queue service.
     *
     * @return QueueService
     */
    protected static function getQueue()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(QueueService::CLASS_NAME);
    }

    /**
     * Retrieves services ids.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Config\SyncService[] $services
     *
     * @return array
     */
    protected static function getServicesIds(array $services)
    {
        $result = array();

        /** @var \Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Config\SyncService $service */
        foreach ($services as $service) {
            $result[] = $service->getUuid();
        }

        return $result;
    }

    /**
     * Retrieves configuration manager.
     *
     * @return \Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\ConfigurationManager
     */
    protected static function getConfigurationManager()
    {
        /** @var ConfigurationManager $manager */
        $manager = ServiceRegister::getService(ConfigurationManager::CLASS_NAME);

        return $manager;
    }
}