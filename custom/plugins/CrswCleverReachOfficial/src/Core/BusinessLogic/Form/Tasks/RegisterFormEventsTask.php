<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Tasks;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\FormEventsService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\Tasks\Context\ExecutionContext;
use Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\Tasks\RegisterEventTask;

class RegisterFormEventsTask extends RegisterEventTask
{
    const CLASS_NAME = __CLASS__;

    /**
     * RegisterFormEventsTask constructor.
     */
    public function __construct()
    {
        parent::__construct(new ExecutionContext(FormEventsService::CLASS_NAME));
    }

    protected static function createTask(array $tasks, $initialProgress)
    {
        return new static();
    }
}