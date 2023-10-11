<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Events;

use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Events\EventBus;

/**
 * Class FormEventBus
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Events
 */
class FormEventBus extends EventBus
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