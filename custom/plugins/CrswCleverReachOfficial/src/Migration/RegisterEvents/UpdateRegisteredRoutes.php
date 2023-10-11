<?php

namespace Crsw\CleverReachOfficial\Migration\RegisterEvents;

use Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\Tasks\RegisterDynamicContentTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Tasks\RegisterFormEventsTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\RegisterReceiverEventsTask;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\CompositeTask;

class UpdateRegisteredRoutes extends CompositeTask
{
    public function __construct()
    {
        parent::__construct($this->getSubTasks());
    }

    protected function createSubTask($taskKey)
    {
        return new $taskKey;
    }

    private function getSubTasks(): array
    {
        return [
            RegisterReceiverEventsTask::class => 35,
            RegisterFormEventsTask::class => 35,
            RegisterDynamicContentTask::class => 30,
        ];
    }
}