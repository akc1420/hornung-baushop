<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\SyncSettings\Contracts;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Config\SyncService;

interface SyncSettingsService
{
    const CLASS_NAME = __CLASS__;

    /**
     * Sets enables services.
     *
     * @param SyncService[] $services
     *
     * @return void
     */
    public function setEnabledServices(array $services);

    /**
     * Retrieves enabled services.
     *
     * @return SyncService[]
     */
    public function getEnabledServices();

    /**
     * Retrieves all available services that can be enabled by user.
     *
     * @return SyncService[]
     */
    public function getAvailableServices();
}