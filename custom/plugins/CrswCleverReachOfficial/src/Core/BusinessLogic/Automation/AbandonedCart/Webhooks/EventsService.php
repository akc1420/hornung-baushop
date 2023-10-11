<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Webhooks;

use Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\EventsService as BaseService;

abstract class EventsService extends BaseService
{
    const CLASS_NAME = __CLASS__;

    /**
     * Provides event type. One of [form | receiver]
     *
     * @return string
     */
    public function getType()
    {
        return 'automation';
    }
}