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

use Pickware\DalBundle\EntityManager;
use Pickware\PickwareErpStarter\Stock\ProductReservedStockUpdater;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\IteratorFactory;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexer;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexingMessage;

/**
 * The reserved stock is calculated based on stocks so this indexer is already part of the StockIndexer. This
 * ReservedStockIndexer should _only_ be run by itself when the plugin is initially installed and the
 * InitializeStockInstallationStep created the initial stock movements and stock values.
 *
 * Note: When running all indexers via the Administration, the reserved stock is calculated twice (by this
 * ReservedStockIndexer _and_ the StockIndexer). That does not end up in wrong results so we ignore this for now.
 */
class ProductReservedStockIndexer extends EntityIndexer
{
    public const NAME = 'PickwareErp.ReservedStockIndexer';

    private EntityManager $entityManager;
    private IteratorFactory $iteratorFactory;
    private ProductReservedStockUpdater $productReservedStockUpdater;

    public function __construct(
        EntityManager $entityManager,
        IteratorFactory $iteratorFactory,
        ProductReservedStockUpdater $productReservedStockUpdater
    ) {
        $this->entityManager = $entityManager;
        $this->iteratorFactory = $iteratorFactory;
        $this->productReservedStockUpdater = $productReservedStockUpdater;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function iterate($offset): ?EntityIndexingMessage
    {
        $iterator = $this->iteratorFactory->createIterator(
            $this->entityManager->getEntityDefinition(ProductDefinition::class),
            $offset,
        );
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
        // Keeping the reserved stock index in sync is done by a synchronous subscriber
        // See ProductReservedStockUpdater
        return null;
    }

    public function handle(EntityIndexingMessage $message): void
    {
        $productIds = $message->getData();
        $productIds = array_unique(array_filter($productIds));
        if (empty($productIds)) {
            return;
        }

        // Note: Recalculating the product reserved stock also triggers product available stock, which also triggers product
        // available flag calculation.
        $this->productReservedStockUpdater->recalculateProductReservedStock($productIds);
    }
}
