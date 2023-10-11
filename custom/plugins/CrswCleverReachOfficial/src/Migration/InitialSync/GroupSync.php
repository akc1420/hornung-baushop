<?php


namespace Crsw\CleverReachOfficial\Migration\InitialSync;

use Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\Tasks\RegisterDynamicContentTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Tasks\CacheFormsTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Tasks\CreateDefaultFormTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Tasks\RegisterFormEventsTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\InitialSynchronization\Tasks\Composite\Components\InitialSyncSubTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Mailing\Tasks\CreateDefaultMailing;

class GroupSync extends InitialSyncSubTask
{
    /**
     * GroupSync constructor.
     */
    public function __construct()
    {
        parent::__construct(self::getSubTasks());
    }

    /**
     * @return array
     */
    protected static function getSubTasks()
    {
        return [
            CreateDefaultFormTask::class => 15,
            CacheFormsTask::class => 50,
            CreateDefaultMailing::class => 15,
            RegisterFormEventsTask::class => 5,
            RegisterDynamicContentTask::class => 15,
        ];
    }
}