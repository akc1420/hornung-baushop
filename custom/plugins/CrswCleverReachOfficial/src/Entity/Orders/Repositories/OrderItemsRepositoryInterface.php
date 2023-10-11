<?php


namespace Crsw\CleverReachOfficial\Entity\Orders\Repositories;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Order\DTO\OrderItem;

/**
 * Interface OrderItemsRepositoryInterface
 *
 * @package Crsw\CleverReachOfficial\Entity\Orders\Repositories
 */
interface OrderItemsRepositoryInterface
{
    /**
     * Retrieves list of order items for a given order id.
     *
     * @param int | string $orderId
     *
     * @return OrderItem[]
     */
    public function getByOrderId($orderId): array;
}