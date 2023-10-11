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

use Pickware\DalBundle\EntityManager;
use Pickware\PickwareErpStarter\ReturnOrder\Model\ReturnOrderLineItemDefinition;
use Pickware\PickwareErpStarter\ReturnOrder\Model\ReturnOrderLineItemEntity;
use Pickware\PickwareErpStarter\Stock\Model\StockDefinition;
use Pickware\PickwareErpStarter\Stock\Model\StockEntity;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;

class PickingRequestFactory
{
    private EntityManager $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @deprecated tag:next-major Method will be removed, use createPickingRequestForOrder instead (Reason: Typo)
     */
    public function createPickingRequestsForOrder(string $orderId, Context $context): PickingRequest
    {
        return $this->createPickingRequestForOrder($orderId, $context);
    }

    public function createPickingRequestForOrder(string $orderId, Context $context): PickingRequest
    {
        return $this->createPickingRequestsForOrders([$orderId], $context)[$orderId];
    }

    /**
     * @return array<string, PickingRequest> An array with a PickingRequest for each passed order ID, the key
     *     is the order ID
     */
    public function createPickingRequestsForOrders(array $orderIds, Context $context): array
    {
        $orderLineItemCriteria = new Criteria();
        $orderLineItemCriteria
            ->addFilter(new EqualsAnyFilter('orderId', $orderIds))
            ->addFilter(new EqualsFilter('type', LineItem::PRODUCT_LINE_ITEM_TYPE))
            ->addFilter(new NotFilter('AND', [new EqualsFilter('productId', null)]))
            ->addAssociation('product.pickwareErpPickwareProduct');
        /** @var OrderLineItemCollection $orderLineItems */
        $orderLineItems = $this->entityManager->findBy(
            OrderLineItemDefinition::class,
            $orderLineItemCriteria,
            $context,
        );

        $returnOrderLineItemCriteria = new Criteria();
        $returnOrderLineItemCriteria
            ->addFilter(new EqualsAnyFilter('returnOrder.orderId', $orderIds))
            ->addFilter(new EqualsFilter('type', ReturnOrderLineItemDefinition::TYPE_PRODUCT))
            ->addFilter(new NotFilter('AND', [new EqualsFilter('productId', null)]));
        /** @var ReturnOrderLineItemEntity[] $returnOrderLineItems */
        $returnOrderLineItems = $this->entityManager->findBy(
            ReturnOrderLineItemDefinition::class,
            $returnOrderLineItemCriteria,
            $context,
            ['returnOrder'],
        );

        /** @var StockEntity[] $orderStocks */
        $orderStocks = $this->entityManager->findBy(StockDefinition::class, ['orderId' => $orderIds], $context);

        /**
         * @var array<string, array<string, int>> $quantitiesToPick
         *     first key: order ID, second key: product ID, value: quantity to pick
         */
        $quantitiesToPick = array_combine($orderIds, array_fill(0, count($orderIds), []));
        $productNumbers = [];
        $isStockManagementDisabledByProductIds = [];
        foreach ($orderLineItems as $orderLineItem) {
            $orderId = $orderLineItem->getOrderId();
            $quantitiesToPick[$orderId][$orderLineItem->getProductId()] ??= 0;
            $quantitiesToPick[$orderId][$orderLineItem->getProductId()] += $orderLineItem->getQuantity();
            $productNumbers[$orderLineItem->getProductId()] = $orderLineItem->getProduct()->getProductNumber();

            // Check if the extension is loaded. If not we just use the fallback as "false" as this is the normal
            // behavior for the picking request
            $isStockManagementDisabled = $orderLineItem->getProduct()
                ->getExtension('pickwareErpPickwareProduct') ? $orderLineItem->getProduct()
                ->getExtension('pickwareErpPickwareProduct')->getIsStockManagementDisabled() : false;
            $isStockManagementDisabledByProductIds[$orderLineItem->getProductId()] = $isStockManagementDisabled;
        }
        foreach ($returnOrderLineItems as $returnOrderLineItem) {
            $orderId = $returnOrderLineItem->getReturnOrder()->getOrderId();
            $quantitiesToPick[$orderId][$returnOrderLineItem->getProductId()] ??= 0;
            $quantitiesToPick[$orderId][$returnOrderLineItem->getProductId()] -= $returnOrderLineItem->getQuantity();
        }
        foreach ($orderStocks as $orderStock) {
            $orderId = $orderStock->getOrderId();
            $quantitiesToPick[$orderId][$orderStock->getProductId()] ??= 0;
            $quantitiesToPick[$orderId][$orderStock->getProductId()] -= $orderStock->getQuantity();
        }

        return array_map(function (array $quantitiesToPick) use (
            $productNumbers,
            $isStockManagementDisabledByProductIds
        ) {
            $productPickingRequests = [];
            foreach ($quantitiesToPick as $productId => $quantityToPick) {
                if ($quantityToPick <= 0) {
                    continue;
                }
                $productPickingRequests[] = new ProductPickingRequest(
                    $productId,
                    $quantityToPick,
                    [],
                    ['productNumber' => $productNumbers[$productId] ?? null],
                    $isStockManagementDisabledByProductIds[$productId],
                );
            }

            return new PickingRequest($productPickingRequests);
        }, $quantitiesToPick);
    }
}
