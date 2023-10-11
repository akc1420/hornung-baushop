<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Contracts;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\AbandonedCart;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\AbandonedCartSubmit;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\AbandonedCartTrigger;

interface AbandonedCartService
{
    const CLASS_NAME = __CLASS__;

    /**
     * Retrieves abandoned cart automation name.
     *
     * @return string
     */
    public function getAutomationName();

    /**
     * Store id used to identify automation chain.
     *
     * @return string
     */
    public function getStoreId();

    /**
     * Creates abandoned cart.
     *
     * @param AbandonedCartSubmit $cartData
     *
     * @return AbandonedCart
     *
     */
    public function create(AbandonedCartSubmit $cartData);

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
    public function delete($id);

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
    public function enable($id);

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
    public function disable($id);

    /**
     * Triggers abandoned cart automation.
     *
     * @param AbandonedCartTrigger $trigger
     *
     * @return void
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Exceptions\FailedToTriggerAbandonedCartException
     */
    public function trigger(AbandonedCartTrigger $trigger);
}