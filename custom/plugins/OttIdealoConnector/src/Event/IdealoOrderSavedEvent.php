<?php declare(strict_types=1);

namespace Ott\IdealoConnector\Event;

use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Symfony\Contracts\EventDispatcher\Event;

class IdealoOrderSavedEvent extends Event
{
    private array $orderItem;
    private SalesChannelEntity $salesChannelEntity;
    private string $orderId;

    public function __construct(string $orderId, array $orderItem, SalesChannelEntity $salesChannelEntity)
    {
        $this->orderId = $orderId;
        $this->orderItem = $orderItem;
        $this->salesChannelEntity = $salesChannelEntity;
    }

    public function getOrderItem(): array
    {
        return $this->orderItem;
    }

    public function getSalesChannelEntity(): SalesChannelEntity
    {
        return $this->salesChannelEntity;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }
}
