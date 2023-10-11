<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Tasks\Composite;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Tasks\Composite\Components\CompleteAuthTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Tasks\Composite\Components\UpdateUserInfoTask;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\CompositeTask;

class ConnectTask extends CompositeTask
{
    /**
     * ConnectTask constructor.
     */
    public function __construct()
    {
        parent::__construct(
            array(
                UpdateUserInfoTask::CLASS_NAME => 70,
                CompleteAuthTask::CLASS_NAME => 30,
            )
        );
    }

    /**
     * @inheritDoc
     */
    protected function createSubTask($taskKey)
    {
        return new $taskKey;
    }
}