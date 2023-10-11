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
use Pickware\DalBundle\DatabaseBulkInsertService;
use Pickware\DalBundle\EntityPreWriteValidationEvent;
use Pickware\DalBundle\EntityPreWriteValidationEventDispatcher;
use Pickware\DalBundle\RetryableTransaction;
use Pickware\PickwareErpStarter\Product\PickwareProductInitializer;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryStates;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemDefinition;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Order\OrderEvents;
use Shopware\Core\Checkout\Order\OrderStates;
use Shopware\Core\Content\Product\ProductEvents;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\ChangeSetAware;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\DeleteCommand;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Validation\PreWriteValidationEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProductReservedStockUpdater implements EventSubscriberInterface
{
    private Connection $db;
    private EventDispatcherInterface $eventDispatcher;
    private ?DatabaseBulkInsertService $bulkInsertWithUpdate;
    private ?PickwareProductInitializer $pickwareProductInitializer;

    /** @deprecated next major, $bulkInsertWithUpdate and $pickwareProductInitializer will be non-optional */
    public function __construct(
        Connection $db,
        EventDispatcherInterface $eventDispatcher,
        ?DatabaseBulkInsertService $bulkInsertWithUpdate = null,
        ?PickwareProductInitializer $pickwareProductInitializer = null
    ) {
        $this->db = $db;
        $this->eventDispatcher = $eventDispatcher;
        $this->bulkInsertWithUpdate = $bulkInsertWithUpdate;
        $this->pickwareProductInitializer = $pickwareProductInitializer;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntityPreWriteValidationEventDispatcher::getEventName(OrderLineItemDefinition::ENTITY_NAME) => 'triggerOrderLineItemChangeSet',
            EntityPreWriteValidationEventDispatcher::getEventName(OrderDefinition::ENTITY_NAME) => 'triggerOrderChangeSet',
            EntityPreWriteValidationEventDispatcher::getEventName(OrderDeliveryDefinition::ENTITY_NAME) => 'triggerOrderDeliveryChangeSet',
            StockUpdatedForStockMovementsEvent::class => 'stockUpdatedForStockMovements',
            ProductEvents::PRODUCT_WRITTEN_EVENT => 'productWritten',
            OrderEvents::ORDER_WRITTEN_EVENT => 'orderWritten',
            OrderEvents::ORDER_DELETED_EVENT => 'orderWritten',
            OrderEvents::ORDER_DELIVERY_WRITTEN_EVENT => 'orderDeliveryWritten',
            OrderEvents::ORDER_LINE_ITEM_WRITTEN_EVENT => 'orderLineItemWritten',
            OrderEvents::ORDER_LINE_ITEM_DELETED_EVENT => 'orderLineItemWritten',
        ];
    }

    /**
     * If this subscriber instantiated in its old version (with the Shopware PreWriteValidationEvent subscribed with
     * function triggerChangeSet) during plugin update, we need to keep the old (unused) subscriber function to not
     * crash the container. The function is unused during update, so we can keep this a noop.
     * See also: https://github.com/pickware/shopware-plugins/commit/d4cd9e725df724388fa31cc24461ff62ee0585eb#diff-d298c50af83392dc452a387c04823b8b63b7d333f250e02fbed95aa490ae3914L60
     */
    public function triggerChangeSet(PreWriteValidationEvent $event): void
    {
    }

    public function triggerOrderLineItemChangeSet(EntityPreWriteValidationEvent $event): void
    {
        foreach ($event->getCommands() as $command) {
            if ($command instanceof ChangeSetAware && (
                $command instanceof DeleteCommand
                    || $command->hasField('product_id')
                    || $command->hasField('product_version_id')
                    || $command->hasField('version_id')
                    || $command->hasField('type')
                    || $command->hasField('quantity')
            )
            ) {
                $command->requestChangeSet();
            }
        }
    }

    public function triggerOrderChangeSet(EntityPreWriteValidationEvent $event): void
    {
        foreach ($event->getCommands() as $command) {
            if ($command instanceof ChangeSetAware && (
                $command instanceof DeleteCommand
                    || $command->hasField('order_line_item_id')
            )
            ) {
                $command->requestChangeSet();
            }
        }
    }

    public function triggerOrderDeliveryChangeSet(EntityPreWriteValidationEvent $event): void
    {
        foreach ($event->getCommands() as $command) {
            if ($command instanceof ChangeSetAware && $command->hasField('order_id')) {
                $command->requestChangeSet();
            }
        }
    }

    public function stockUpdatedForStockMovements(StockUpdatedForStockMovementsEvent $event): void
    {
        $productIds = [];
        foreach ($event->getStockMovements() as $stockMovement) {
            if ($stockMovement['sourceOrderId'] || $stockMovement['destinationOrderId']) {
                $productIds[] = $stockMovement['productId'];
            }
        }
        $this->recalculateProductReservedStock($productIds);
    }

    public function orderWritten(EntityWrittenEvent $entityWrittenEvent): void
    {
        if ($entityWrittenEvent->getContext()->getVersionId() !== Defaults::LIVE_VERSION) {
            return;
        }

        $orderIds = [];
        foreach ($entityWrittenEvent->getWriteResults() as $writeResult) {
            $payload = $writeResult->getPayload();
            if (isset($payload['versionId'])
                || isset($payload['stateId'])
            ) {
                $orderIds[] = $writeResult->getPrimaryKey();
            }
        }

        $products = $this->db->fetchAllAssociative(
            'SELECT LOWER(HEX(`order_line_item`.`product_id`)) AS `id`
            FROM `order_line_item`
            WHERE `order_line_item`.`order_id` IN (:orderIds)
                AND `order_line_item`.`version_id` = :liveVersionId
                AND `order_line_item`.`order_version_id` = :liveVersionId
                AND `order_line_item`.`product_version_id` = :liveVersionId
                AND `order_line_item`.`product_id` IS NOT NULL',
            [
                'orderIds' => array_map('hex2bin', $orderIds),
                'liveVersionId' => hex2bin(Defaults::LIVE_VERSION),
            ],
            [
                'orderIds' => Connection::PARAM_STR_ARRAY,
            ],
        );

        $productIds = array_column($products, 'id');

        $this->recalculateProductReservedStock($productIds);
    }

    public function orderDeliveryWritten(EntityWrittenEvent $entityWrittenEvent): void
    {
        if ($entityWrittenEvent->getContext()->getVersionId() !== Defaults::LIVE_VERSION) {
            return;
        }

        $orderDeliveryIds = [];
        $orderIds = [];
        foreach ($entityWrittenEvent->getWriteResults() as $writeResult) {
            $payload = $writeResult->getPayload();
            if (isset($payload['stateId'])
                || isset($payload['versionId'])
                || isset($payload['orderVersionId'])
            ) {
                $orderDeliveryIds[] = $payload['id'];
            }
            $changeSet = $writeResult->getChangeSet();
            if ($changeSet && $changeSet->hasChanged('order_id') && !empty($changeSet->getBefore('order_id'))) {
                $orderIds[] = bin2hex($changeSet->getBefore('order_id'));
                $orderIdAfter = $changeSet->getAfter('order_id');
                if ($orderIdAfter) {
                    // $orderIdAfter === null, when product_id was not changed
                    $orderIds[] = bin2hex($orderIdAfter);
                }
            }
        }

        $productIds = [];
        if (count($orderDeliveryIds) > 0) {
            $orderDeliveries = $this->db->fetchAllAssociative(
                'SELECT
                    LOWER(HEX(`order_line_item`.`product_id`)) AS `productId`
                FROM `order_delivery`
                INNER JOIN `order`
                    ON `order`.`id` = `order_delivery`.`order_id`
                    AND `order`.`version_id` = `order_delivery`.`order_version_id`
                INNER JOIN `order_line_item`
                    ON `order`.`id` = `order_line_item`.`order_id`
                    AND `order`.`version_id` = `order_line_item`.`order_version_id`
                WHERE `order_delivery`.`id` IN (:orderDeliveryIds)
                    AND `order_line_item`.`product_id` IS NOT NULL
                    AND `order_line_item`.`product_version_id` = :liveVersionId',
                [
                    'orderDeliveryIds' => array_map('hex2bin', $orderDeliveryIds),
                    'liveVersionId' => hex2bin(Defaults::LIVE_VERSION),
                ],
                [
                    'orderDeliveryIds' => Connection::PARAM_STR_ARRAY,
                ],
            );

            $productIds = array_merge($productIds, array_column($orderDeliveries, 'productId'));
        }
        if (count($orderIds) > 0) {
            $orders = $this->db->fetchAllAssociative(
                'SELECT
                    LOWER(HEX(`order_line_item`.`product_id`)) AS `productId`
                FROM `order`
                INNER JOIN `order_line_item`
                    ON `order`.`id` = `order_line_item`.`order_id`
                    AND `order`.`version_id` = `order_line_item`.`order_version_id`
                WHERE `order`.`id` IN (:orderIds)
                    AND `order_line_item`.`product_id` IS NOT NULL
                    AND `order_line_item`.`product_version_id` = :liveVersionId',
                [
                    'orderIds' => array_map('hex2bin', $orderIds),
                    'liveVersionId' => hex2bin(Defaults::LIVE_VERSION),
                ],
                [
                    'orderIds' => Connection::PARAM_STR_ARRAY,
                ],
            );

            $productIds = array_merge($productIds, array_column($orders, 'productId'));
        }

        $productIds = array_values(array_unique($productIds));

        $this->recalculateProductReservedStock($productIds);
    }

    public function productWritten(EntityWrittenEvent $entityWrittenEvent): void
    {
        if ($entityWrittenEvent->getContext()->getVersionId() !== Defaults::LIVE_VERSION) {
            return;
        }

        $productIds = [];
        foreach ($entityWrittenEvent->getWriteResults() as $writeResult) {
            $payload = $writeResult->getPayload();
            if (isset($payload['versionId'])
                || isset($payload['availableStock'])
            ) {
                $productIds[] = $payload['id'];
            }
        }

        $this->recalculateProductReservedStock($productIds);
    }

    /**
     * Updates the old and the new product, if the product of an order line item is changed.
     */
    public function orderLineItemWritten(EntityWrittenEvent $entityWrittenEvent): void
    {
        if ($entityWrittenEvent->getContext()->getVersionId() !== Defaults::LIVE_VERSION) {
            return;
        }

        $productIds = [];
        foreach ($entityWrittenEvent->getWriteResults() as $writeResult) {
            // $writeResult->getExistence() can be null, but we have no idea why and also not what this means.
            $existence = $writeResult->getExistence();
            $isNewOrderLineItem = (
                $existence === null
                && $writeResult->getOperation() === EntityWriteResult::OPERATION_INSERT
            ) || (
                $existence !== null && !$existence->exists()
            );
            if ($isNewOrderLineItem && array_key_exists('productId', $writeResult->getPayload())) {
                // This is a newly-created order line item
                $productIds[] = $writeResult->getPayload()['productId'];
                continue;
            }

            $changeSet = $writeResult->getChangeSet();
            if ($changeSet) {
                if ($changeSet->hasChanged('product_id')
                    || $changeSet->hasChanged('product_version_id')
                    || $changeSet->hasChanged('type')
                    || $changeSet->hasChanged('version_id')
                    || $changeSet->hasChanged('quantity')
                ) {
                    $productIdBefore = $changeSet->getBefore('product_id');
                    if ($productIdBefore) {
                        $productIds[] = bin2hex($productIdBefore);
                    }
                    $productIdAfter = $changeSet->getAfter('product_id');
                    if ($productIdAfter) {
                        // $productIdAfter === null, when product_id was not changed
                        $productIds[] = bin2hex($productIdAfter);
                    }
                }
            }
        }
        $productIds = array_values(array_filter(array_unique($productIds)));

        $this->recalculateProductReservedStock($productIds);
    }

    /**
     * @param string[] $productIds
     */
    public function recalculateProductReservedStock(array $productIds): void
    {
        if (!$this->bulkInsertWithUpdate) {
            // The property was made optional for backwards compatibility in the constructor. Should not happen
            // during an actual request. Return early.
            return;
        }

        if (count($productIds) === 0) {
            return;
        }

        // By splitting the SELECT and the UPDATE query we work around a performance problem. If the queries were
        // executed in one UPDATE ... JOIN query the query time would rise unexpectedly.
        RetryableTransaction::retryable($this->db, function () use ($productIds): void {
            $this->db->executeStatement(
                'SELECT `id` FROM `product` WHERE `id` IN (:productIds) FOR UPDATE',
                ['productIds' => array_map('hex2bin', $productIds)],
                ['productIds' => Connection::PARAM_STR_ARRAY],
            );
            $this->pickwareProductInitializer->ensurePickwareProductsExist($productIds);

            $pickwareProductReservedStocks = $this->db->fetchAllAssociative(
                'SELECT
                    `pickware_product`.`id` AS `id`,
                    `product`.`id` AS `product_id`,
                    `product`.`version_id` AS `product_version_id`,
                    SUM(
                        GREATEST(0, (
                            CASE
                                WHEN (`order_state`.`technical_name` IN (:orderStates)
                                    AND `order_delivery_state`.`technical_name` IN (:orderDeliveryStates))
                                THEN IFNULL(`order_line_item`.`quantity`, 0)
                                ELSE 0
                            END
                            ) - IFNULL(`stock`.`quantity`, 0))
                    ) AS `reserved_stock`,
                    NOW(3) as `updated_at`,
                    NOW(3) as `created_at`
                FROM `product`
                LEFT JOIN `order_line_item`
                    ON `order_line_item`.`product_id` = `product`.`id`
                    AND `order_line_item`.`product_version_id` = `product`.`version_id`
                    AND `order_line_item`.`version_id` = :liveVersionId
                    AND `order_line_item`.`type` = :orderLineItemTypeProduct
                LEFT JOIN `order`
                    ON `order`.`id` = `order_line_item`.`order_id`
                    AND `order`.`version_id` = `order_line_item`.`order_version_id`
                    AND `order`.`version_id` = :liveVersionId
                LEFT JOIN `state_machine_state` AS `order_state`
                    ON `order`.`state_id` = `order_state`.`id`
                LEFT JOIN `pickware_erp_stock` AS `stock`
                    ON `product`.`id` = `stock`.`product_id`
                    AND `product`.`version_id` = `stock`.`product_version_id`
                    AND `order`.`id` = `stock`.`order_id`
                    AND `order`.`version_id` = `stock`.`order_version_id`
                LEFT JOIN `pickware_erp_pickware_product` AS `pickware_product`
                    ON `product`.`id` = `pickware_product`.`product_id`
                    AND `product`.`version_id` = `pickware_product`.`product_version_id`

                -- Select a single order delivery with the highest shippingCosts.unitPrice as the primary order
                -- delivery for the order. This selection strategy is adapted from how order deliveries are selected
                -- in the administration. See /administration/src/module/sw-order/view/sw-order-detail-base/index.js
                LEFT JOIN (
                    SELECT
                        `order_id`,
                        `order_version_id`,
                        MAX(
                            CAST(JSON_UNQUOTE(
                                JSON_EXTRACT(`order_delivery`.`shipping_costs`, "$.unitPrice")
                            ) AS DECIMAL)
                        ) AS `unitPrice`
                    FROM `order_delivery`
                    GROUP BY `order_id`, `order_version_id`
                ) `primary_order_delivery_shipping_cost`
                    ON `primary_order_delivery_shipping_cost`.`order_id` = `order`.`id`
                    AND `primary_order_delivery_shipping_cost`.`order_version_id` = `order`.`version_id`
                LEFT JOIN `order_delivery` as `primary_order_delivery`
                    ON `primary_order_delivery`.`order_version_id` = `order`.`version_id`
                    AND `primary_order_delivery`.`id` = (
                        SELECT `id`
                        FROM `order_delivery`
                        WHERE `order_delivery`.`order_id` = `order`.`id`
                        AND `order_delivery`.`order_version_id` = `order`.`version_id`
                        AND CAST(JSON_UNQUOTE(JSON_EXTRACT(`order_delivery`.`shipping_costs`, "$.unitPrice")) AS DECIMAL) = `primary_order_delivery_shipping_cost`.`unitPrice`
                        -- Add LIMIT 1 here because this join would join multiple deliveries if they are tied for the
                        -- primary order delivery (i.e. multiple order delivery have the same highest shipping cost).
                        LIMIT 1
                    )
                LEFT JOIN `state_machine_state` AS `order_delivery_state`
                    ON `order_delivery_state`.`id` = `primary_order_delivery`.`state_id`

                WHERE
                    -- The following two conditions are performance optimizations
                    `product`.`id` IN (:productIds)
                    AND `product`.`version_id` = :liveVersionId
                GROUP BY
                     `product`.`id`,
                     `product`.`version_id`',
                [
                    'orderStates' => [
                        OrderStates::STATE_OPEN,
                        OrderStates::STATE_IN_PROGRESS,
                    ],
                    'orderDeliveryStates' => [
                        OrderDeliveryStates::STATE_OPEN,
                        OrderDeliveryStates::STATE_PARTIALLY_SHIPPED,
                    ],
                    'liveVersionId' => hex2bin(Defaults::LIVE_VERSION),
                    'productIds' => array_map('hex2bin', $productIds),
                    'orderLineItemTypeProduct' => LineItem::PRODUCT_LINE_ITEM_TYPE,
                ],
                [
                    'orderStates' => Connection::PARAM_STR_ARRAY,
                    'orderDeliveryStates' => Connection::PARAM_STR_ARRAY,
                    'productIds' => Connection::PARAM_STR_ARRAY,
                ],
            );

            // While testing optimizations on a larger shop system we saw that 5000 is a batch size which has great
            // performance while also having a size large enough that smaller shops can update everything in one go to
            // not waste performance on those systems.
            // Further references: https://github.com/pickware/shopware-plugins/issues/3324 and linked tickets
            $batches = array_chunk($pickwareProductReservedStocks, 5000);
            foreach ($batches as $batch) {
                $this->bulkInsertWithUpdate->insertOnDuplicateKeyUpdate(
                    'pickware_erp_pickware_product',
                    $batch,
                    [],
                    ['reserved_stock'],
                );
            }

            $this->eventDispatcher->dispatch(new ProductReservedStockUpdatedEvent($productIds));
        });
    }
}
