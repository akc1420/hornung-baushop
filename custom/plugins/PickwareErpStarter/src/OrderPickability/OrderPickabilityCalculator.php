<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareErpStarter\OrderPickability;

use DateTime;
use Doctrine\DBAL\Connection;
use Pickware\PickwareErpStarter\OrderPickability\Model\OrderPickabilityCollection;
use Pickware\PickwareErpStarter\OrderPickability\Model\OrderPickabilityDefinition;
use Pickware\PickwareErpStarter\OrderPickability\Model\OrderPickabilityEntity;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryStates;
use Shopware\Core\Checkout\Order\OrderStates;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;

class OrderPickabilityCalculator
{
    // For orders with any state in the ignore list, no pickability will be calculated. This way we avoid performing
    // expensive calculations for old (completed/canceled/..) orders.
    private const ORDER_STATE_IGNORE_LIST = [
        OrderStates::STATE_CANCELLED,
        OrderStates::STATE_COMPLETED,
    ];
    private const ORDER_DELIVERY_STATE_IGNORE_LIST = [
        OrderDeliveryStates::STATE_CANCELLED,
        OrderDeliveryStates::STATE_SHIPPED,
    ];

    private Connection $connection;
    private ?array $cachedIgnoredOrderStateIds;
    private ?array $cachedIgnoredOrderDeliveryStateIds;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->cachedIgnoredOrderStateIds = null;
        $this->cachedIgnoredOrderDeliveryStateIds = null;
    }

    /**
     * @param string[] $orderIds
     */
    public function calculateOrderPickabilitiesForOrders(array $orderIds): OrderPickabilityCollection
    {
        if (count($orderIds) === 0) {
            return new OrderPickabilityCollection();
        }

        $allWarehouseIds = $this->connection->fetchFirstColumn(
            'SELECT LOWER(HEX(`id`)) FROM `pickware_erp_warehouse`;',
        );

        return $this->calculateOrderPickabilitiesForOrdersAndWarehouses(
            $this->getFilteredOrderIds($orderIds),
            $allWarehouseIds,
        );
    }

    /**
     * @param string[] $warehouseIds
     */
    public function calculateOrderPickabilitiesForWarehouses(array $warehouseIds): OrderPickabilityCollection
    {
        if (count($warehouseIds) === 0) {
            return new OrderPickabilityCollection();
        }

        return $this->calculateOrderPickabilitiesForOrdersAndWarehouses(
            $this->getFilteredOrderIds(),
            $warehouseIds,
        );
    }

    /**
     * @return string[]
     */
    public function getOrderIdsWithoutPickabilities(): array
    {
        // For performance reasons we pre-fetch the ignored state IDs instead of adding another join when filtering
        $ignoredOrderStateIds = $this->getIgnoredOrderStateIds();
        $ignoredOrderDeliveryStateIds = $this->getIgnoredOrderDeliveryStateIds();

        $filterQuery = <<<SQL
            SELECT DISTINCT
                LOWER(HEX(`order`.`id`))
            FROM `order`

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

            WHERE
                `order`.`version_id` = :liveVersionId
                AND (
                    `order`.`state_id` IN (:ignoredOrderStateIds)
                    OR `primary_order_delivery`.`id` IS NULL
                    OR `primary_order_delivery`.`state_id` IN (:ignoredOrderDeliveryStateIds)
                )
        SQL;

        return $this->connection->fetchFirstColumn(
            $filterQuery,
            [
                'liveVersionId' => hex2bin(Defaults::LIVE_VERSION),
                'ignoredOrderStateIds' => array_map('hex2bin', $ignoredOrderStateIds),
                'ignoredOrderDeliveryStateIds' => array_map('hex2bin', $ignoredOrderDeliveryStateIds),
            ],
            [
                'ignoredOrderStateIds' => Connection::PARAM_STR_ARRAY,
                'ignoredOrderDeliveryStateIds' => Connection::PARAM_STR_ARRAY,
            ],
        );
    }

    /**
     * @param string[] $orderIds
     * @param string[] $warehouseIds
     */
    private function calculateOrderPickabilitiesForOrdersAndWarehouses(
        array $orderIds,
        array $warehouseIds
    ): OrderPickabilityCollection {
        if (count($orderIds) === 0 || count($warehouseIds) === 0) {
            return new OrderPickabilityCollection();
        }

        $rawPickabilities = $this->connection->fetchAllAssociative(
            'SELECT
                LOWER(HEX(`order`.`id`)) AS `orderId`,
                LOWER(HEX(`order`.`version_id`)) AS `orderVersionId`,
                LOWER(HEX(`order_line_item_pickability`.`warehouse_id`)) AS `warehouseId`,
                CASE
                    -- If all order line items are completely pickable, the order is "completely pickable"
                    WHEN SUM(
                        LEAST(
                            -- Regardless of their physical pickability, shipped order line items are ignored by
                            -- making them `completely pickable`
                            IFNULL(`order_line_item_pickability`.`completely_pickable`, 1) + IFNULL(`order_line_item_pickability`.`is_completely_shipped`, 1),
                            1
                        )
                    ) = COUNT(*) THEN :pickabilityStatusCompletelyPickable

                    -- If at least one order line item is partially pickable (which is also true for order line
                    -- items that are completely pickable), the order is "partially pickable"
                    WHEN SUM(
                        GREATEST(
                            -- shipped order line items are ignored by making them not `partially_pickable`
                            IFNULL(`order_line_item_pickability`.`partially_pickable`, 0) - IFNULL(`order_line_item_pickability`.`is_completely_shipped`, 0),
                            0
                        )
                    ) > 0 THEN :pickabilityStatusPartiallyPickable

                    -- Otherwise (not a single order line item is not even partially pickable) the order is
                    -- "not pickable"
                    ELSE :pickabilityStatusNotPickable
                END AS `orderPickabilityStatus`,
                NOW(3) AS `createdAt`
            FROM `order`
            LEFT JOIN (
                -- This sub select calculates the pickability of each order line item per order.
                SELECT
                    `order_line_item`.`order_id` AS `order_id`,
                    `order_line_item`.`order_version_id` AS `order_version_id`,
                    `warehouse_stock`.`warehouse_id` AS `warehouse_id`,

                    -- Note that the same product can be part of an order multiple time (multiple order line items
                    -- can reference the same product). Whereas the same product can only have a single stock value
                    -- in each order.
                    IFNULL(SUM(`order_line_item`.`quantity`), 0) - IFNULL(MIN(`stock_in_order_by_product`.`quantity`), 0) <= 0 AS `is_completely_shipped`,

                    -- "completely pickable" if there is enough stock in the warehouse for the product in the order to
                    -- fulfill the quantity of the order (minus what is already stocked into the order).
                    --
                    -- This is also true if:
                    --      - order line item references a product for which stock management is disable
                    --      - there is no stock to pick any more (product in order is completely shipped)
                    --      - order line item references a deleted product (warehouse stock is NULL)
                    (
                        `pickware_product`.`is_stock_management_disabled` = 1
                        OR `warehouse_stock`.`quantity` IS NULL
                        OR `warehouse_stock`.`quantity` >= GREATEST(
                            0,
                            IFNULL(SUM(`order_line_item`.`quantity`), 0) - IFNULL(SUM(`stock_in_order_by_product`.`quantity`), 0)
                        )
                    ) AS `completely_pickable`,

                    -- "partially pickable" if there is at least some stock in the warehouse to pick from
                    (
                        `pickware_product`.`is_stock_management_disabled` = 0
                        AND (`warehouse_stock`.`quantity` IS NULL OR `warehouse_stock`.`quantity` > 0)
                    ) AS `partially_pickable`
                FROM `order_line_item`
                LEFT JOIN `pickware_erp_warehouse_stock` AS `warehouse_stock`
                    ON `warehouse_stock`.`warehouse_id` IN (:warehouseIds)
                    AND `warehouse_stock`.`product_id` = `order_line_item`.`product_id`
                    -- Join via liveVersionId (which we know is true because of the WHERE statement below), because
                    -- there is no index on the `order_line_item`.`product_version_id`, which makes this query slow
                    AND `warehouse_stock`.`product_version_id` = :liveVersionId
                LEFT JOIN `pickware_erp_pickware_product` AS `pickware_product`
                    ON `pickware_product`.`product_id` = `order_line_item`.`product_id`
                    AND `pickware_product`.`product_version_id` = `order_line_item`.`product_version_id`
                LEFT JOIN `pickware_erp_stock` AS `stock_in_order_by_product`
                    ON `stock_in_order_by_product`.`order_id` = `order_line_item`.`order_id`
                    AND `stock_in_order_by_product`.`order_version_id` = `order_line_item`.`order_version_id`
                    AND `stock_in_order_by_product`.`product_id` = `order_line_item`.`product_id`
                    -- Join via liveVersionId (which we know is true because of the WHERE statement below), because
                    -- there is no index on the `order_line_item`.`product_version_id`, which makes this query slow
                    AND `stock_in_order_by_product`.`product_version_id` = :liveVersionId
                WHERE
                    `order_line_item`.`order_id` IN (:orderIds)
                    AND `order_line_item`.`order_version_id` = :liveVersionId
                    AND `order_line_item`.`version_id` = :liveVersionId
                    AND `order_line_item`.`type` = :orderLineItemTypeProduct
                GROUP BY
                    `order_line_item`.`order_id`,
                    `order_line_item`.`order_version_id`,
                    `order_line_item`.`product_id`,
                    `order_line_item`.`product_version_id`,
                    `warehouse_stock`.`warehouse_id`
            ) AS `order_line_item_pickability`
                ON `order_line_item_pickability`.`order_id` = `order`.`id`
                AND `order_line_item_pickability`.`order_version_id` = `order`.`version_id`
            WHERE
                `order`.`id` IN (:orderIds)
                AND `order`.`version_id` = :liveVersionId
            GROUP BY
                `order`.`id`,
                `order`.`version_id`,
                `order_line_item_pickability`.`warehouse_id`',
            [
                'pickabilityStatusCompletelyPickable' => OrderPickabilityDefinition::PICKABILITY_STATUS_COMPLETELY_PICKABLE,
                'pickabilityStatusPartiallyPickable' => OrderPickabilityDefinition::PICKABILITY_STATUS_PARTIALLY_PICKABLE,
                'pickabilityStatusNotPickable' => OrderPickabilityDefinition::PICKABILITY_STATUS_NOT_PICKABLE,
                'orderLineItemTypeProduct' => LineItem::PRODUCT_LINE_ITEM_TYPE,
                'liveVersionId' => hex2bin(Defaults::LIVE_VERSION),
                'orderIds' => array_map('hex2bin', $orderIds),
                'warehouseIds' => array_map('hex2bin', $warehouseIds),
            ],
            [
                'orderIds' => Connection::PARAM_STR_ARRAY,
                'warehouseIds' => Connection::PARAM_STR_ARRAY,
            ],
        );

        // Add any missing entries for order/warehouse combinations that cannot yield a result in the SQL query because
        // e.g. the order only contains line items of other types than "product" and hence no row in
        // `order_line_item_pickability` can be joined
        $pickabilitiesByOrderId = [];
        foreach ($rawPickabilities as $pickability) {
            $orderId = $pickability['orderId'];
            $pickabilitiesByOrderId[$orderId] ??= [];
            $pickabilitiesByOrderId[$orderId][] = $pickability;
        }
        $orderPickabilityCollection = new OrderPickabilityCollection();
        foreach ($pickabilitiesByOrderId as $orderId => $orderPickabilities) {
            foreach ($warehouseIds as $warehouseId) {
                $warehousePickability = current(array_filter(
                    $orderPickabilities,
                    fn (array $pickability) => $pickability['warehouseId'] === $warehouseId,
                ));
                $pickabilityEntity = new OrderPickabilityEntity();
                $pickabilityEntity->setId(Uuid::randomHex());
                if ($warehousePickability) {
                    $pickabilityEntity->assign($warehousePickability);
                } else {
                    // Create a dummy entity for the order/warehouse combination with a pickability status of
                    // "completely pickable"
                    $pickabilityEntity->setOrderId($orderId);
                    $pickabilityEntity->setOrderVersionId(Defaults::LIVE_VERSION);
                    $pickabilityEntity->setWarehouseId($warehouseId);
                    $pickabilityEntity->setOrderPickabilityStatus(
                        OrderPickabilityDefinition::PICKABILITY_STATUS_COMPLETELY_PICKABLE,
                    );
                    $pickabilityEntity->setCreatedAt(new DateTime());
                }
                $orderPickabilityCollection->add($pickabilityEntity);
            }
        }

        return $orderPickabilityCollection;
    }

    /**
     * @param string[]|null $orderIds
     * @return string[]
     */
    private function getFilteredOrderIds(?array $orderIds = null): array
    {
        // For performance reasons we pre-fetch the ignored state IDs instead of adding another join when filtering
        $ignoredOrderStateIds = $this->getIgnoredOrderStateIds();
        $ignoredOrderDeliveryStateIds = $this->getIgnoredOrderDeliveryStateIds();

        $filterQuery = <<<SQL
            SELECT DISTINCT
                LOWER(HEX(`order`.`id`))
            FROM `order`

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
            INNER JOIN `order_delivery` as `primary_order_delivery`
                ON `primary_order_delivery`.`order_version_id` = `order`.`version_id`
                AND `primary_order_delivery`.`state_id` NOT IN (:ignoredOrderDeliveryStateIds)
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

            WHERE
                `order`.`version_id` = :liveVersionId
                AND `order`.`state_id` NOT IN (:ignoredOrderStateIds)
        SQL;
        $filterQueryArguments = [
            'liveVersionId' => hex2bin(Defaults::LIVE_VERSION),
            'ignoredOrderStateIds' => array_map('hex2bin', $ignoredOrderStateIds),
            'ignoredOrderDeliveryStateIds' => array_map('hex2bin', $ignoredOrderDeliveryStateIds),
        ];
        $filterQueryArgumentTypes = [
            'ignoredOrderStateIds' => Connection::PARAM_STR_ARRAY,
            'ignoredOrderDeliveryStateIds' => Connection::PARAM_STR_ARRAY,
        ];
        if (is_array($orderIds) && count($orderIds) > 0) {
            $filterQuery .= ' AND `order`.`id` IN (:orderIds)';
            $filterQueryArguments['orderIds'] = array_map('hex2bin', $orderIds);
            $filterQueryArgumentTypes['orderIds'] = Connection::PARAM_STR_ARRAY;
        }

        return $this->connection->fetchFirstColumn($filterQuery, $filterQueryArguments, $filterQueryArgumentTypes);
    }

    /**
     * @return string[]
     */
    private function getIgnoredOrderStateIds(): array
    {
        if ($this->cachedIgnoredOrderStateIds !== null) {
            return $this->cachedIgnoredOrderStateIds;
        }

        $this->cachedIgnoredOrderStateIds = $this->connection->fetchFirstColumn(
            'SELECT
                LOWER(HEX(`state_machine_state`.`id`))
            FROM `state_machine_state`
            INNER JOIN `state_machine`
                ON `state_machine`.`id` = `state_machine_state`.`state_machine_id`
                AND `state_machine`.`technical_name` = :stateMaschineName
            WHERE`state_machine_state`.`technical_name` IN (:states)',
            [
                'states' => self::ORDER_STATE_IGNORE_LIST,
                'stateMaschineName' => OrderStates::STATE_MACHINE,
            ],
            [
                'states' => Connection::PARAM_STR_ARRAY,
            ],
        );

        return $this->cachedIgnoredOrderStateIds;
    }

    /**
     * @return string[]
     */
    private function getIgnoredOrderDeliveryStateIds(): array
    {
        if ($this->cachedIgnoredOrderDeliveryStateIds !== null) {
            return $this->cachedIgnoredOrderDeliveryStateIds;
        }

        $this->cachedIgnoredOrderDeliveryStateIds = $this->connection->fetchFirstColumn(
            'SELECT
                LOWER(HEX(`state_machine_state`.`id`))
            FROM `state_machine_state`
            INNER JOIN `state_machine`
                ON `state_machine`.`id` = `state_machine_state`.`state_machine_id`
                AND `state_machine`.`technical_name` = :stateMaschineName
            WHERE `state_machine_state`.`technical_name` IN (:states)',
            [
                'states' => self::ORDER_DELIVERY_STATE_IGNORE_LIST,
                'stateMaschineName' => OrderDeliveryStates::STATE_MACHINE,
            ],
            [
                'states' => Connection::PARAM_STR_ARRAY,
            ],
        );

        return $this->cachedIgnoredOrderDeliveryStateIds;
    }
}
