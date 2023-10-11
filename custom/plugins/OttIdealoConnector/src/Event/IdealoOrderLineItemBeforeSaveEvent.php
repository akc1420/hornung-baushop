<?php declare(strict_types=1);

namespace Ott\IdealoConnector\Event;

use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Symfony\Contracts\EventDispatcher\Event;

class IdealoOrderLineItemBeforeSaveEvent extends Event
{
    private array $orderLineItem;
    private SalesChannelEntity $salesChannelEntity;

    public function __construct(array $orderLineItem, SalesChannelEntity $salesChannelEntity)
    {
        $this->orderLineItem = $orderLineItem;
        $this->salesChannelEntity = $salesChannelEntity;
    }

    public function setOrderLineItem(array $orderLineItem): self
    {
        $this->orderLineItem = $orderLineItem;

        return $this;
    }

    public function getOrderLineItem(): array
    {
        return $this->orderLineItem;
    }

    public function getSalesChannelEntity(): SalesChannelEntity
    {
        return $this->salesChannelEntity;
    }
}
