<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\SyncSettings;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Contracts\SyncConfigService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\SyncSettings\Contracts\SyncSettingsService as BaseService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\SyncSettings\Events\EnabledServicesSetEvent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\SyncSettings\Events\SyncSettingsEventBus;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;

abstract class SyncSettingsService implements BaseService
{
    /**
     * @inheritDoc
     */
    public function setEnabledServices(array $services)
    {
        $previousServices = $this->getSyncConfigService()->getEnabledServices();
        $this->getSyncConfigService()->setEnabledServices($services);

        SyncSettingsEventBus::getInstance()->fire(new EnabledServicesSetEvent($previousServices, $services));
    }

    /**
     * @inheritDoc
     */
    public function getEnabledServices()
    {
        return $this->getSyncConfigService()->getEnabledServices();
    }

    /**
     * Retrieves sync config service.
     *
     * @return SyncConfigService
     */
    private function getSyncConfigService()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(SyncConfigService::CLASS_NAME);
    }
}