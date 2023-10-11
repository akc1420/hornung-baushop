<?php

namespace Crsw\CleverReachOfficial\Service\BusinessLogic\SyncSettings;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Contracts\SyncServicePriority;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Config\SyncService;
use Crsw\CleverReachOfficial\Mergers\BuyerMerger;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Customer\BuyerService;

/**
 * Class BuyerSyncSettings
 *
 * @package Crsw\CleverReachOfficial\Service\BusinessLogic\SyncSettings
 */
class BuyerSyncSettings extends SyncService
{
    /**
     * BuyerSyncSettings constructor.
     */
    public function __construct()
    {
        parent::__construct(
            'buyer-service',
            SyncServicePriority::MEDIUM,
            BuyerService::class,
            BuyerMerger::class
        );
    }

    /**
     * @inheritDoc
     */
    public static function fromArray(array $data)
    {
        return new self();
    }
}
