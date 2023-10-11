<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Contracts\AbandonedCartEntityService as BaseService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\AbandonedCart;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\ConfigurationManager;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;

class AbandonedCartEntityService implements BaseService
{
    /**
     * Persists abandoned cart automation information.
     *
     * @param AbandonedCart|null $data
     *
     * @return void
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function set(AbandonedCart $data = null)
    {
        $data = $data ? $data->toArray() : null;

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->getConfigManager()->saveConfigValue('abandonedCart', $data);
    }

    /**
     * Retrieves abandoned cart persisted automation information.
     *
     * @return AbandonedCart|null
     *
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function get()
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $cart = $this->getConfigManager()->getConfigValue('abandonedCart');
        if ($cart !== null) {
            $cart = AbandonedCart::fromArray($cart);
        }

        return $cart;
    }

    /**
     * Persists the store id used when creating the automation chain.
     *
     * @param string $id
     *
     * @return void
     *
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function setStoreId($id)
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->getConfigManager()->saveConfigValue('abandonedCartStoreId', $id);
    }
    /**
     * Retrieves store id.
     *
     * @return string
     *
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function getStoreId()
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        return $this->getConfigManager()->getConfigValue('abandonedCartStoreId', '');
    }

    /**
     * @return ConfigurationManager | object
     */
    private function getConfigManager()
    {
        return ServiceRegister::getService(ConfigurationManager::CLASS_NAME);
    }
}