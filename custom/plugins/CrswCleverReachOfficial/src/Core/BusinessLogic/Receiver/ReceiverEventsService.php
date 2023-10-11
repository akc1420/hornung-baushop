<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver;

use Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\EventsService;

abstract class ReceiverEventsService extends EventsService
{
    const CLASS_NAME = __CLASS__;

    public function getType()
    {
        return 'receiver';
    }
}