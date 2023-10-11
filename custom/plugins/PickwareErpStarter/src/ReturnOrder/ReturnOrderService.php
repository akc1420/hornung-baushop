<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareErpStarter\ReturnOrder;

use Pickware\DalBundle\EntityManager;
use Pickware\PickwareErpStarter\ReturnOrder\Model\ReturnOrderCollection;
use Pickware\PickwareErpStarter\ReturnOrder\Model\ReturnOrderDefinition;
use Pickware\PickwareErpStarter\ReturnOrder\Model\ReturnOrderEntity;
use Pickware\PickwareErpStarter\ReturnOrder\Model\ReturnOrderLineItemDefinition;
use Pickware\PickwareErpStarter\ReturnOrder\Model\ReturnOrderLineItemEntity;
use Pickware\PickwareErpStarter\Stock\Model\StockDefinition;
use Pickware\PickwareErpStarter\Stock\Model\StockEntity;
use Pickware\PickwareErpStarter\StockApi\StockLocationReference;
use Pickware\PickwareErpStarter\StockApi\StockMovement;
use Pickware\PickwareErpStarter\StockApi\StockMovementService;
use Pickware\PickwareErpStarter\Stocking\ProductQuantity;
use Pickware\PickwareErpStarter\Stocking\StockingRequest;
use Pickware\PickwareErpStarter\Stocking\StockingStrategy;
use Pickware\ShopwareExtensionsBundle\StateTransitioning\StateTransitionService;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Order\OrderCollection;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\NumberRange\ValueGenerator\NumberRangeValueGeneratorInterface;
use Shopware\Core\System\StateMachine\StateMachineRegistry;

class ReturnOrderService
{
    private EntityManager $entityManager;
    private StockMovementService $stockMovementService;
    private ReturnOrderRefundService $returnOrderRefundService;
    private StateTransitionService $stateTransitionService;
    private NumberRangeValueGeneratorInterface $numberRangeValueGenerator;
    private ReturnOrderPriceCalculationService $returnOrderPriceCalculationService;
    private StateMachineRegistry $stateMachineRegistry;
    private StockingStrategy $stockingStrategy;

    public function __construct(
        EntityManager $entityManager,
        StockMovementService $stockMovementService,
        ReturnOrderRefundService $returnOrderRefundService,
        StateTransitionService $stateTransitionService,
        NumberRangeValueGeneratorInterface $numberRangeValueGenerator,
        ReturnOrderPriceCalculationService $returnOrderPriceCalculationService,
        StateMachineRegistry $stateMachineRegistry,
        StockingStrategy $stockingStrategy
    ) {
        $this->entityManager = $entityManager;
        $this->stockMovementService = $stockMovementService;
        $this->returnOrderRefundService = $returnOrderRefundService;
        $this->stateTransitionService = $stateTransitionService;
        $this->numberRangeValueGenerator = $numberRangeValueGenerator;
        $this->returnOrderPriceCalculationService = $returnOrderPriceCalculationService;
        $this->stateMachineRegistry = $stateMachineRegistry;
        $this->stockingStrategy = $stockingStrategy;
    }

    /**
     * @return array (map) for each order: quantity that can be returned by product id (not order line item id)
     */
    public function getReturnableOrderLineItems(array $orderIds, Context $context): array
    {
        $stocks = $this->entityManager->findBy(
            StockDefinition::class,
            ['orderId' => $orderIds],
            $context,
        )->getElements();

        $stocksByOrder = array_fill_keys($orderIds, []);
        /** @var StockEntity $stock */
        foreach ($stocks as $stock) {
            $stocksByOrder[$stock->getOrderId()][$stock->getProductId()] = $stock->getQuantity();
        }

        return $stocksByOrder;
    }

    /**
     * @deprecated next-major wil be removed. Use `requestReturnOrders` instead
     */
    public function requestReturnOrder(array $returnOrderPayload, Context $context, ?string $userId = null): void
    {
        $this->requestReturnOrders([$returnOrderPayload], $context, $userId);
    }

