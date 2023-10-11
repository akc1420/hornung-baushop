<?php

namespace Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Events;

use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueItem;
use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Events\Event;

/**
 * Class QueueStatusChangedEvent.
 *
 * @package Crsw\CleverReachOfficial\Core\Infrastructure\Scheduler
 */
class QueueStatusChangedEvent extends Event
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Queue item.
     *
     * @var \Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueItem
     */
    private $queueItem;
    /**
     * Previous state of queue item.
     *
     * @var string
     */
    private $previousState;

    /**
     * TaskProgressEvent constructor.
     *
     * @param \Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueItem $queueItem Queue item with changed status.
     * @param string $previousState Previous state. MUST be one of the states defined as constants in @see QueueItem.
     */
    public function __construct(QueueItem $queueItem, $previousState)
    {
        $this->queueItem = $queueItem;
        $this->previousState = $previousState;
    }

    /**
     * Gets Queue item.
     *
     * @return \Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueItem Queue item.
     */
    public function getQueueItem()
    {
        return $this->queueItem;
    }

    /**
     * Gets previous state.
     *
     * @return string Previous state..
     */
    public function getPreviousState()
    {
        return $this->previousState;
    }
}
