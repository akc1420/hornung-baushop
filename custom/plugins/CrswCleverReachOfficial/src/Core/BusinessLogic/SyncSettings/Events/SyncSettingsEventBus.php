<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\SyncSettings\Events;

use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Events\EventBus;

/**
 * Class SyncSettingsEventBus
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\SyncSettings\Events
 */
class SyncSettingsEventBus extends EventBus
{
    /**
     * Class name.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * Instance.
     *
     * @var static
     */
    protected static $instance;
}