    public function requestReturnOrders(array $returnOrderPayloads, Context $context, ?string $userId = null): void
    {
        $orderIds = array_unique(array_column($returnOrderPayloads, 'orderId'));
        /** @var OrderCollection $orders */
        $orders = $this->entityManager->findBy(
            OrderDefinition::class,
            ['id' => $orderIds],
            $context,
        );
        if (count($orderIds) > $orders->count()) {
            throw ReturnOrderException::orderNotFound($orderIds, $orders->getKeys());
        }
        $initialReturnOrderStateMachineStateId = $this->stateMachineRegistry->getInitialState(
            ReturnOrderStateMachine::TECHNICAL_NAME,
            $context,
        )->getId();

        foreach ($returnOrderPayloads as &$returnOrderPayload) {
            $returnOrderPayload['userId'] = $userId;
            $returnOrderPayload['number'] = $this->numberRangeValueGenerator
                ->getValue(ReturnOrderNumberRange::TECHNICAL_NAME, $context, null);

            // Prices are recalculated after entity creation
            /** @var OrderEntity $order */
            $order = $orders->get($returnOrderPayload['orderId']);
            $returnOrderPayload['price'] = $this->returnOrderPriceCalculationService->createEmptyCartPrice(
                $order->getPrice()->getTaxStatus(),
            );
            if (isset($returnOrderPayload['lineItems'])) {
                foreach ($returnOrderPayload['lineItems'] as &$lineItem) {
                    $lineItem['price'] = $this->returnOrderPriceCalculationService->createEmptyCalculatedPrice();
                    if (!isset($lineItem['reason'])) {
                        $lineItem['reason'] = ReturnOrderLineItemDefinition::REASON_UNKNOWN;
                    }
                }
            }

            $returnOrderPayload['stateId'] = $initialReturnOrderStateMachineStateId;
        }

        $this->entityManager->runInTransactionWithRetry(
            function () use ($context, $returnOrderPayloads): void {
                $returnOrderIds = array_column($returnOrderPayloads, 'id');
                $this->entityManager->create(ReturnOrderDefinition::class, $returnOrderPayloads, $context);
                $this->returnOrderRefundService->createRefundForReturnOrders($returnOrderIds, $context);
                $this->returnOrderPriceCalculationService->recalculateReturnOrders($returnOrderIds, $context);
            },
        );
    }

    /**
     * @deprecated next-major will be removed. Use `approveReturnOrders` instead.
     */
    public function approveReturnOrder(string $returnOrderId, Context $context, ?string $userId = null): void
    {
        $this->approveReturnOrders([$returnOrderId], $context);
    }

    /**
     * @param String[] $returnOrderIds
     */
    public function approveReturnOrders(array $returnOrderIds, Context $context): void
    {
        foreach ($returnOrderIds as $returnOrderId) {
            $this->stateTransitionService->transitionState(
                ReturnOrderDefinition::ENTITY_NAME,
                $returnOrderId,
                ReturnOrderStateMachine::TRANSITION_APPROVE,
                'stateId',
                $context,
            );
        }
    }

