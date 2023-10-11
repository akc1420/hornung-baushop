<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\TaskExecution\Events;

use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueItem;

/**
 * Class QueueItemFailedEvent
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\TaskExecution\Events
 */
class QueueItemFailedEvent extends BaseQueueItemEvent
{
    const CLASS_NAME = __CLASS__;

    /**
     * @var string
     */
    protected $failureDescription;

    /**
     * QueueItemFailedEvent constructor.
     *
     * @param QueueItem $queueItem
     * @param string $failureDescription
     */
    public function __construct(QueueItem $queueItem, $failureDescription)
    {
        parent::__construct($queueItem);
        $this->failureDescription = $failureDescription;
    }

    /**
     * @return string
     */
    public function getFailureDescription()
    {
        return $this->failureDescription;
    }
}
