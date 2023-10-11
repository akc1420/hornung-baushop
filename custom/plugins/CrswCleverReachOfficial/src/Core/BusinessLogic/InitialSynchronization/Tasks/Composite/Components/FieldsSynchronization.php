<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\InitialSynchronization\Tasks\Composite\Components;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Field\Tasks\CreateFieldsTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Segment\Tasks\CreateSegmentsTask;

/**
 * Class FieldsSynchronization
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\InitialSynchronization\Tasks\Composite\Components
 */
class FieldsSynchronization extends InitialSyncSubTask
{
    const CLASS_NAME = __CLASS__;

    /**
     * FieldsSynchronization constructor.
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
            CreateFieldsTask::CLASS_NAME => 30,
            CreateSegmentsTask::CLASS_NAME => 70,
        );
    }
}