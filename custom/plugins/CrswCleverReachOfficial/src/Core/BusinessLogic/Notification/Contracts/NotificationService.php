<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Notification\Contracts;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Notification\DTO\Notification;

/**
 * Interface NotificationService
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Notification\Contracts
 */
interface NotificationService
{
    const CLASS_NAME = __CLASS__;

    public function push(Notification $notification);
}