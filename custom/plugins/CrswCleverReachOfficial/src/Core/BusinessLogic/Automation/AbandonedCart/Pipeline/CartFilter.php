<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Pipeline;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\AbandonedCartTrigger;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Exceptions\FailedToPassFilterException;

class CartFilter extends Filter
{
    /**
     * Checks whether trigger can pass the filter.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\AbandonedCartTrigger $trigger
     *
     * @return void
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Exceptions\FailedToPassFilterException
     *
     */
    public function pass(AbandonedCartTrigger $trigger)
    {
        if ($trigger->getAbandonedCartData()->getTotal() <= 0) {
            throw new FailedToPassFilterException(
                "Cart {$trigger->getAbandonedCartData()->getTotal()} has insufficient value"
            );
        }
    }
}