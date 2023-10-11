<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Events;

use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Events\EventBus;

/**
 * Class AuthorizationEventBus
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Events
 */
class AuthorizationEventBus extends EventBus
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
