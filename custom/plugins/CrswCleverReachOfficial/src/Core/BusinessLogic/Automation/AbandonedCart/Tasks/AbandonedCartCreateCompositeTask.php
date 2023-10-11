<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Tasks;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Webhooks\Tasks\RegisterEventTask;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\CompositeTask;

class AbandonedCartCreateCompositeTask extends CompositeTask
{
    public function __construct()
    {
        parent::__construct($this->getTasks());
    }

    protected static function createTask(array $tasks, $initialProgress)
    {
        return new static();
    }

    /**
     * Instantiates task.
     *
     * @param string $taskKey
     *
     * @return \Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Task
     */
    protected function createSubTask($taskKey)
    {
        return new $taskKey;
    }

    /**
     * Retrieves sub tasks.
     *
     * @return array
     */
    protected function getTasks()
    {
        return array(
            AbandonedCartCreateTask::CLASS_NAME => 30,
            RegisterEventTask::CLASS_NAME => 70,
        );
    }
}