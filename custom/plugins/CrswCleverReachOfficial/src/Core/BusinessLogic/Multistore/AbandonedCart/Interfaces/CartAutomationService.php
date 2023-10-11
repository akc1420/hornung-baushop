<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Interfaces;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Entities\CartAutomation;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Exceptions\FailedToCreateCartException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Exceptions\FailedToDeleteCartException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Exceptions\FailedToUpdateCartException;

/**
 * Interface CartAutomationService
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Interfaces
 */
interface CartAutomationService
{
    const CLASS_NAME = __CLASS__;

    /**
     * Creates cart automation.
     *
     * @param string $storeId
     * @param string $name
     * @param string $source
     * @param array $settings
     *
     * @return CartAutomation
     *
     * @throws FailedToCreateCartException
     */
    public function create($storeId, $name, $source, array $settings);

    /**
     * Updates cart.
     *
     * @param CartAutomation $cart
     *
     * @return CartAutomation
     *
     * @throws FailedToUpdateCartException
     */
    public function update(CartAutomation $cart);

    /**
     * Provides cart identified by id.
     *
     * @param int $id
     *
     * @return CartAutomation | null
     */
    public function find($id);

    /**
     * Provides carts identified by query.
     *
     * @param array $query
     *
     * @return CartAutomation[]
     */
    public function findBy(array $query);

    /**
     * Deletes cart identified by id.
     *
     * @param int $id
     *
     * @return void
     *
     * @throws FailedToDeleteCartException
     */
    public function delete($id);

    /**
     * Deletes carts identified by query.
     *
     * @param array $query
     *
     * @return void
     *
     * @throws FailedToDeleteCartException
     */
    public function deleteBy(array $query);
}