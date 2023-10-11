<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\TaskExecution\Events;

use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Interfaces\Priority;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Task;
use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Events\Event;

/**
 * Class QueueItemEnqueuedEvent
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\TaskExecution\Events
 */
class QueueItemEnqueuedEvent extends Event
{
    const CLASS_NAME = __CLASS__;
    /**
     * @var string
     */
    protected $queueName;
    /**
     * @var \Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Task
     */
    protected $task;
    /**
     * @var string
     */
    protected $context;
    /**
     * @var int
     */
    protected $priority;

    /**
     * QueueItemEnqueuedEvent constructor.
     *
     * @param string $queueName
     * @param \Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Task $task
     * @param string $context
     * @param int $priority
     */
    public function __construct($queueName, Task $task, $context = '', $priority = Priority::NORMAL)
    {
        $this->queueName = $queueName;
        $this->task = $task;
        $this->context = $context;
        $this->priority = $priority;
    }

    /**
     * @return string
     */
    public function getQueueName()
    {
        return $this->queueName;
    }

    /**
     * @return \Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Task
     */
    public function getTask()
    {
        return $this->task;
    }

    /**
     * @return string
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }
}
