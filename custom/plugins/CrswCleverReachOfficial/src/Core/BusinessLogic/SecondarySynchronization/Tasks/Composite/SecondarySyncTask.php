<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\SecondarySynchronization\Tasks\Composite;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\ReceiverSyncTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\SyncSettings\Tasks\UpdateSyncSettingsTask;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\CompositeTask;

class SecondarySyncTask extends CompositeTask
{
    /**
     * SecondarySyncTask constructor.
     */
    public function __construct()
    {
        parent::__construct($this->getSubTasks());
    }

    /**
     * @inheritDoc
     */
    protected function createSubTask($taskKey)
    {
        return new $taskKey;
    }

    /**
     * Returns list of subtasks
     *
     * @return array
     */
    protected function getSubTasks()
    {
        return array(
            UpdateSyncSettingsTask::CLASS_NAME => 5,
            ReceiverSyncTask::CLASS_NAME => 95,
        );
    }
}