<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareErpStarter\Picking;

class ProductPickingRequest
{
    private string $productId;
    private int $quantity;
    private array $pickLocations;
    private array $productSnapshot;
    private bool $isStockManagementDisabled;

    /**
     * @param PickLocation[]|null $pickLocations
     */
    public function __construct(
        string $productId,
        int $quantity,
        array $pickLocations = [],
        array $productSnapshot = [],
        ?bool $isStockManagementDisabled = false
    ) {
        $this->productId = $productId;
        $this->quantity = $quantity;
        $this->pickLocations = $pickLocations;
        $this->productSnapshot = $productSnapshot;
        $this->isStockManagementDisabled = $isStockManagementDisabled;
    }

    public function isEnoughStockAvailable(): bool
    {
        return $this->getTotalQuantityToPick() === $this->getQuantity();
    }

    /**
     * @return int as many items as possible and as few as necessary
     */
    public function getTotalQuantityToPick(): int
    {
        return array_reduce($this->pickLocations, fn (int $sum, PickLocation $pickLocation) => $sum + $pickLocation->getQuantityToPick(), 0);
    }

    public function getStockShortage(): int
    {
        return $this->getQuantity() - $this->getTotalQuantityToPick();
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    /**
     * @return PickLocation[]
     */
    public function getPickLocations(): array
    {
        return $this->pickLocations;
    }

    /**
     * @param PickLocation[] $pickLocations
     */
    public function setPickLocations(array $pickLocations): void
    {
        $this->pickLocations = $pickLocations;
    }

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function getProductSnapshot(): array
    {
        return $this->productSnapshot;
    }

    public function setProductSnapshot(array $productSnapshot): void
    {
        $this->productSnapshot = $productSnapshot;
    }

    public function getIsStockManagementDisabled(): bool
    {
        return $this->isStockManagementDisabled;
    }

    public function setIsStockManagementDisabled(bool $isStockManged): void
    {
        $this->isStockManagementDisabled = $isStockManged;
    }
}
