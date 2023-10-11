<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareErpStarter\Cache;

use Doctrine\DBAL\Connection;
use Pickware\PickwareErpStarter\Stock\Model\StockMovementDefinition;
use Pickware\PickwareErpStarter\Stock\ProductAvailableStockUpdatedEvent;
use Pickware\PickwareErpStarter\Stock\StockUpdatedForStockMovementsEvent;
use Shopware\Core\Content\Product\SalesChannel\Detail\CachedProductDetailRoute;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Adapter\Cache\CacheInvalidator;
use Shopware\Core\Framework\DataAbstractionLayer\Cache\EntityCacheKeyGenerator;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CacheInvalidationSubscriber implements EventSubscriberInterface
{
    private Connection $connection;
    private CacheInvalidator $cacheInvalidator;

    public function __construct(
        Connection $connection,
        CacheInvalidator $cacheInvalidator
    ) {
        $this->connection = $connection;
        $this->cacheInvalidator = $cacheInvalidator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductAvailableStockUpdatedEvent::class => [
                'onProductAvailableStockUpdated',
                PHP_INT_MIN,
            ],

            StockMovementDefinition::ENTITY_WRITTEN_EVENT => [
                'stockMovementWritten',
                10000, // Set a high priority to execute the invalidation of the cache before any other updates are written
            ],

            StockUpdatedForStockMovementsEvent::class => [
                'onStockUpdatedForStockMovements',
                10000, // Set a high priority to execute the invalidation of the cache before any other updates are written
            ],
        ];
    }

    public function onProductAvailableStockUpdated(ProductAvailableStockUpdatedEvent $event): void
    {
        $this->invalidateProductCache($event->getProductIds());
    }

    public function stockMovementWritten(EntityWrittenEvent $entityWrittenEvent): void
    {
        if ($entityWrittenEvent->getContext()->getVersionId() !== Defaults::LIVE_VERSION) {
            return;
        }

        $productIds = [];
        foreach ($entityWrittenEvent->getWriteResults() as $writeResult) {
            if ($writeResult->getExistence()->exists()) {
                // Updating stock movements is not supported yet
                // In case a stock location is deleted, this code path is also reached. This is because an
                // EntityWrittenEvent is triggered when an entity field gets null-ed because of a SET NULL constraint
                // of a FK.
                continue;
            }
            $payload = $writeResult->getPayload();
            $productIds[] = $payload['productId'];
        }

        $this->invalidateProductStreams($productIds);
    }

    public function onStockUpdatedForStockMovements(StockUpdatedForStockMovementsEvent $event): void
    {
        $productIds = array_values(array_map(
            fn (array $stockMovement) => $stockMovement['productId'],
            $event->getStockMovements(),
        ));
        $this->invalidateProductStreams($productIds);
    }

    private function invalidateProductCache(array $productIds): void
    {
        // Invalidate the storefront api cache if the products stock or reserved stock was updated and in turn the
        // product availability was recalculated. For variant products the variant and main product cache need to be
        // invalidated.
        $parentIds = $this->connection->fetchFirstColumn(
            'SELECT DISTINCT LOWER(HEX(COALESCE(parent_id, id)))
                    FROM product
                    WHERE id in (:productIds) AND version_id = :version',
            [
                'productIds' => array_map('hex2bin', $productIds),
                'version' => hex2bin(Defaults::LIVE_VERSION),
            ],
            [
                'productIds' => Connection::PARAM_STR_ARRAY,
            ],
        );

        $productIds = array_merge($productIds, $parentIds);

        $this->invalidateDetailRoute($productIds);
        $this->invalidateProductIds($productIds);
        $this->invalidateProductStreams($productIds);
    }

    private function invalidateDetailRoute(array $productIds): void
    {
        $this->cacheInvalidator->invalidate(
            array_map([CachedProductDetailRoute::class, 'buildName'], $productIds),
        );
    }

    private function invalidateProductIds(array $productIds): void
    {
        $this->cacheInvalidator->invalidate(
            array_map([EntityCacheKeyGenerator::class, 'buildProductTag'], $productIds),
        );
    }

    private function invalidateProductStreams(array $productIds): void
    {
        if (count($productIds) === 0) {
            return;
        }

        $ids = $this->connection->fetchFirstColumn(
            'SELECT DISTINCT LOWER(HEX(product_stream_id))
             FROM product_stream_mapping
             WHERE product_stream_mapping.product_id IN (:ids)
             AND product_stream_mapping.product_version_id = :version',
            [
                'ids' => array_map('hex2bin', $productIds),
                'version' => hex2bin(Defaults::LIVE_VERSION),
            ],
            [
                'ids' => Connection::PARAM_STR_ARRAY,
            ],
        );

        if (count($ids) === 0) {
            return;
        }

        $this->cacheInvalidator->invalidate(
            array_map([EntityCacheKeyGenerator::class, 'buildStreamTag'], $ids),
        );
    }
}
