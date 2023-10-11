<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Events;

use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Events\EventBus as BaseEventBus;

/**
 * Class ReceiverEventBus
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Events
 */
class ReceiverEventBus extends BaseEventBus
{
    /**
     * Class name.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * Singleton instance of this class.
     *
     * @var static
     */
    protected static $instance;
}