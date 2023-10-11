<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareErpStarter\Product\Model;

use Pickware\DalBundle\Association\Exception\AssociationNotLoadedException;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class PickwareProductEntity extends Entity
{
    use EntityIdTrait;

    protected string $productId;
    protected ?ProductEntity $product = null;
    protected ?int $reorderPoint;
    protected int $incomingStock;
    protected int $reservedStock;
    protected int $stockNotAvailableForSale;
    protected bool $isStockManagementDisabled;

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function setProductId(string $productId): void
    {
        if ($this->product && $this->product->getId() !== $productId) {
            $this->product = null;
        }
        $this->productId = $productId;
    }

    public function getProduct(): ProductEntity
    {
        if (!$this->product) {
            throw new AssociationNotLoadedException('product', $this);
        }

        return $this->product;
    }

    public function setProduct(?ProductEntity $product): void
    {
        if ($product) {
            $this->productId = $product->getId();
        }
        $this->product = $product;
    }

    public function getReorderPoint(): ?int
    {
        return $this->reorderPoint;
    }

    public function setReorderPoint(?int $reorderPoint): void
    {
        $this->reorderPoint = $reorderPoint;
    }

    public function getIncomingStock(): int
    {
        return $this->incomingStock;
    }

    public function setIncomingStock(int $incomingStock): void
    {
        $this->incomingStock = $incomingStock;
    }

    public function getIsStockManagementDisabled(): bool
    {
        return $this->isStockManagementDisabled;
    }

    public function setIsStockManagementDisabled(bool $isStockManagementDisabled): void
    {
        $this->isStockManagementDisabled = $isStockManagementDisabled;
    }

    public function getReservedStock(): int
    {
        return $this->reservedStock;
    }

    public function setReservedStock(int $reservedStock): void
    {
        $this->reservedStock = $reservedStock;
    }

    public function getStockNotAvailableForSale(): int
    {
        return $this->stockNotAvailableForSale;
    }

    public function setStockNotAvailableForSale(int $stockNotAvailableForSale): void
    {
        $this->stockNotAvailableForSale = $stockNotAvailableForSale;
    }
}
