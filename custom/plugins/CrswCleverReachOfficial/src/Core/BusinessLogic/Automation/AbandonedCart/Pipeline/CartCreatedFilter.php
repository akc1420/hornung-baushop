<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Pipeline;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Contracts\AbandonedCartEntityService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\AbandonedCartTrigger;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Exceptions\FailedToPassFilterException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;

class CartCreatedFilter extends Filter
{
    /**
     * Checks whether the cart is created or not.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\AbandonedCartTrigger $trigger
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Exceptions\FailedToPassFilterException
     */
    public function pass(AbandonedCartTrigger $trigger)
    {
        if ($this->getService()->get() === null) {
            throw new FailedToPassFilterException('Cart is not created.');
        }
    }

    /**
     * Retrieves abandoned cart service.
     *
     * @return AbandonedCartEntityService | object
     */
    private function getService()
    {
        return ServiceRegister::getService(AbandonedCartEntityService::CLASS_NAME);
    }
}