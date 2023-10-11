<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareErpStarter\OrderShipping;

use JsonSerializable;
use Pickware\PickwareErpStarter\StockApi\StockLocationReference;

/**
 * Keep this object immutable
 */
class ProductQuantityLocation implements JsonSerializable
{
    private string $productId;
    private int $quantity;
    private StockLocationReference $stockLocationReference;

    public function __construct(StockLocationReference $locationReference, string $productId, int $quantity)
    {
        $this->stockLocationReference = $locationReference;
        $this->productId = $productId;
        $this->quantity = $quantity;
    }

    /**
     * @deprecated tag:next-major Will be removed to make the class immutable
     */
    public function setProductId(string $productId): self
    {
        $this->productId = $productId;

        return $this;
    }

    public function getProductId(): string
    {
        return $this->productId;
    }

    /**
     * @deprecated tag:next-major Will be removed to make the class immutable
     */
    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * @deprecated tag:next-major Will be removed to make the class immutable
     */
    public function setStockLocationReference(StockLocationReference $stockLocationReference): self
    {
        $this->stockLocationReference = $stockLocationReference;

        return $this;
    }

    public function getStockLocationReference(): StockLocationReference
    {
        return $this->stockLocationReference;
    }

    public function jsonSerialize(): array
    {
        return [
            'productId' => $this->productId,
            'quantity' => $this->quantity,
            'stockLocation' => $this->stockLocationReference->jsonSerialize(),
        ];
    }
}
