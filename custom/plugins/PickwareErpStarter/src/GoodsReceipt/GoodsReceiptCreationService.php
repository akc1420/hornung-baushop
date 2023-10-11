<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareErpStarter\GoodsReceipt;

use InvalidArgumentException;
use Pickware\DalBundle\EntityManager;
use Pickware\PickwareErpStarter\GoodsReceipt\Model\GoodsReceiptDefinition;
use Pickware\PickwareErpStarter\Product\ProductNameFormatterService;
use Pickware\PickwareErpStarter\StockApi\StockLocationReference;
use Pickware\PickwareErpStarter\StockApi\StockMovement;
use Pickware\PickwareErpStarter\StockApi\StockMovementService;
use Pickware\PickwareErpStarter\Stocking\ProductQuantity;
use Pickware\PickwareErpStarter\Stocking\StockingRequestService;
use Pickware\PickwareErpStarter\Stocking\StockingStrategy;
use Pickware\PickwareErpStarter\SupplierOrder\Model\SupplierOrderDefinition;
use Pickware\PickwareErpStarter\SupplierOrder\Model\SupplierOrderEntity;
use Pickware\PickwareErpStarter\SupplierOrder\Model\SupplierOrderLineItemEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\NumberRange\ValueGenerator\NumberRangeValueGeneratorInterface;
use Shopware\Core\System\StateMachine\Loader\InitialStateIdLoader;
use Shopware\Core\System\User\UserDefinition;
use Shopware\Core\System\User\UserEntity;

class GoodsReceiptCreationService
{
    private EntityManager $entityManager;
    private GoodsReceiptPriceCalculationService $goodsReceiptPriceCalculationService;
    private NumberRangeValueGeneratorInterface $numberRangeValueGenerator;
    private InitialStateIdLoader $initialStateIdLoader;
    private ProductNameFormatterService $productNameFormatterService;
    private StockingRequestService $stockingRequestService;
    private StockingStrategy $stockingStrategy;
    private StockMovementService $stockMovementService;

    public function __construct(
        EntityManager $entityManager,
        GoodsReceiptPriceCalculationService $goodsReceiptPriceCalculationService,
        NumberRangeValueGeneratorInterface $numberRangeValueGenerator,
        InitialStateIdLoader $initialStateIdLoader,
        ProductNameFormatterService $productNameFormatterService,
        StockingRequestService $stockingRequestService,
        StockingStrategy $stockingStrategy,
        StockMovementService $stockMovementService
    ) {
        $this->entityManager = $entityManager;
        $this->goodsReceiptPriceCalculationService = $goodsReceiptPriceCalculationService;
        $this->numberRangeValueGenerator = $numberRangeValueGenerator;
        $this->initialStateIdLoader = $initialStateIdLoader;
        $this->productNameFormatterService = $productNameFormatterService;
        $this->stockingRequestService = $stockingRequestService;
        $this->stockingStrategy = $stockingStrategy;
        $this->stockMovementService = $stockMovementService;
    }

    public function createGoodsReceiptForSupplierOrder(
        string $goodsReceiptId,
        string $supplierOrderId,
        array $lineItemQuantities,
        string $userId,
        Context $context
    ): void {
        $goodsReceiptPayload = $this->createGoodsReceiptPayload(
            $goodsReceiptId,
            $supplierOrderId,
            $lineItemQuantities,
            $userId,
            $context,
        );

        $this->entityManager->runInTransactionWithRetry(
            function () use ($context, $goodsReceiptPayload, $goodsReceiptId): void {
                $this->entityManager->create(GoodsReceiptDefinition::class, [$goodsReceiptPayload], $context);
                $this->goodsReceiptPriceCalculationService->recalculateGoodsReceipts([$goodsReceiptId], $context);
            },
        );

        $stockingRequest = $this->stockingRequestService->createStockingRequestForGoodsReceipt($goodsReceiptId, $context);

        // Create stock movements to move stock from unknown into the goods receipt based on all the necessary
        // quantities that are stocked into the warehouse
        $stockMovementsIntoGoodsReceipt = array_map(
            fn (ProductQuantity $productQuantity) => StockMovement::create([
                'productId' => $productQuantity->getProductId(),
                'quantity' => $productQuantity->getQuantity(),
                'source' => StockLocationReference::unknown(),
                'destination' => StockLocationReference::goodsReceipt($goodsReceiptPayload['id']),
                'userId' => $userId,
            ]),
            $stockingRequest->getProductQuantities(),
        );

        // Create stock movements from the supplier order into the warehouse
        $stockMovementsIntoWarehouse = $this->stockingStrategy
            ->calculateStockingSolution($stockingRequest, $context)
            ->createStockMovementsWithSource(
                StockLocationReference::goodsReceipt($goodsReceiptPayload['id']),
                ['userId' => $userId],
            );

        $this->stockMovementService->moveStock(
            [
                ...array_values($stockMovementsIntoGoodsReceipt),
                ...array_values($stockMovementsIntoWarehouse),
            ],
            $context,
        );
    }

