<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\TaskExecution\Events;

use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueItem;
use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Events\Event;

/**
 * Class BaseQueueItemEvent
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\TaskExecution\Events
 */
abstract class BaseQueueItemEvent extends Event
{
    /**
     * @var QueueItem
     */
    protected $queueItem;

    /**
     * QueueItemStartedEvent constructor.
     *
     * @param QueueItem $queueItem
     */
    public function __construct(QueueItem $queueItem)
    {
        $this->queueItem = $queueItem;
    }

    /**
     * @return QueueItem
     */
    public function getQueueItem()
    {
        return $this->queueItem;
    }
}