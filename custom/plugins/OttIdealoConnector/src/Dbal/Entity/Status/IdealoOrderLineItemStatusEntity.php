<?php declare(strict_types=1);

namespace Ott\IdealoConnector\Dbal\Entity\Status;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class IdealoOrderLineItemStatusEntity extends Entity
{
    use EntityIdTrait;
    private string $idealoOrderId;
    private string $lineItemId;
    private string $status;

    public function getIdealoOrderId(): string
    {
        return $this->idealoOrderId;
    }

    public function setIdealoOrderId(string $idealoOrderId): self
    {
        $this->idealoOrderId = $idealoOrderId;

        return $this;
    }

    public function getLineItemId(): string
    {
        return $this->lineItemId;
    }

    public function setLineItemId(string $lineItemId): self
    {
        $this->lineItemId = $lineItemId;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }
}
