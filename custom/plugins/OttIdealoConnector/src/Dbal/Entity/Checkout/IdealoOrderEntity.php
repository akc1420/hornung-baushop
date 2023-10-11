<?php declare(strict_types=1);

namespace Ott\IdealoConnector\Dbal\Entity\Checkout;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class IdealoOrderEntity extends Entity
{
    use EntityIdTrait;
    protected string $orderId;
    protected string $idealoTransactionId;

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function setOrderId(string $orderId): self
    {
        $this->orderId = $orderId;

        return $this;
    }

    public function getIdealoTransactionId(): string
    {
        return $this->idealoTransactionId;
    }

    public function setIdealoTransactionId(string $idealoTransactionId): self
    {
        $this->idealoTransactionId = $idealoTransactionId;

        return $this;
    }
}
