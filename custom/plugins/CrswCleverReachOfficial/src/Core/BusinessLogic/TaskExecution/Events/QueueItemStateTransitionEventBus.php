<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\TaskExecution\Events;

use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Events\EventBus;

/**
 * Class QueueItemStateTransitionEventBus
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\TaskExecution\Events
 */
class QueueItemStateTransitionEventBus extends EventBus
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
