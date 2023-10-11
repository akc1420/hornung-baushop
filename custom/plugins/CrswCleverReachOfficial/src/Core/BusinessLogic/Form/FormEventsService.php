<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Form;

use Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\EventsService;

/**
 * Class FormEventsService
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Form
 */
abstract class FormEventsService extends EventsService
{
    const CLASS_NAME = __CLASS__;

    /**
     * @inheritDoc
     */
    public function getType()
    {
        return 'form';
    }
}