<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\TaskExecution\Events;

use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueItem;
use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Events\Event;

/**
 * Class QueueItemStartedEvent
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\TaskExecution\Events
 */
class QueueItemStartedEvent extends Event
{
    const CLASS_NAME = __CLASS__;

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
