<?php declare(strict_types=1);

namespace Ott\IdealoConnector\Event;

use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Symfony\Contracts\EventDispatcher\Event;

class IdealoCustomerSavedEvent extends Event
{
    private array $customer;
    private SalesChannelEntity $salesChannelEntity;
    private string $customerId;

    public function __construct(string $customerId, array $customer, SalesChannelEntity $salesChannelEntity)
    {
        $this->customerId = $customerId;
        $this->customer = $customer;
        $this->salesChannelEntity = $salesChannelEntity;
    }

    public function getCustomer(): array
    {
        return $this->customer;
    }

    public function getSalesChannelEntity(): SalesChannelEntity
    {
        return $this->salesChannelEntity;
    }

    public function getCustomerId(): string
    {
        return $this->customerId;
    }
}
