<?php declare(strict_types=1);

namespace Ott\IdealoConnector\Event;

use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Symfony\Contracts\EventDispatcher\Event;

class IdealoOrderLineItemSavedEvent extends Event
{
    private array $orderLineItem;
    private SalesChannelEntity $salesChannelEntity;
    private string $orderLineItemId;

    public function __construct(string $orderLineItemId, array $orderLineItem, SalesChannelEntity $salesChannelEntity)
    {
        $this->orderLineItemId = $orderLineItemId;
        $this->orderLineItem = $orderLineItem;
        $this->salesChannelEntity = $salesChannelEntity;
    }

    public function getOrderLineItem(): array
    {
        return $this->orderLineItem;
    }

    public function getSalesChannelEntity(): SalesChannelEntity
    {
        return $this->salesChannelEntity;
    }

    public function getOrderLineItemId(): string
    {
        return $this->orderLineItemId;
    }
}
