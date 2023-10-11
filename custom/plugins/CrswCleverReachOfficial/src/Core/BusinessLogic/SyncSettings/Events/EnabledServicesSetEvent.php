<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\SyncSettings\Events;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Config\SyncService;
use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Events\Event;

/**
 * Class EnabledServicesSet
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\SyncSettings\Events
 */
class EnabledServicesSetEvent extends Event
{
    const CLASS_NAME = __CLASS__;

    /**
     * @var SyncService[]
     */
    private $previousServices;
    /**
     * @var SyncService[]
     */
    private $newServices;

    /**
     * EnabledServicesSet constructor.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Config\SyncService[] $previousServices
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Config\SyncService[] $newServices
     */
    public function __construct(array $previousServices, array $newServices)
    {
        $this->previousServices = $previousServices;
        $this->newServices = $newServices;
    }

    /**
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Config\SyncService[]
     */
    public function getPreviousServices()
    {
        return $this->previousServices;
    }

    /**
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Config\SyncService[]
     */
    public function getNewServices()
    {
        return $this->newServices;
    }
}