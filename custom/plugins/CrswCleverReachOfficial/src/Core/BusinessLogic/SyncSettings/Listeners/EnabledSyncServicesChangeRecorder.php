<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\SyncSettings\Listeners;

use Crsw\CleverReachOfficial\Core\BusinessLogic\SyncSettings\Entities\EnabledServicesChangeLog;
use Crsw\CleverReachOfficial\Core\BusinessLogic\SyncSettings\Events\EnabledServicesSetEvent;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\ConfigurationManager;
use Crsw\CleverReachOfficial\Core\Infrastructure\Data\Transformer;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\RepositoryRegistry;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\TimeProvider;

class EnabledSyncServicesChangeRecorder
{
    const CLASS_NAME = __CLASS__;

    /**
     * Saves current services in change log.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\SyncSettings\Events\EnabledServicesSetEvent $event
     *
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public static function handle(EnabledServicesSetEvent $event)
    {
        $entity = new EnabledServicesChangeLog();
        $entity->createdAt = TimeProvider::getInstance()->getDateTime(time());
        $entity->services = Transformer::batchTransform($event->getNewServices());
        $entity->context = static::getConfigManager()->getContext();

        static::getRepository()->save($entity);
    }

    /**
     * Retrieves repository.
     *
     * @return \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Interfaces\RepositoryInterface
     *
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    private static function getRepository()
    {
        return RepositoryRegistry::getRepository(EnabledServicesChangeLog::getClassName());
    }

    /**
     * Return configuration manager.
     *
     * @return ConfigurationManager | object
     */
    private static function getConfigManager()
    {
        return ServiceRegister::getService(ConfigurationManager::CLASS_NAME);
    }
}