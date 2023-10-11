<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Tasks\Trigger\Filter;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\DTO\Trigger;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Entities\AutomationRecord;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Exceptions\FailedToPassFilterException;

/**
 * Class CartDataFilter
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Tasks\Trigger\Filter
 */
class CartDataFilter extends Filter
{
    public function pass(AutomationRecord $record, Trigger $cartData)
    {
        if ($cartData->getCart()->getTotal() <= 0.0) {
            throw new FailedToPassFilterException('Total value of a cart must be greater than zero.');
        }
    }
}