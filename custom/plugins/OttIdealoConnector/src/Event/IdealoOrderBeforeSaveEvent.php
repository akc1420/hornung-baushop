<?php declare(strict_types=1);

namespace Ott\IdealoConnector\Event;

use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Symfony\Contracts\EventDispatcher\Event;

class IdealoOrderBeforeSaveEvent extends Event
{
    private array $orderItem;
    private SalesChannelEntity $salesChannelEntity;

    public function __construct(array $orderItem, SalesChannelEntity $salesChannelEntity)
    {
        $this->orderItem = $orderItem;
        $this->salesChannelEntity = $salesChannelEntity;
    }

    public function setOrderItem(array $orderItem): self
    {
        $this->orderItem = $orderItem;

        return $this;
    }

    public function getOrderItem(): array
    {
        return $this->orderItem;
    }

    public function getSalesChannelEntity(): SalesChannelEntity
    {
        return $this->salesChannelEntity;
    }
}
