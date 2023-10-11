<?php declare(strict_types=1);

namespace Cbax\ModulAnalytics\Core\Content\Analytics;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class ProductImpressionsEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $productId;

    /**
     * @var string
     */
    protected $salesChannelId;

    /**
     * @var string|null
     */
    protected $customerGroupId;

    /**
     * @var \DateTimeInterface|null
     */
    protected $date;

    /**
     * @var int
     */
    protected $impressions;

    /**
     * @var string
     */
    protected $deviceType;

    /**
     * @return string
     */
    public function getProductId(): string
    {
        return $this->productId;
    }

    /**
     * @param string $productId
     */
    public function setProductId(string $productId): void
    {
        $this->productId = $productId;
    }

    /**
     * @return string
     */
    public function getSalesChannelId(): string
    {
        return $this->salesChannelId;
    }

    /**
     * @param string $salesChannelId
     */
    public function setSalesChannelId(string $salesChannelId): void
    {
        $this->salesChannelId = $salesChannelId;
    }

    /**
     * @return string|null
     */
    public function getCustomerGroupId(): ?string
    {
        return $this->customerGroupId;
    }

    /**
     * @param string|null $customerGroupId
     */
    public function setCustomerGroupId(?string $customerGroupId): void
    {
        $this->customerGroupId = $customerGroupId;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    /**
     * @param \DateTimeInterface|null $date
     */
    public function setDate(?\DateTimeInterface $date): void
    {
        $this->date = $date;
    }

    /**
     * @return int
     */
    public function getImpressions(): int
    {
        return $this->impressions;
    }

    /**
     * @param int $impressions
     */
    public function setImpressions(int $impressions): void
    {
        $this->impressions = $impressions;
    }

    /**
     * @return string
     */
    public function getDeviceType(): string
    {
        return $this->deviceType;
    }

    /**
     * @param string $deviceType
     */
    public function setDeviceType(string $deviceType): void
    {
        $this->deviceType = $deviceType;
    }
}
