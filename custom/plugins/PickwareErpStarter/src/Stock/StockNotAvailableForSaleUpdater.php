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
use Pickware\DalBundle\EntityPreWriteValidationEvent;
use Pickware\DalBundle\EntityPreWriteValidationEventDispatcher;
use Pickware\PickwareErpStarter\Product\PickwareProductInitializer;
use Pickware\PickwareErpStarter\Stock\Model\StockMovementDefinition;
use Pickware\PickwareErpStarter\Warehouse\Model\WarehouseDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\UpdateCommand;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class StockNotAvailableForSaleUpdater implements EventSubscriberInterface
{
    private Connection $db;
    private EventDispatcherInterface $eventDispatcher;
    private PickwareProductInitializer $pickwareProductInitializer;

    public function __construct(
        Connection $db,
        EventDispatcherInterface $eventDispatcher = null,
        PickwareProductInitializer $pickwareProductInitializer = null
    ) {
        $this->db = $db;
        $this->eventDispatcher = $eventDispatcher;
        $this->pickwareProductInitializer = $pickwareProductInitializer;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            StockMovementDefinition::ENTITY_WRITTEN_EVENT => 'stockMovementWritten',
            WarehouseDefinition::ENTITY_WRITTEN_EVENT => 'warehouseWritten',
            EntityPreWriteValidationEventDispatcher::getEventName(WarehouseDefinition::ENTITY_NAME) => 'triggerChangeSetForWarehouseChanges',
        ];
    }

    public function stockMovementWritten(EntityWrittenEvent $entityWrittenEvent): void
    {
        if ($entityWrittenEvent->getContext()->getVersionId() !== Defaults::LIVE_VERSION) {
            return;
        }

        $stockMovementIds = [];
        foreach ($entityWrittenEvent->getWriteResults() as $writeResult) {
            if ($writeResult->getExistence()->exists()) {
                // Updating stock movements is not supported yet
                // In case a stock location is deleted, this code path is also reached. This is because an
                // EntityWrittenEvent is triggered when an entity field gets null-ed because of a SET NULL constraint
                // of a FK.
                continue;
            }
            $payload = $writeResult->getPayload();
            $stockMovementIds[] = $payload['id'];
        }

        $this->updateProductStockNotAvailableForSaleByStockMovements($stockMovementIds);
    }

    public function triggerChangeSetForWarehouseChanges($event): void
    {
        if (!($event instanceof EntityPreWriteValidationEvent)) {
            // The subscriber is probably instantiated in its old version (with the Shopware PreWriteValidationEvent) in
            // the container and will be updated on the next container rebuild (next request). Early return.
            return;
        }

        foreach ($event->getCommands() as $command) {
            if (!($command instanceof UpdateCommand)
                || $command->getDefinition()->getEntityName() !== WarehouseDefinition::ENTITY_NAME) {
                continue;
            }
            if ($command->hasField('is_stock_available_for_sale')) {
                $command->requestChangeSet();
            }
        }
    }

    public function warehouseWritten(EntityWrittenEvent $entityWrittenEvent): void
    {
        if ($entityWrittenEvent->getContext()->getVersionId() !== Defaults::LIVE_VERSION) {
            return;
        }

        $warehouseIdsToDecreaseStockNotAvailableForSale = [];
        $warehouseIdsToIncreaseStockNotAvailableForSale = [];
        foreach ($entityWrittenEvent->getWriteResults() as $writeResult) {
            $payload = $writeResult->getPayload();
            // Should be not null when 'isStockAvailableForSale' has changed as we requested a change set in
            // triggerChangeSetForWarehouseChanges.
            $changeSet = $writeResult->getChangeSet();
            if (($writeResult->getOperation() !== EntityWriteResult::OPERATION_UPDATE)
                || !array_key_exists('isStockAvailableForSale', $payload)
                || !$changeSet
                || !$changeSet->hasChanged('is_stock_available_for_sale')) {
                continue;
            }

            if ($payload['isStockAvailableForSale']) {
                // Warehouse stock is now available for sale, decrease stockNotAvailableForSale
                $warehouseIdsToDecreaseStockNotAvailableForSale[] = $writeResult->getPrimaryKey();
            } else {
                // Warehouse stock is no longer available for sale, increase stockNotAvailableForSale
                $warehouseIdsToIncreaseStockNotAvailableForSale[] = $writeResult->getPrimaryKey();
            }
        }

        if (count($warehouseIdsToDecreaseStockNotAvailableForSale) > 0) {
            $this->updateProductStockNotAvailableForSaleByWarehouseStock(-1, $warehouseIdsToDecreaseStockNotAvailableForSale);
            $this->eventDispatcher->dispatch(new StockNotAvailableForSaleUpdatedForAllProductsInWarehousesEvent(
                $warehouseIdsToDecreaseStockNotAvailableForSale,
                false,
            ));
        }
        if (count($warehouseIdsToIncreaseStockNotAvailableForSale) > 0) {
            $this->updateProductStockNotAvailableForSaleByWarehouseStock(1, $warehouseIdsToIncreaseStockNotAvailableForSale);
            $this->eventDispatcher->dispatch(new StockNotAvailableForSaleUpdatedForAllProductsInWarehousesEvent(
                $warehouseIdsToIncreaseStockNotAvailableForSale,
                true,
            ));
        }
    }

    public function updateProductStockNotAvailableForSaleByStockMovements(array $stockMovementIds): void
    {
        $stockMovementIds = array_values(array_unique($stockMovementIds));
        $stockMovements = $this->db->fetchAllAssociative(
            'SELECT
                LOWER(HEX(product_id)) AS productId,
                quantity,
                COALESCE(
                    sourceWarehouse.id,
                    sourceBinLocationWarehouse.id
                ) AS sourceWarehouseId,
                COALESCE(
                    sourceWarehouse.is_stock_available_for_sale,
                    sourceBinLocationWarehouse.is_stock_available_for_sale
                ) AS sourceWarehouseIsStockAvailableForSale,
                COALESCE(
                    destinationWarehouse.id,
                    destinationBinLocationWarehouse.id
                ) AS destinationWarehouseId,
                COALESCE(
                    destinationWarehouse.is_stock_available_for_sale,
                    destinationBinLocationWarehouse.is_stock_available_for_sale
                ) AS destinationWarehouseIsStockAvailableForSale

            FROM pickware_erp_stock_movement stockMovement
            LEFT JOIN pickware_erp_warehouse sourceWarehouse ON sourceWarehouse.id = stockMovement.source_warehouse_id
            LEFT JOIN pickware_erp_bin_location sourceBinLocation ON sourceBinLocation.id = stockMovement.source_bin_location_id
                LEFT JOIN pickware_erp_warehouse sourceBinLocationWarehouse ON sourceBinLocationWarehouse.id = sourceBinLocation.warehouse_id
            LEFT JOIN pickware_erp_warehouse destinationWarehouse ON destinationWarehouse.id = stockMovement.destination_warehouse_id
            LEFT JOIN pickware_erp_bin_location destinationBinLocation ON destinationBinLocation.id = stockMovement.destination_bin_location_id
                LEFT JOIN pickware_erp_warehouse destinationBinLocationWarehouse ON destinationBinLocationWarehouse.id = destinationBinLocation.warehouse_id
            WHERE stockMovement.id IN (:stockMovementIds)
              AND product_version_id = :liveVersionId
              AND (
                  # Note that "<>" comparator does not work with NULL values. Hence, the verbose check.
                  COALESCE(sourceWarehouse.id,sourceBinLocationWarehouse.id) IS NULL && COALESCE(destinationWarehouse.id, destinationBinLocationWarehouse.id) IS NOT NULL ||
                  COALESCE(sourceWarehouse.id,sourceBinLocationWarehouse.id) IS NOT NULL && COALESCE(destinationWarehouse.id, destinationBinLocationWarehouse.id) IS NULL ||
                  COALESCE(sourceWarehouse.id,sourceBinLocationWarehouse.id) <> COALESCE(destinationWarehouse.id, destinationBinLocationWarehouse.id)
              )',
            [
                'stockMovementIds' => array_map('hex2bin', $stockMovementIds),
                'liveVersionId' => hex2bin(Defaults::LIVE_VERSION),
            ],
            [
                'stockMovementIds' => Connection::PARAM_STR_ARRAY,
            ],
        );

        $stockNotAvailableForSaleChanges = [];
        foreach ($stockMovements as $stockMovement) {
            $sourceIsWarehouse = (bool) $stockMovement['sourceWarehouseId'];
            $sourceWarehouseIsStockAvailableForSale = (bool) $stockMovement['sourceWarehouseIsStockAvailableForSale'];
            $destinationIsWarehouse = (bool) $stockMovement['destinationWarehouseId'];
            $destinationWarehouseIsStockAvailableForSale = (bool) $stockMovement['destinationWarehouseIsStockAvailableForSale'];

            if ($sourceIsWarehouse && !$sourceWarehouseIsStockAvailableForSale && ($destinationWarehouseIsStockAvailableForSale || !$destinationIsWarehouse)) {
                $stockNotAvailableForSaleChanges[] = [
                    'productId' => $stockMovement['productId'],
                    'change' => -1 * (int) $stockMovement['quantity'],
                ];
            }
            if ($destinationIsWarehouse && !$destinationWarehouseIsStockAvailableForSale && ($sourceWarehouseIsStockAvailableForSale || !$sourceIsWarehouse)) {
                $stockNotAvailableForSaleChanges[] = [
                    'productId' => $stockMovement['productId'],
                    'change' => (int) $stockMovement['quantity'],
                ];
            }
        }

        if (count($stockNotAvailableForSaleChanges) > 0) {
            $productIds = array_values(array_unique(array_column($stockNotAvailableForSaleChanges, 'productId')));

            $this->pickwareProductInitializer->ensurePickwareProductsExist($productIds);
            foreach ($stockNotAvailableForSaleChanges as $stockAvailableForSaleChange) {
                $this->persistStockNotAvailableForSaleChange(
                    $stockAvailableForSaleChange['productId'],
                    $stockAvailableForSaleChange['change'],
                );
            }
            $this->eventDispatcher->dispatch(new StockNotAvailableForSaleUpdatedEvent($productIds));
        }
    }

    private function persistStockNotAvailableForSaleChange(string $productId, int $change): void
    {
        $this->db->executeStatement(
            'UPDATE `pickware_erp_pickware_product`
            SET `pickware_erp_pickware_product`.`stock_not_available_for_sale` = `pickware_erp_pickware_product`.`stock_not_available_for_sale` + (:change)
            WHERE `pickware_erp_pickware_product`.`product_id` = :productId
            AND `pickware_erp_pickware_product`.`product_version_id` = :liveVersionId;',
            [
                'productId' => hex2bin($productId),
                'change' => $change,
                'liveVersionId' => hex2bin(Defaults::LIVE_VERSION),
            ],
            [
                'productIds' => Connection::PARAM_STR_ARRAY,
            ],
        );
    }

    /**
     * When a warehouse "isStockAvailableForSale" flag is changed, we need to update the stock_not_available_for_sale of
     * _ALL AFFECTED PRODUCTS_. Since the warehouse stock does not need to be recalculated, we can simply add/subtract
     * it from the stock_not_available_for_sale for all affected products.
     *
     * @param int $stockNotAvailableForSaleFactor 1 or -1 whether or not the online not available stock should be
     * increased (1) or decreased (-1)
     */
    private function updateProductStockNotAvailableForSaleByWarehouseStock(
        int $stockNotAvailableForSaleFactor,
        array $warehouseIds
    ): void {
        $this->db->executeStatement(
            'UPDATE `pickware_erp_pickware_product` pickwareProduct

            INNER JOIN `pickware_erp_warehouse_stock` warehouseStock
            ON warehouseStock.`product_id` = pickwareProduct.`product_id`
            AND warehouseStock.`product_version_id` = pickwareProduct.`product_version_id`

            SET pickwareProduct.`stock_not_available_for_sale` = pickwareProduct.`stock_not_available_for_sale` + (' . $stockNotAvailableForSaleFactor . ' * warehouseStock.`quantity`)

            WHERE warehouseStock.`warehouse_id` IN (:warehouseIds)
            AND warehouseStock.`quantity` > 0
            AND pickwareProduct.`product_version_id` = :liveVersionId;',
            [
                'warehouseIds' => array_map('hex2bin', $warehouseIds),
                'liveVersionId' => hex2bin(Defaults::LIVE_VERSION),
            ],
            [
                'warehouseIds' => Connection::PARAM_STR_ARRAY,
            ],
        );
    }
}
