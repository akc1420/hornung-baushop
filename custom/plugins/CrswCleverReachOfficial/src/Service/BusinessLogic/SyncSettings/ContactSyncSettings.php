<?php

namespace Crsw\CleverReachOfficial\Service\BusinessLogic\SyncSettings;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Contracts\SyncServicePriority;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Config\SyncService;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Customer\ContactService;

/**
 * Class ContactSyncSettings
 *
 * @package Crsw\CleverReachOfficial\Service\BusinessLogic\SyncSettings
 */
class ContactSyncSettings extends SyncService
{
    /**
     * ContactSyncSettings constructor.
     */
    public function __construct()
    {
        parent::__construct('contact-service', SyncServicePriority::LOWEST, ContactService::class);
    }

    /**
     * @inheritDoc
     */
    public static function fromArray(array $data)
    {
        return new self();
    }
}