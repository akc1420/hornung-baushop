<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Webhooks\Tasks;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Webhooks\EventsService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\Tasks\Context\ExecutionContext;
use Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\Tasks\RegisterEventTask as BaseTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\Tasks\SubTasks\EventRegistrationResultRecorder;

class RegisterEventTask extends BaseTask
{
    const CLASS_NAME = __CLASS__;

    /**
     * RegisterReceiverEventsTask constructor.
     */
    public function __construct()
    {
        parent::__construct(new ExecutionContext(EventsService::CLASS_NAME));
    }

    /**
     * @inheritDoc
     */
    protected static function createTask(array $tasks, $initialProgress)
    {
        return new static();
    }

    /**
     * @return array
     */
    protected function getSubTasks()
    {
        return array(
            EventProvider::CLASS_NAME => 10,
            EventDeleter::CLASS_NAME => 30,
            EventRegistrator::CLASS_NAME => 40,
            EventRegistrationResultRecorder::CLASS_NAME => 20,
        );
    }
}