    /**
     * Moves the given stock quantities into the return order. The corresponding order is used as stock source for as
     * much stock as possible. If the stock in the order does not suffice, stock from the unknown stock location is
     * moved into the return order until the given stock quantity is achieved.
     *
     * No restock/dispose values are set or used here.
     *
     * @param array<string, array<string, int>> $lineItemQuantitiesByReturnOrderId
     */
    public function moveStockIntoReturnOrders(
        array $lineItemQuantitiesByReturnOrderId,
        Context $context,
        ?string $userId = null
    ): void {
        $returnOrderIds = array_keys($lineItemQuantitiesByReturnOrderId);
        /** @var ReturnOrderCollection $returnOrders */
        $returnOrders = $this->entityManager->findBy(
            ReturnOrderDefinition::class,
            ['id' => $returnOrderIds],
            $context,
            [
                'lineItems',
                'stocks',
            ],
        );
        if (count($returnOrderIds) > $returnOrders->count()) {
            throw ReturnOrderException::returnOrderNotFound($returnOrderIds, $returnOrders->getKeys());
        }

        $orderIds = array_values($returnOrders->map(fn (ReturnOrderEntity $returnOrder) => $returnOrder->getOrderId()));

        $returnableStockByOrderId = $this->getReturnableOrderLineItems(
            $orderIds,
            $context,
        );

        $stockMovements = [];
        foreach ($returnOrders as $returnOrder) {
            $newStockMovements = array_filter(
                array_values(array_map(
                    // Use $returnableStockByOrderId by reference because the stock needs to be reduced when move stock
                    // from orders. This because multiple return orders can exist for the same order.
                    function (ReturnOrderLineItemEntity $lineItem) use ($returnOrder, $userId, &$returnableStockByOrderId, $lineItemQuantitiesByReturnOrderId): array {
                        $stockInOrder = $returnableStockByOrderId[$returnOrder->getOrderId()][$lineItem->getProductId()] ?? 0;
                        $returnOrderLineItemQuantities = $lineItemQuantitiesByReturnOrderId[$returnOrder->getId()];

                        if (!array_key_exists($lineItem->getId(), $returnOrderLineItemQuantities)) {
                            return [];
                        }
                        $returnQuantity = $returnOrderLineItemQuantities[$lineItem->getId()];

                        if ($stockInOrder < $returnQuantity) {
                            // If there is not enough stock for the returned quantity in the order, we move all existing
                            // stock from the order to the return order and the remaining difference from unknown to
                            // return order directly.
                            $returnQuantityFromOrder = $stockInOrder;
                            $returnQuantityFromUnknown = $returnQuantity - $stockInOrder;
                        } else {
                            // Move all returned quantity from the order to the return order otherwise.
                            $returnQuantityFromOrder = $returnQuantity;
                            $returnQuantityFromUnknown = 0;
                        }
                        $returnableStockByOrderId[$returnOrder->getOrderId()][$lineItem->getProductId()] = $stockInOrder - $returnQuantityFromOrder;

                        $stockMovements = [];
                        if ($returnQuantityFromUnknown > 0) {
                            $stockMovements[] = StockMovement::create([
                                'productId' => $lineItem->getProductId(),
                                'quantity' => $returnQuantityFromUnknown,
                                'source' => StockLocationReference::unknown(),
                                'destination' => StockLocationReference::returnOrder($returnOrder->getId()),
                                'userId' => $userId,
                            ]);
                        }
                        if ($returnQuantityFromOrder > 0) {
                            $stockMovements[] = StockMovement::create([
                                'productId' => $lineItem->getProductId(),
                                'quantity' => $returnQuantityFromOrder,
                                'source' => StockLocationReference::order($returnOrder->getOrderId()),
                                'destination' => StockLocationReference::returnOrder($returnOrder->getId()),
                                'userId' => $userId,
                            ]);
                        }

                        return $stockMovements;
                    },
                    $returnOrder->getLineItems()->filter(
                        fn (ReturnOrderLineItemEntity $lineItem) => $lineItem->getType() === LineItem::PRODUCT_LINE_ITEM_TYPE && $lineItem->getQuantity() > 0,
                    )->getElements(),
                )),
            );
            $stockMovements = array_merge($stockMovements, ...$newStockMovements);
        }

        if (count($stockMovements) > 0) {
            $this->stockMovementService->moveStock($stockMovements, $context);
        }
    }

    /**
     * @deprecated next-major will be removed. Use `moveStockIntoReturnOrders` instead.
     */
    public function moveMissingStockFromOrderIntoReturnOrder(
        string $returnOrderId,
        Context $context,
        ?string $userId = null
    ): void {
        $this->moveMissingStockFromOrderIntoReturnOrders([$returnOrderId], $context, $userId);
    }

    /**
     * @deprecated next-major will be removed. Use `moveStockIntoReturnOrders` instead.
     */
    public function moveMissingStockFromOrderIntoReturnOrders(
        array $returnOrderIds,
        Context $context,
        ?string $userId = null
    ): void {
        $lineItemQuantitiesByReturnOrderId = $this->getReturnOrderLineItemQuantitiesByReturnOrderId($returnOrderIds, $context);
        $this->moveStockIntoReturnOrders(
            $lineItemQuantitiesByReturnOrderId,
            $context,
            $userId,
        );
    }

    /**
     * @deprecated next-major will be removed. Use `completeReturnOrders` instead.
     */
    public function completeReturnOrder(
        string $returnOrderId,
        Context $context
    ): void {
        $this->completeReturnOrders([$returnOrderId], $context);
    }

    /**
     * @param String[] $returnOrderIds
     */
    public function completeReturnOrders(array $returnOrderIds, Context $context): void
    {
        foreach ($returnOrderIds as $returnOrderId) {
            $this->stateTransitionService->transitionState(
                ReturnOrderDefinition::ENTITY_NAME,
                $returnOrderId,
                ReturnOrderStateMachine::TRANSITION_COMPLETE,
                'stateId',
                $context,
            );
        }
    }

