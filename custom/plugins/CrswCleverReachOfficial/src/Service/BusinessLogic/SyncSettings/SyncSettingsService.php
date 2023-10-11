<?php

namespace Crsw\CleverReachOfficial\Service\BusinessLogic\SyncSettings;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Config\SyncService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\SyncSettings\SyncSettingsService as BaseService;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;

/**
 * Class SyncSettingsService
 *
 * @package Crsw\CleverReachOfficial\Service\BusinessLogic\SyncSettings
 */
class SyncSettingsService extends BaseService
{
    /**
     * Retrieves all available services that can be enabled by user.
     *
     * @return SyncService[]
     */
    public function getAvailableServices(): array
    {
        return [
            ServiceRegister::getService(SubscriberSyncSettings::class),
            ServiceRegister::getService(BuyerSyncSettings::class),
            ServiceRegister::getService(ContactSyncSettings::class),
        ];
    }
}