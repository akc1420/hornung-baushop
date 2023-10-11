<?php


namespace Crsw\CleverReachOfficial\Service\BusinessLogic\Orders;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Order\Contracts\OrderService as BaseOrderService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Order\DTO\OrderItem;
use Crsw\CleverReachOfficial\Core\BusinessLogic\SyncSettings\Contracts\SyncSettingsService;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Entity\Orders\Repositories\OrderItemsRepositoryInterface;
use DateTime;

/**
 * Class OrderService
 *
 * @package Crsw\CleverReachOfficial\Service\BusinessLogic\Orders
 */
class OrderService implements BaseOrderService
{
    /**
     * @var OrderItemsRepositoryInterface
     */
    private $orderItemsRepository;

    /**
     * OrderService constructor.
     *
     * @param OrderItemsRepositoryInterface $orderItemsRepository
     */
    public function __construct(OrderItemsRepositoryInterface $orderItemsRepository)
    {
        $this->orderItemsRepository = $orderItemsRepository;
    }

    /**
     * Checks whether order items can be synced or not.
     *
     * @return bool Flag that indicates whether order items can be synced or not.
     */
    public function canSynchronizeOrderItems(): bool
    {
        /** @var SyncSettingsService $syncSettingsService */
        $syncSettingsService = ServiceRegister::getService(SyncSettingsService::CLASS_NAME);
        $enabledServices = $syncSettingsService->getEnabledServices();

        foreach ($enabledServices as $enabledService) {
            if ($enabledService->getUuid() === 'buyer-service') {
                return true;
            }
        }

        return false;
    }

    /**
     * Retrieves list of order items for a given order id.
     *
     * @param string | int $orderId Order identifier.
     *
     * @return OrderItem[]
     */
    public function getOrderItems($orderId): array
    {
        return $this->orderItemsRepository->getByOrderId($orderId);
    }

    /**
     * Get list of order items for given customer email.
     *
     * @param string $email
     * @param DateTime|null $date
     *
     * @return OrderItem[]
     */
    public function getOrderItemsByCustomerEmail(string $email, DateTime $date = null): array
    {
        $ordersBeforeGivenDate = false;

        if ($date) {
            $ordersBeforeGivenDate = true;
        } else {
            $date = new DateTime();
            $date->modify('-1 years')->format('Y-m-d');
        }

        return $this->orderItemsRepository->getOrderItemsByEmail($email, $ordersBeforeGivenDate, $date);
    }

    /**
     * Provides order source that will be attached to receiver during export.
     *
     * @param mixed $orderId
     *
     * @return string
     */
    public function getOrderSource($orderId): string
    {
        return $this->orderItemsRepository->getOrderSource($orderId);
    }
}