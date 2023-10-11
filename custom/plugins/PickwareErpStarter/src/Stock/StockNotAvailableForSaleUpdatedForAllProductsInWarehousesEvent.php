<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareErpStarter\Stock;

class StockNotAvailableForSaleUpdatedForAllProductsInWarehousesEvent
{
    /**
     * @var string[]
     */
    private array $warehouseIds;

    /**
     * @var bool true, if the warehouses changed from being online to offline: the stock not available for sale is now increased
     *  for all products in these warehouses.
     *  false, if the warehouses changed from being offline to online: the stock not available for sale is now decreased
     *  for all products in these warehouses.
     */
    private bool $isStockNotAvailableForSaleIncrease;

    public function __construct(array $warehouseIds, bool $isStockNotAvailableForSaleIncrease)
    {
        $this->warehouseIds = $warehouseIds;
        $this->isStockNotAvailableForSaleIncrease = $isStockNotAvailableForSaleIncrease;
    }

    public function getWarehouseIds(): array
    {
        return $this->warehouseIds;
    }

    public function isStockNotAvailableForSaleIncrease(): bool
    {
        return $this->isStockNotAvailableForSaleIncrease;
    }
}
