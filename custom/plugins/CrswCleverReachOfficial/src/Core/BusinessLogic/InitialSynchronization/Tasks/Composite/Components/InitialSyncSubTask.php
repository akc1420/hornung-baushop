<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\InitialSynchronization\Tasks\Composite\Components;

use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\CompositeTask;

abstract class InitialSyncSubTask extends CompositeTask
{
    /**
     * @inheritDoc
     */
    protected function createSubTask($taskKey)
    {
        return new $taskKey;
    }
}