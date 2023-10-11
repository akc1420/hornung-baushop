<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareErpStarter\GoodsReceipt\Model;

use Pickware\DalBundle\Association\Exception\AssociationNotLoadedException;
use Pickware\PickwareErpStarter\Stock\Model\StockCollection;
use Pickware\PickwareErpStarter\Stock\Model\StockMovementCollection;
use Pickware\PickwareErpStarter\Warehouse\Model\WarehouseEntity;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\CashRoundingConfig;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\User\UserEntity;

class GoodsReceiptEntity extends Entity
{
    use EntityIdTrait;

    protected ?CartPrice $price;
    protected ?float $amountTotal;
    protected ?float $amountNet;
    protected ?float $positionPrice;
    protected ?string $taxStatus;
    protected ?string $currencyId;
    protected ?CashRoundingConfig $itemRounding;
    protected ?CashRoundingConfig $totalRounding;
    protected ?CurrencyEntity $currency = null;
    protected ?float $currencyFactor;
    protected string $number;
    protected ?string $comment = null;
    protected ?string $userId = null;
    protected ?UserEntity $user = null;
    protected array $userSnapshot;
    protected ?string $warehouseId = null;
    protected ?WarehouseEntity $warehouse = null;
    protected array $warehouseSnapshot;
    protected ?GoodsReceiptItemCollection $items = null;
    protected ?StockMovementCollection $sourceStockMovements = null;
    protected ?StockMovementCollection $destinationStockMovements = null;
    protected ?StockCollection $stocks = null;

    public function getPrice(): ?CartPrice
    {
        return $this->price;
    }

    public function setPrice(?CartPrice $price): void
    {
        $this->price = $price;
    }

    public function getAmountTotal(): ?float
    {
        return $this->amountTotal;
    }

    public function setAmountTotal(?float $amountTotal): void
    {
        $this->amountTotal = $amountTotal;
    }

    public function getAmountNet(): ?float
    {
        return $this->amountNet;
    }

    public function setAmountNet(?float $amountNet): void
    {
        $this->amountNet = $amountNet;
    }

    public function getPositionPrice(): ?float
    {
        return $this->positionPrice;
    }

    public function setPositionPrice(?float $positionPrice): void
    {
        $this->positionPrice = $positionPrice;
    }

    public function getTaxStatus(): ?string
    {
        return $this->taxStatus;
    }

    public function setTaxStatus(?string $taxStatus): void
    {
        $this->taxStatus = $taxStatus;
    }

    public function getCurrencyId(): ?string
    {
        return $this->currencyId;
    }

    public function setCurrencyId(?string $currencyId): void
    {
        if ($this->currency && $this->currency->getId() !== $currencyId) {
            $this->currency = null;
        }

        $this->currencyId = $currencyId;
    }

    public function getCurrency(): ?CurrencyEntity
    {
        if (!$this->currency) {
            throw new AssociationNotLoadedException('currency', $this);
        }

        return $this->currency;
    }

    public function setCurrency(?CurrencyEntity $currency): void
    {
        if ($currency) {
            $this->currencyId = $currency->getId();
            $this->currency = $currency;
        }
    }

    public function getCurrencyFactor(): ?float
    {
        return $this->currencyFactor;
    }

    public function setCurrencyFactor(?float $currencyFactor): void
    {
        $this->currencyFactor = $currencyFactor;
    }

    public function getItemRounding(): ?CashRoundingConfig
    {
        return $this->itemRounding;
    }

    public function setItemRounding(?CashRoundingConfig $itemRounding): void
    {
        $this->itemRounding = $itemRounding;
    }

    public function getTotalRounding(): ?CashRoundingConfig
    {
        return $this->totalRounding;
    }

    public function setTotalRounding(?CashRoundingConfig $totalRounding): void
    {
        $this->totalRounding = $totalRounding;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function setNumber(string $number): void
    {
        $this->number = $number;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): void
    {
        $this->comment = $comment;
    }

    public function getUserId(): ?string
    {
        return $this->warehouseId;
    }

    public function setUserId(?string $userId): void
    {
        if ($this->user && $this->user->getId() !== $userId) {
            $this->user = null;
        }
        $this->userId = $userId;
    }

    public function getUser(): ?UserEntity
    {
        if (!$this->user && $this->userId) {
            throw new AssociationNotLoadedException('user', $this);
        }

        return $this->user;
    }

    public function setUser(?UserEntity $user): void
    {
        if ($user) {
            $this->userId = $user->getId();
        }
        $this->user = $user;
    }

    public function getUserSnapshot(): array
    {
        return $this->userSnapshot;
    }

    public function setUserSnapshot(array $userSnapshot): void
    {
        $this->userSnapshot = $userSnapshot;
    }

    public function getWarehouseId(): ?string
    {
        return $this->warehouseId;
    }

    public function setWarehouseId(?string $warehouseId): void
    {
        if ($this->warehouse && $this->warehouse->getId() !== $warehouseId) {
            $this->warehouse = null;
        }
        $this->warehouseId = $warehouseId;
    }

    public function getWarehouse(): ?WarehouseEntity
    {
        if ($this->warehouseId && !$this->warehouse) {
            throw new AssociationNotLoadedException('warehouse', $this);
        }

        return $this->warehouse;
    }

    public function setWarehouse(?WarehouseEntity $warehouse): void
    {
        if ($warehouse) {
            $this->warehouseId = $warehouse->getId();
        }
        $this->warehouse = $warehouse;
    }

    public function getWarehouseSnapshot(): array
    {
        return $this->warehouseSnapshot;
    }

    public function setWarehouseSnapshot(array $warehouseSnapshot): void
    {
        $this->warehouseSnapshot = $warehouseSnapshot;
    }

    public function getItems(): GoodsReceiptItemCollection
    {
        if (!$this->items) {
            throw new AssociationNotLoadedException('items', $this);
        }

        return $this->items;
    }

    public function setItems(GoodsReceiptItemCollection $items): void
    {
        $this->items = $items;
    }

    public function getSourceStockMovements(): StockMovementCollection
    {
        if (!$this->sourceStockMovements) {
            throw new AssociationNotLoadedException('sourceStockMovements', $this);
        }

        return $this->sourceStockMovements;
    }

    public function setSourceStockMovements(?StockMovementCollection $sourceStockMovements): void
    {
        $this->sourceStockMovements = $sourceStockMovements;
    }

    public function getDestinationStockMovements(): StockMovementCollection
    {
        if (!$this->destinationStockMovements) {
            throw new AssociationNotLoadedException('destinationStockMovements', $this);
        }

        return $this->destinationStockMovements;
    }

    public function setDestinationStockMovements(?StockMovementCollection $destinationStockMovements): void
    {
        $this->destinationStockMovements = $destinationStockMovements;
    }

    public function getStocks(): StockCollection
    {
        if (!$this->stocks) {
            throw new AssociationNotLoadedException('stocks', $this);
        }

        return $this->stocks;
    }

    public function setStocks(?StockCollection $stocks): void
    {
        $this->stocks = $stocks;
    }
}
