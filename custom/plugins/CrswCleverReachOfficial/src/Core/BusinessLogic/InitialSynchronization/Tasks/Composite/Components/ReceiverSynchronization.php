<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\InitialSynchronization\Tasks\Composite\Components;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\ReceiverSyncTask;

class ReceiverSynchronization extends InitialSyncSubTask
{
    const CLASS_NAME = __CLASS__;

    /**
     * ReceiverSynchronization constructor.
     */
    public function __construct()
    {
        parent::__construct($this->getSubTasks());
    }

    /**
     * Retrieves sub tasks.
     *
     * @return array
     */
    protected function getSubTasks()
    {
        return array(
            ReceiverSyncTask::CLASS_NAME => 100,
        );
    }
}