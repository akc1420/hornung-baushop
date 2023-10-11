<?php

namespace Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Interfaces;

use Crsw\CleverReachOfficial\Core\Infrastructure\Serializer\Interfaces\Serializable;

/**
 * Interface Runnable.
 *
 * @package Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Interfaces
 */
interface Runnable extends Serializable
{
    /**
     * Starts runnable run logic
     */
    public function run();
}
