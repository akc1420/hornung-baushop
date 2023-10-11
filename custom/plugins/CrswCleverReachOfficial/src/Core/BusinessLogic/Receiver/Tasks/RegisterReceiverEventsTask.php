<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\ReceiverEventsService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\Tasks\Context\ExecutionContext;
use Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\Tasks\RegisterEventTask;

class RegisterReceiverEventsTask extends RegisterEventTask
{
    const CLASS_NAME = __CLASS__;

    /**
     * RegisterReceiverEventsTask constructor.
     */
    public function __construct()
    {
        parent::__construct(new ExecutionContext(ReceiverEventsService::CLASS_NAME));
    }

    /**
     * @inheritDoc
     */
    protected static function createTask(array $tasks, $initialProgress)
    {
        return new static();
    }
}