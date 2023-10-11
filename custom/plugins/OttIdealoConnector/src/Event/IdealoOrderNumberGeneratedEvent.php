<?php declare(strict_types=1);

namespace Ott\IdealoConnector\Event;

use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Symfony\Contracts\EventDispatcher\Event;

class IdealoOrderNumberGeneratedEvent extends Event
{
    private SalesChannelEntity $salesChannelEntity;
    private string $orderNumber;

    public function __construct(string $orderNumber, SalesChannelEntity $salesChannelEntity)
    {
        $this->orderNumber = $orderNumber;
        $this->salesChannelEntity = $salesChannelEntity;
    }

    public function getSalesChannelEntity(): SalesChannelEntity
    {
        return $this->salesChannelEntity;
    }

    public function getOrderNumber(): string
    {
        return $this->orderNumber;
    }

    public function setOrderNumber(string $orderNumber): self
    {
        $this->orderNumber = $orderNumber;

        return $this;
    }
}
