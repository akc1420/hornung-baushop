<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Contracts;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\AbandonedCart;

interface AbandonedCartEntityService
{
    const CLASS_NAME = __CLASS__;

    /**
     * Persists abandoned cart automation information.
     *
     * @param AbandonedCart|null $data
     *
     * @return void
     */
    public function set(AbandonedCart $data = null);

    /**
     * Retrieves abandoned cart persisted automation information.
     *
     * @return AbandonedCart|null
     */
    public function get();

    /**
     * Persists the store id used when creating the automation chain.
     *
     * @param string $id
     *
     * @return void
     */
    public function setStoreId($id);

    /**
     * Retrieves store id.
     *
     * @return string
     */
    public function getStoreId();
}