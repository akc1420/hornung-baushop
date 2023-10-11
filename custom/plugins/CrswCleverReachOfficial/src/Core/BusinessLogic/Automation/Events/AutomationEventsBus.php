<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\Events;

use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Events\EventBus;

class AutomationEventsBus extends EventBus
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