<?php declare(strict_types=1);

namespace Ott\IdealoConnector\Event;

use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Symfony\Contracts\EventDispatcher\Event;

class IdealoOrderLineItemStockUpdatedEvent extends Event
{
    private SalesChannelEntity $salesChannelEntity;
    private string $productId;
    private int $updateQuantity;

    public function __construct(string $productId, int $updateQuantity, SalesChannelEntity $salesChannelEntity)
    {
        $this->salesChannelEntity = $salesChannelEntity;
        $this->productId = $productId;
        $this->updateQuantity = $updateQuantity;
    }

    public function getSalesChannelEntity(): SalesChannelEntity
    {
        return $this->salesChannelEntity;
    }

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function getUpdateQuantity(): int
    {
        return $this->updateQuantity;
    }
}
