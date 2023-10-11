<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Config\SyncService;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\ConfigurationManager;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\Singleton;

/**
 * Class SyncConfigService
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver
 */
class SyncConfigService extends Singleton implements Contracts\SyncConfigService
{
    /**
     * @var static
     */
    protected static $instance;
    /**
     * Retrieves enabled services.
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Config\SyncService[]
     *
     * @noinspection PhpDocMissingThrowsInspection
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function getEnabledServices()
    {
        return SyncService::fromBatch($this->getConfigurationManager()->getConfigValue('enabledSyncServices', array()));
    }

    /**
     * Sets enabled services.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Config\SyncService[] $services
     *
     * @return void
     *
     * @noinspection PhpDocMissingThrowsInspection
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function setEnabledServices(array $services)
    {
        $persistFormat = array();

        foreach ($services as $service) {
            $persistFormat[] = $service->toArray();
        }

        $this->getConfigurationManager()->saveConfigValue('enabledSyncServices', $persistFormat);
    }

    /**
     * Retrieves configuration manager.
     *
     * @return \Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\ConfigurationManager Configuration Manager instance.
     */
    protected function getConfigurationManager()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(ConfigurationManager::CLASS_NAME);
    }
}