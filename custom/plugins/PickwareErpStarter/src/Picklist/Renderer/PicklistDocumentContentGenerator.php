<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareErpStarter\Picklist\Renderer;

use Pickware\DalBundle\EntityManager;
use Pickware\PickwareErpStarter\Picking\PickingRequest;
use Pickware\PickwareErpStarter\Picking\ProductPickingRequest;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;

class PicklistDocumentContentGenerator
{
    private const NUMBER_OF_DISPLAYED_PICK_LOCATIONS_PER_PRODUCT = 4;

    private const ALLOWED_ORDER_LINE_TYPES = [
        LineItem::PRODUCT_LINE_ITEM_TYPE,
        LineItem::CUSTOM_LINE_ITEM_TYPE,
    ];

    private EntityManager $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function createDocumentPickingRouteNodes(
        PickingRequest $pickingRequest,
        array $orderLineItemIds,
        Context $context
    ): array {
        $productContext = Context::createFrom($context);
        $productContext->setConsiderInheritance(true);

        $orderLineItems = $this->entityManager->findBy(
            OrderLineItemDefinition::class,
            ['id' => $orderLineItemIds],
            $context,
            [
                'product',
            ],
        );

        $allowedOrderLineItems = array_filter(
            $orderLineItems->getElements(),
            fn (OrderLineItemEntity $orderLineItem) => in_array($orderLineItem->getType(), self::ALLOWED_ORDER_LINE_TYPES),
        );

        $pickingNodes = array_filter(array_map(
            function (ProductPickingRequest $pickingRequest) use ($allowedOrderLineItems, $productContext) {
                return $this->mapPickLocationsFromPickingRequestToOrderLineItems(
                    $pickingRequest,
                    $allowedOrderLineItems,
                    $productContext,
                );
            },
            $pickingRequest->getElements(),
        ));

        foreach ($allowedOrderLineItems as $orderLineItem) {
            // If an order line item is a custom line item, or it got deleted it should be displayed on the picklist,
            // with its quantity. Therefore, we move it in front of the picklist.
            if ($orderLineItem->getType() === LineItem::CUSTOM_LINE_ITEM_TYPE
                || $orderLineItem->getProduct() === null
            ) {
                array_unshift($pickingNodes, [
                    'orderLineItems' => [$orderLineItem],
                    'pickLocations' => [],
                    'quantity' => $orderLineItem->getQuantity(),
                ]);
            }
        }

        return $pickingNodes;
    }

    private function mapPickLocationsFromPickingRequestToOrderLineItems(
        ProductPickingRequest $productPickingRequest,
        array $allowedOrderLineItems,
        Context $productContext
    ): ?array {
        $orderLineItems = [];
        // Truncate each picking request to only display the first n pick locations
        $pickLocations = array_slice(
            $productPickingRequest->getPickLocations(),
            0,
            self::NUMBER_OF_DISPLAYED_PICK_LOCATIONS_PER_PRODUCT,
        );

        /** @var ProductEntity $product */
        $product = $this->entityManager->findByPrimaryKey(
            ProductDefinition::class,
            $productPickingRequest->getProductId(),
            $productContext,
            ['options.group'],
        );

        foreach ($allowedOrderLineItems as $allowedOrderLineItem) {
            // We are only matching items by product id. It is technically possible that the order contains the
            // same product multiple times. We match the same product picking request in this case.
            if ($allowedOrderLineItem->getProductId() !== $productPickingRequest->getProductId()) {
                continue;
            }

            $allowedOrderLineItem->setProduct($product);

            $orderLineItems[] = $allowedOrderLineItem;
        }

        $pickingNode = [
            'orderLineItems' => $orderLineItems,
            'pickLocations' => $pickLocations,
            'quantity' => $productPickingRequest->getQuantity(),
        ];

        return count($orderLineItems) > 0 ? $pickingNode : null;
    }
}
