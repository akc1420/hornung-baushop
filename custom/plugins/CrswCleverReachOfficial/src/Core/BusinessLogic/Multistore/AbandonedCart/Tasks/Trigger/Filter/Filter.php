<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Tasks\Trigger\Filter;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\DTO\Trigger;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Entities\AutomationRecord;

abstract class Filter
{
    /**
     * Checks if the record and cart data satisfy necessary requirements
     * before the mail can be sent.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Entities\AutomationRecord $record
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\DTO\Trigger $cartData
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Exceptions\FailedToPassFilterException
     *
     * @return void
     */
    abstract public function pass(AutomationRecord $record, Trigger $cartData);
}