<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagSocialShopping\DataAbstractionLayer\Entity;

use Shopware\Core\Content\ProductStream\ProductStreamEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainEntity;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class SocialShoppingSalesChannelEntity extends Entity
{
    use EntityIdTrait;

    protected string $salesChannelId;

    protected ?string $productStreamId = null;

    protected string $salesChannelDomainId;

    protected string $network;

    protected ?array $configuration = null;

    protected ?SalesChannelEntity $salesChannel = null;

    protected ?ProductStreamEntity $productStream = null;

    protected ?SalesChannelDomainEntity $salesChannelDomain = null;

    protected ?string $currencyId;

    protected ?CurrencyEntity $currency = null;

    protected bool $isValidating;

    protected ?\DateTimeInterface $lastValidation = null;

    public function getSalesChannelId(): string
    {
        return $this->salesChannelId;
    }

    public function setSalesChannelId(string $salesChannelId): void
    {
        $this->salesChannelId = $salesChannelId;
    }

    public function getProductStreamId(): ?string
    {
        return $this->productStreamId;
    }

    public function setProductStreamId(?string $productStreamId): void
    {
        $this->productStreamId = $productStreamId;
    }

    public function getSalesChannelDomainId(): string
    {
        return $this->salesChannelDomainId;
    }

    public function setSalesChannelDomainId(string $salesChannelDomainId): void
    {
        $this->salesChannelDomainId = $salesChannelDomainId;
    }

    public function getNetwork(): string
    {
        return $this->network;
    }

    public function setNetwork(string $network): void
    {
        $this->network = $network;
    }

    public function getConfiguration(): ?array
    {
        return $this->configuration;
    }

    public function setConfiguration(array $configuration): void
    {
        $this->configuration = $configuration;
    }

    public function getSalesChannel(): ?SalesChannelEntity
    {
        return $this->salesChannel;
    }

    public function setSalesChannel(?SalesChannelEntity $salesChannel): void
    {
        $this->salesChannel = $salesChannel;
    }

    public function getProductStream(): ?ProductStreamEntity
    {
        return $this->productStream;
    }

    public function setProductStream(?ProductStreamEntity $productStream): void
    {
        $this->productStream = $productStream;
    }

    public function getSalesChannelDomain(): ?SalesChannelDomainEntity
    {
        return $this->salesChannelDomain;
    }

    public function setSalesChannelDomain(?SalesChannelDomainEntity $salesChannelDomain): void
    {
        $this->salesChannelDomain = $salesChannelDomain;
    }

    public function getCurrencyId(): ?string
    {
        return $this->currencyId;
    }

    public function setCurrencyId(?string $currencyId): void
    {
        $this->currencyId = $currencyId;
    }

    public function getCurrency(): ?CurrencyEntity
    {
        return $this->currency;
    }

    public function setCurrency(?CurrencyEntity $currency): void
    {
        $this->currency = $currency;
    }

    /**
     * @deprecated tag:3.0.0, since 2.1.0 use getIsValidating() instead
     */
    public function isValidating(): bool
    {
        return $this->getIsValidating();
    }

    public function getIsValidating(): bool
    {
        return $this->isValidating;
    }

    public function setIsValidating(bool $isValidating): void
    {
        $this->isValidating = $isValidating;
    }

    public function getLastValidation(): ?\DateTimeInterface
    {
        return $this->lastValidation;
    }

    public function setLastValidation(?\DateTimeInterface $lastValidation): void
    {
        $this->lastValidation = $lastValidation;
    }
}
