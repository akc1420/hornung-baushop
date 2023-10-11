<?php declare(strict_types=1);

namespace Ott\IdealoConnector\Dbal;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

class DataPersister
{
    private EntityRepositoryInterface $customerRepository;
    private EntityRepositoryInterface $orderRepository;
    private EntityRepositoryInterface $idealoOrderRepository;
    private Context $context;
    private EntityRepositoryInterface $idealoOrderLineItemStatusRepository;

    public function __construct(
        EntityRepositoryInterface $customerRepository,
        EntityRepositoryInterface $orderRepository,
        EntityRepositoryInterface $idealoOrderRepository,
        EntityRepositoryInterface $idealoOrderLineItemStatusRepository
    )
    {
        $this->customerRepository = $customerRepository;
        $this->orderRepository = $orderRepository;
        $this->idealoOrderRepository = $idealoOrderRepository;
        $this->context = Context::createDefaultContext();
        $this->idealoOrderLineItemStatusRepository = $idealoOrderLineItemStatusRepository;
    }

    public function createCustomer(array $customer): void
    {
        $this->customerRepository->create([$customer], $this->context);
    }

    public function createOrder(array $order): void
    {
        $this->orderRepository->create([$order], $this->context);
    }

    public function createIdealoTransactionId(string $orderId, string $idealoTrackingId): void
    {
        $this->idealoOrderRepository->create([[
            'orderId'             => $orderId,
            'idealoTransactionId' => $idealoTrackingId,
        ]], $this->context);
    }

    public function createLineItemStatus(string $idealoOrderId, string $lineItemId, string $status): void
    {
        $this->idealoOrderLineItemStatusRepository->create([[
            'idealoOrderId' => $idealoOrderId,
            'lineItemId'    => $lineItemId,
            'status'        => $status,
        ]], $this->context);
    }
}
