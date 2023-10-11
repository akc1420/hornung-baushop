<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Contracts;

/**
 * Interface SyncConfigService
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Contracts
 */
interface SyncConfigService
{
    const CLASS_NAME = __CLASS__;

    /**
     * Retrieves enabled services.
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Config\SyncService[]
     */
    public function getEnabledServices();

    /**
     * Sets enabled services.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Config\SyncService[] $services
     *
     * @return void
     */
    public function setEnabledServices(array $services);
}