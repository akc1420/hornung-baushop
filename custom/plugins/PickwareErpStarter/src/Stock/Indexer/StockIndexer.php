<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareErpStarter\Stock\Indexer;

use Pickware\PickwareErpStarter\Stock\ProductAvailableUpdater;
use Pickware\PickwareErpStarter\Stock\ProductReservedStockUpdater;
use Pickware\PickwareErpStarter\Stock\ProductStockUpdater;
use Pickware\PickwareErpStarter\Stock\WarehouseStockInitializer;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\IteratorFactory;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexer;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexingMessage;

class StockIndexer extends EntityIndexer
{
    public const NAME = 'PickwareErp.StockIndexer';

    private ProductDefinition $productDefinition;
    private IteratorFactory $iteratorFactory;
    private ProductStockUpdater $productStockUpdater;
    private ProductReservedStockUpdater $productReservedStockUpdater;
    private ProductAvailableUpdater $productAvailableUpdater;
    private WarehouseStockInitializer $warehouseStockInitializer;

    /**
     * @deprecated next-major argument `ProductAvailableUpdater $productAvailableUpdater` will be removed
     */
    public function __construct(
        ProductDefinition $productDefinition,
        IteratorFactory $iteratorFactory,
        ProductStockUpdater $productStockUpdater,
        ProductReservedStockUpdater $productReservedStockUpdater,
        ProductAvailableUpdater $productAvailableUpdater,
        WarehouseStockInitializer $warehouseStockInitializer
    ) {
        $this->productDefinition = $productDefinition;
        $this->iteratorFactory = $iteratorFactory;
        $this->productStockUpdater = $productStockUpdater;
        $this->productReservedStockUpdater = $productReservedStockUpdater;
        $this->productAvailableUpdater = $productAvailableUpdater;
        $this->warehouseStockInitializer = $warehouseStockInitializer;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function iterate($offset): ?EntityIndexingMessage
    {
        $iterator = $this->iteratorFactory->createIterator($this->productDefinition, $offset);
        // Index 50 products per run
        $iterator->getQuery()->setMaxResults(50);

        $ids = $iterator->fetch();

        if (empty($ids)) {
            return null;
        }

        return new EntityIndexingMessage(array_values($ids), $iterator->getOffset());
    }

    public function update(EntityWrittenContainerEvent $event): ?EntityIndexingMessage
    {
        // Keeping the stock index in sync is done by synchronous subscribers
        // See ProductStockUpdater, ProductReservedStockUpdater, ProductAvailableUpdater
        return null;
    }

    public function handle(EntityIndexingMessage $message): void
    {
        $productIds = $message->getData();

        $productIds = array_unique(array_filter($productIds));
        if (empty($productIds)) {
            return;
        }

        $this->warehouseStockInitializer->ensureProductWarehouseStockForProductsExist($productIds);
        $this->productStockUpdater->recalculateStockFromStockMovementsForProducts($productIds, $message->getContext());
        $this->productStockUpdater->upsertStockEntriesForDefaultBinLocationsOfProducts($productIds);

        // Note: Recalculating the product reserved stock also triggers product available stock, which also triggers product
        // available flag calculation.
        $this->productReservedStockUpdater->recalculateProductReservedStock($productIds);
    }
}
