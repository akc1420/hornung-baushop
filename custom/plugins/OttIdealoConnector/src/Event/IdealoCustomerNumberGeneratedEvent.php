<?php declare(strict_types=1);

namespace Ott\IdealoConnector\Event;

use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Symfony\Contracts\EventDispatcher\Event;

class IdealoCustomerNumberGeneratedEvent extends Event
{
    private SalesChannelEntity $salesChannelEntity;
    private string $customerNumber;

    public function __construct(string $customerNumber, SalesChannelEntity $salesChannelEntity)
    {
        $this->customerNumber = $customerNumber;
        $this->salesChannelEntity = $salesChannelEntity;
    }

    public function getSalesChannelEntity(): SalesChannelEntity
    {
        return $this->salesChannelEntity;
    }

    public function getCustomerNumber(): string
    {
        return $this->customerNumber;
    }

    public function setCustomerNumber(string $customerNumber): self
    {
        $this->customerNumber = $customerNumber;

        return $this;
    }
}