    private function createGoodsReceiptPayload(
        string $goodsReceiptId,
        string $supplierOrderId,
        array $lineItemQuantities,
        string $userId,
        Context $context
    ): array {
        /** @var SupplierOrderEntity $supplierOrder */
        $supplierOrder = $this->entityManager->getByPrimaryKey(
            SupplierOrderDefinition::class,
            $supplierOrderId,
            $context,
            [
                'lineItems.product',
                'warehouse',
                'currency',
            ],
        );

        $productIds = $supplierOrder->getLineItems()->fmap(
            fn (SupplierOrderLineItemEntity $supplierOrderLineItem) =>  $supplierOrderLineItem->getProductId(),
        );
        $productNamesByProductId = $this->productNameFormatterService->getFormattedProductNames(array_values($productIds), [], $context);

        $goodsReceiptItems = [];
        foreach ($lineItemQuantities as $supplierOrderLineItemId => $quantity) {
            $supplierOrderLineItem = $supplierOrder->getLineItems()->get($supplierOrderLineItemId);
            if (!$supplierOrderLineItem) {
                throw new InvalidArgumentException(
                    sprintf('Supplier order line item with ID "%s" was not found', $supplierOrderLineItemId),
                );
            }

            $product = $supplierOrderLineItem->getProduct();
            $oldProductSnapshot = $supplierOrderLineItem->getProductSnapshot();

            $oldPriceDefinition = $supplierOrderLineItem->getPriceDefinition();
            $oldPriceDefinition->setQuantity($quantity);
            // Prices are recalculated after the entities have been created.

            $goodsReceiptItems[] = [
                'id' => Uuid::randomHex(),
                'productId' => $supplierOrderLineItem->getProductId(),
                'productVersionId' => $supplierOrderLineItem->getProductVersionId(),
                'productSnapshot' => [
                    'name' => $productNamesByProductId[$supplierOrderLineItem->getProductId()] ?? $oldProductSnapshot['name'] ?? '',
                    'productNumber' => $product ? $product->getProductNumber() : $oldProductSnapshot['productNumber'] ?? '',
                ],
                'quantity' => $quantity,
                'priceDefinition' => $oldPriceDefinition,
                'supplierOrderId' => $supplierOrderLineItem->getSupplierOrderId(),
            ];
        }

        $initialGoodsReceiptStateId = $this->initialStateIdLoader->get(GoodsReceiptStateMachine::TECHNICAL_NAME);
        $number = $this->numberRangeValueGenerator
            ->getValue(GoodsReceiptNumberRange::TECHNICAL_NAME, $context, null);

        /** @var UserEntity $user */
        $user = $this->entityManager->getByPrimaryKey(UserDefinition::class, $userId, $context);
        $taxStatus = $supplierOrder->getTaxStatus();

        return [
            'id' => $goodsReceiptId,
            'price' => $this->goodsReceiptPriceCalculationService->createEmptyCartPrice($taxStatus),
            'currencyId' => $supplierOrder->getCurrencyId(),
            'currencyFactor' => $supplierOrder->getCurrency()->getFactor(),
            'itemRounding' => $supplierOrder->getItemRounding()->getVars(),
            'totalRounding' => $supplierOrder->getTotalRounding()->getVars(),
            'taxStatus' => $taxStatus,
            'number' => $number,
            'userId' => $userId,
            'userSnapshot' => [
                'email' => $user->getEmail(),
                'username' => $user->getUsername(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
            ],
            'warehouseId' => $supplierOrder->getWarehouseId(),
            'warehouseSnapshot' => [
                'name' => $supplierOrder->getWarehouse()->getName(),
                'code' => $supplierOrder->getWarehouse()->getCode(),
            ],
            'stateId' => $initialGoodsReceiptStateId,
            'items' => $goodsReceiptItems,
        ];
    }
}
