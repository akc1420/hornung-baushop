<?php declare(strict_types=1);

namespace Ott\IdealoConnector\Event;

use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Symfony\Contracts\EventDispatcher\Event;

class IdealoCustomerBeforeSaveEvent extends Event
{
    private array $customer;
    private SalesChannelEntity $salesChannelEntity;

    public function __construct(array $customer, SalesChannelEntity $salesChannelEntity)
    {
        $this->customer = $customer;
        $this->salesChannelEntity = $salesChannelEntity;
    }

    public function setCustomer(array $customer): self
    {
        $this->customer = $customer;

        return $this;
    }

    public function getCustomer(): array
    {
        return $this->customer;
    }

    public function getSalesChannelEntity(): SalesChannelEntity
    {
        return $this->salesChannelEntity;
    }
}
