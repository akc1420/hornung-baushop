<?php

namespace Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Interfaces;

use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\LogData;

/**
 * Interface LoggerAdapter.
 *
 * @package Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Interfaces
 */
interface LoggerAdapter
{
    /**
     * Log message in system
     *
     * @param LogData $data
     */
    public function logMessage(LogData $data);
}
