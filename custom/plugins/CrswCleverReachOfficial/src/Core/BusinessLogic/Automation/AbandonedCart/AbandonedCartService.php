<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Contracts\AbandonedCartService as BaseService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\AbandonedCart;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\AbandonedCartSubmit;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\AbandonedCartTrigger;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Exceptions\FailedToCreateAbandonedCartException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Exceptions\FailedToTriggerAbandonedCartException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Http\Proxy;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;

abstract class AbandonedCartService implements BaseService
{
    /**
     * Creates abandoned cart.
     *
     * @param AbandonedCartSubmit $cartData
     *
     * @return AbandonedCart
     *
     */
    public function create(AbandonedCartSubmit $cartData)
    {
        try {
            $abandonedCart = $this->getProxy()->createAbandonedCartChain($cartData);
        } catch (\Exception $e) {
            throw new FailedToCreateAbandonedCartException($e->getMessage(), $e->getCode(), $e);
        }

        return $abandonedCart;
    }

    /**
     * Deletes abandoned cart.
     *
     * @NOTE NOT YET SUPPORTED BY THE API
     *
     * @param string $id
     *
     * @return void
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Exceptions\FailedToDeleteAbandonedCartException
     */
    public function delete($id)
    {
        throw new \RuntimeException('Method not supported.');
    }

    /**
     * Enables abandoned cart.
     *
     * @NOTE NOT YET SUPPORTED BY THE API
     *
     * @param string $id
     *
     * @return void
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Exceptions\FailedToEnableAbandonedCartException
     */
    public function enable($id)
    {
        throw new \RuntimeException('Method not supported.');
    }

    /**
     * Disables abandoned cart.
     *
     * @NOTE NOT YET SUPPORTED BY THE API
     *
     * @param string $id
     *
     * @return void
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Exceptions\FailedToDisableAbandonedCartException
     */
    public function disable($id)
    {
        throw new \RuntimeException('Method not supported.');
    }

    /**
     * Triggers abandoned cart automation.
     *
     * @param AbandonedCartTrigger $trigger
     *
     * @return void
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Exceptions\FailedToTriggerAbandonedCartException
     */
    public function trigger(AbandonedCartTrigger $trigger)
    {
        try {
            $this->getProxy()->triggerAbandonedCart($trigger);
        } catch (\Exception $e) {
            throw new FailedToTriggerAbandonedCartException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Retrieves automation proxy.
     *
     * @return Proxy | object
     */
    private function getProxy()
    {
        return ServiceRegister::getService(Proxy::CLASS_NAME);
    }
}