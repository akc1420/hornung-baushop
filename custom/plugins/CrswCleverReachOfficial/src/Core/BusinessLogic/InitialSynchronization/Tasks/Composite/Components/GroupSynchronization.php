<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\InitialSynchronization\Tasks\Composite\Components;

use Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\Tasks\RegisterDynamicContentTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Tasks\CacheFormsTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Tasks\CreateDefaultFormTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Tasks\RegisterFormEventsTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Group\Tasks\CreateGroupTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Mailing\Tasks\CreateDefaultMailing;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\RegisterReceiverEventsTask;

/**
 * Class GroupSynchronization
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\InitialSynchronization\Tasks\Composite\Components
 */
class GroupSynchronization extends InitialSyncSubTask
{
    const CLASS_NAME = __CLASS__;

    /**
     * GroupSynchronization constructor.
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
            CreateGroupTask::CLASS_NAME => 10,
            CreateDefaultFormTask::CLASS_NAME => 15,
            CacheFormsTask::CLASS_NAME => 45,
            CreateDefaultMailing::CLASS_NAME => 10,
            RegisterReceiverEventsTask::CLASS_NAME => 5,
            RegisterFormEventsTask::CLASS_NAME => 5,
            RegisterDynamicContentTask::CLASS_NAME => 10,
        );
    }
}
