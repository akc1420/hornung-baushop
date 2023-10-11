<?php

namespace Crsw\CleverReachOfficial\Service\BusinessLogic\SyncSettings;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Contracts\SyncServicePriority;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Config\SyncService;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Customer\SubscriberService;

/**
 * Class SubscriberSyncSettings
 *
 * @package Crsw\CleverReachOfficial\Service\BusinessLogic\SyncSettings
 */
class SubscriberSyncSettings extends SyncService
{
    /**
     * SubscriberSyncSettings constructor.
     */
    public function __construct()
    {
        parent::__construct('subscriber-service', SyncServicePriority::HIGH, SubscriberService::class);
    }

    /**
     * @inheritDoc
     */
    public static function fromArray(array $data)
    {
        return new self();
    }
}