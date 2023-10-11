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

use Doctrine\DBAL\Connection;
use Pickware\DalBundle\RetryableTransaction;
use Pickware\PickwareErpStarter\Product\PickwareProductInitializer;
use Shopware\Core\Defaults;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProductAvailableStockUpdater implements EventSubscriberInterface
{
    private Connection $db;
    private PickwareProductInitializer $pickwareProductInitializer;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        Connection $db,
        PickwareProductInitializer $pickwareProductInitializer,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->db = $db;
        $this->pickwareProductInitializer = $pickwareProductInitializer;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * available stock = product stock - reserved stock - stock not available for sale
     *
     * If any of the 3 stock values on the right changes, we need to recalculate the available stock.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            StockUpdatedForStockMovementsEvent::class => 'productStockUpdated',
            ProductReservedStockUpdatedEvent::class => 'productReservedStockUpdated',
            StockNotAvailableForSaleUpdatedEvent::class => 'stockNotAvailableForSaleUpdated',
            StockNotAvailableForSaleUpdatedForAllProductsInWarehousesEvent::class => 'stockNotAvailableForSaleUpdatedForAllProductsInWarehouses',
        ];
    }

    public function productStockUpdated(StockUpdatedForStockMovementsEvent $event): void
    {
        $productIds = array_values(array_map(
            fn (array $stockMovement) => $stockMovement['productId'],
            $event->getStockMovements(),
        ));
        $this->recalculateProductAvailableStock($productIds);
    }

    public function productReservedStockUpdated(ProductReservedStockUpdatedEvent $event): void
    {
        $this->recalculateProductAvailableStock($event->getProductIds());
    }

    public function stockNotAvailableForSaleUpdated(StockNotAvailableForSaleUpdatedEvent $event): void
    {
        $this->recalculateProductAvailableStock($event->getProductIds());
    }

    public function stockNotAvailableForSaleUpdatedForAllProductsInWarehouses(StockNotAvailableForSaleUpdatedForAllProductsInWarehousesEvent $event): void
    {
        if (count($event->getWarehouseIds()) === 0) {
            return;
        }

        $this->db->executeStatement(
            'UPDATE `pickware_erp_warehouse_stock` warehouseStock

            INNER JOIN `product`
            ON `product`.`id` = warehouseStock.`product_id`
            AND `product`.`version_id` = warehouseStock.`product_version_id`

            SET product.`available_stock` = product.`available_stock` + (' . ($event->isStockNotAvailableForSaleIncrease() ? -1 : 1) . ' * warehouseStock.`quantity`)

            WHERE warehouseStock.`warehouse_id` IN (:warehouseIds)
            AND warehouseStock.`quantity` > 0
            AND `product`.`version_id` = :liveVersionId;',
            [
                'warehouseIds' => array_map('hex2bin', $event->getWarehouseIds()),
                'liveVersionId' => hex2bin(Defaults::LIVE_VERSION),
            ],
            [
                'warehouseIds' => Connection::PARAM_STR_ARRAY,
            ],
        );
    }

    public function recalculateProductAvailableStock(array $productIds): void
    {
        if (count($productIds) === 0) {
            return;
        }
        $this->pickwareProductInitializer->ensurePickwareProductsExist($productIds);

        RetryableTransaction::retryable($this->db, function () use ($productIds): void {
            $this->db->executeStatement(
                'UPDATE `product`
                LEFT JOIN `pickware_erp_pickware_product` pickwareProduct
                    ON pickwareProduct.`product_id` = `product`.`id`
                    AND pickwareProduct.`product_version_id` = `product`.`version_id`
                # The available stock can be negative
                SET `product`.`available_stock` = `product`.`stock` - pickwareProduct.`stock_not_available_for_sale` - pickwareProduct.`reserved_stock`
                WHERE `product`.`version_id` = :liveVersionId
                  AND `product`.`id` IN (:productIds)',
                [
                    'liveVersionId' => hex2bin(Defaults::LIVE_VERSION),
                    'productIds' => array_map('hex2bin', $productIds),
                ],
                [
                    'productIds' => Connection::PARAM_STR_ARRAY,
                ],
            );
        });

        $this->eventDispatcher->dispatch(new ProductAvailableStockUpdatedEvent($productIds));
    }
}
