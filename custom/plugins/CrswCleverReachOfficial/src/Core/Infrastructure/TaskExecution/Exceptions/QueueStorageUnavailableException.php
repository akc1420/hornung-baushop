<?php

namespace Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions;

use Crsw\CleverReachOfficial\Core\Infrastructure\Exceptions\BaseException;

/**
 * Class QueueStorageUnavailableException.
 *
 * @package Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions
 */
class QueueStorageUnavailableException extends BaseException
{
    /**
     * QueueStorageUnavailableException constructor.
     *
     * @param string $message Exception message.
     * @param \Throwable $previous Exception instance that was thrown.
     */
    public function __construct($message = '', $previous = null)
    {
        parent::__construct(trim($message . ' Queue storage failed to save item.'), 0, $previous);
    }
}