    /**
     * @deprecated next-major will be removed. Use `moveStockFromReturnOrders` instead.
     *
     * @param array<int, array{restock: int, dispose: int, productId: string}> $stockAdjustments
     */
    public function moveStockFromReturnOrder(
        string $returnOrderId,
        array $stockAdjustments,
        Context $context,
        ?string $userId = null
    ): void {
        $this->moveStockFromReturnOrders(
            [$returnOrderId => $stockAdjustments],
            $context,
            $userId,
        );
    }

    public function moveStockFromReturnOrders(
        array $stockAdjustmentsByReturnOrderId,
        Context $context,
        ?string $userId = null
    ): void {
        $returnOrderIds = array_keys($stockAdjustmentsByReturnOrderId);
        /** @var ReturnOrderCollection $returnOrders */
        $returnOrders = $this->entityManager->findBy(
            ReturnOrderDefinition::class,
            ['id' => $returnOrderIds],
            $context,
        );
        if (count($returnOrderIds) > $returnOrders->count()) {
            throw ReturnOrderException::returnOrderNotFound($returnOrderIds, $returnOrders->getKeys());
        }

        $stockMovements = [];
        foreach ($returnOrders as $returnOrder) {
            $stockMovements = array_merge(
                $stockMovements,
                $this->parseStockAdjustments(
                    $stockAdjustmentsByReturnOrderId[$returnOrder->getId()],
                    $returnOrder->getId(),
                    $returnOrder->getWarehouseId(),
                    $context,
                    $userId,
                ),
            );
        }

        if (count($stockMovements) > 0) {
            $this->stockMovementService->moveStock($stockMovements, $context);
        }
    }

    /**
     * @return StockMovement[]
     */
    private function parseStockAdjustments(
        array $stockAdjustments,
        string $returnOrderId,
        string $warehouseId,
        Context $context,
        ?string $userId = null
    ): array {
        $stockMovements = [];
        $productQuantitiesRestock = [];
        foreach ($stockAdjustments as $stockAdjustment) {
            if ($stockAdjustment['restock'] > 0) {
                $productQuantitiesRestock[] = new ProductQuantity(
                    $stockAdjustment['productId'],
                    $stockAdjustment['restock'],
                );
            }
            if ($stockAdjustment['dispose'] > 0) {
                $stockMovements[] = StockMovement::create([
                    'productId' => $stockAdjustment['productId'],
                    'quantity' => $stockAdjustment['dispose'],
                    'source' => StockLocationReference::returnOrder($returnOrderId),
                    'destination' => StockLocationReference::unknown(),
                    'userId' => $userId,
                ]);
            }
        }

        // Add stock movements into warehouse using the stocking strategy
        return array_merge(
            $stockMovements,
            $this->stockingStrategy
                ->calculateStockingSolution(new StockingRequest($productQuantitiesRestock, $warehouseId), $context)
                ->createStockMovementsWithSource(StockLocationReference::returnOrder($returnOrderId)),
        );
    }

    /**
     * @return array<string, array<string, int>> return order line item quantities by return order id. e.g.:
     *   [
     *     return-order-id-1: [
     *       return-order-line-item-id-1: 5,
     *       return-order-line-item-id-1: 10,
     *     ],
     *   ]
     */
    public function getReturnOrderLineItemQuantitiesByReturnOrderId(array $returnOrderIds, Context $context): array
    {
        /** @var ReturnOrderCollection $returnOrders */
        $returnOrders = $this->entityManager->findBy(
            ReturnOrderDefinition::class,
            ['id' => $returnOrderIds],
            $context,
            [
                'lineItems',
            ],
        );

        if (count($returnOrderIds) > $returnOrders->count()) {
            throw ReturnOrderException::returnOrderNotFound($returnOrderIds, $returnOrders->getKeys());
        }

        $lineItemQuantitiesByReturnOrderId = [];
        foreach ($returnOrders as $returnOrder) {
            $lineItemQuantities = array_filter(
                array_map(
                    fn (ReturnOrderLineItemEntity $lineItem): int => $lineItem->getQuantity(),
                    $returnOrder->getLineItems()->filter(
                        fn (ReturnOrderLineItemEntity $lineItem) => $lineItem->getType() === LineItem::PRODUCT_LINE_ITEM_TYPE && $lineItem->getQuantity() > 0,
                    )->getElements(),
                ),
            );
            $lineItemQuantitiesByReturnOrderId[$returnOrder->getId()] = $lineItemQuantities;
        }

        return $lineItemQuantitiesByReturnOrderId;
    }
}
