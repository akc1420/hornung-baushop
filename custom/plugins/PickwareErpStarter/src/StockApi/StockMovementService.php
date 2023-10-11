<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareErpStarter\StockApi;

use Pickware\DalBundle\EntityManager;
use Pickware\PickwareErpStarter\Product\Model\PickwareProductCollection;
use Pickware\PickwareErpStarter\Product\Model\PickwareProductDefinition;
use Pickware\PickwareErpStarter\Product\Model\PickwareProductEntity;
use Pickware\PickwareErpStarter\Stock\Model\LocationTypeDefinition;
use Pickware\PickwareErpStarter\Stock\Model\SpecialStockLocationDefinition;
use Pickware\PickwareErpStarter\Stock\Model\StockDefinition;
use Pickware\PickwareErpStarter\Stock\Model\StockEntity;
use Pickware\PickwareErpStarter\Stock\Model\StockMovementDefinition;
use Shopware\Core\Framework\Api\Context\AdminApiSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;

class StockMovementService
{
    private EntityManager $entityManager;
    private StockLocationSnapshotGenerator $stockLocationSnapshotGenerator;

    public function __construct(
        EntityManager $entityManager,
        StockLocationSnapshotGenerator $stockLocationSnapshotGenerator
    ) {
        $this->entityManager = $entityManager;
        $this->stockLocationSnapshotGenerator = $stockLocationSnapshotGenerator;
    }

    /**
     * @param StockMovement[] $stockMovements
     */
    public function moveStock(array $stockMovements, Context $context): void
    {
        $locations = array_merge([], ...array_map(function (StockMovement $stockMovement) {
            return [
                $stockMovement->getSource(),
                $stockMovement->getDestination(),
            ];
        }, $stockMovements));
        $this->stockLocationSnapshotGenerator->generateSnapshots($locations, $context);

        // If no user responsible for the stock movement is set already, determine the user from the context if possible
        foreach ($stockMovements as $stockMovement) {
            if (!$stockMovement->getUserId()) {
                $stockMovement->setUserId($this->getUserIdFromAdminApiContext($context));
            }
        }

        $this->entityManager->runInTransactionWithRetry(
            function () use ($stockMovements, $context): void {
                $stockMovementPayloads = array_map(fn (StockMovement $stockMovement) => $stockMovement->toPayload(), $stockMovements);

                $productIds = array_map(fn (StockMovement $stockMovement) => $stockMovement->getProductId(), $stockMovements);
                $this->entityManager->lockPessimistically(StockDefinition::class, ['productId' => $productIds], $context);

                // Validation before stock movements are written
                $this->validateSourceAndDestinationLocationTypes($stockMovements);
                $this->throwIfStockIsMovedIntoBinLocationForNonStockManagedProducts($stockMovements, $context);

                $context->scope(
                    Context::SYSTEM_SCOPE,
                    function (Context $context) use ($stockMovementPayloads): void {
                        // Use array_values if (e.g. due to filtering) the input stock movements did not have strict ascending numerical array keys
                        $this->entityManager->create(
                            StockMovementDefinition::class,
                            array_values($stockMovementPayloads),
                            $context,
                        );
                    },
                );

                // Validation after stock movements are written
                $this->throwIfNegativeStockLocationsExist($stockMovements, $context);
            },
        );
    }

    /**
     * @param StockMovement[] $stockMovements
     */
    private function throwIfNegativeStockLocationsExist(array $stockMovements, Context $context):void
    {
        $conditions = [];
        foreach ($stockMovements as $stockMovement) {
            $sourceLocation = $stockMovement->getSource();
            if ($sourceLocation->getLocationTypeTechnicalName() === LocationTypeDefinition::TECHNICAL_NAME_SPECIAL_STOCK_LOCATION) {
                continue;
            }
            $conditions[] = new MultiFilter('AND', [
                new EqualsFilter('productId', $stockMovement->getProductId()),
                $sourceLocation->getFilterForStockDefinition(),
            ]);
        }

        if (count($conditions) === 0) {
            return;
        }

        $criteria = new Criteria();
        $criteria->addFilter(new MultiFilter('AND', [
            new MultiFilter('OR', $conditions),
            new EqualsFilter('product.pickwareErpPickwareProduct.isStockManagementDisabled', false),
            new RangeFilter('quantity', [RangeFilter::LT => 0]),
        ]));

        $negativeStocks = $this->entityManager->findBy(StockDefinition::class, $criteria, $context);

        if (count($negativeStocks) > 0) {
            $stockLocationReferences = $negativeStocks->map(fn (StockEntity $stock) => $stock->createStockLocationReference());
            $productIds = array_values($negativeStocks->map(fn (StockEntity $stock) => $stock->getProductId()));

            throw StockMovementServiceValidationException::operationLeadsToNegativeStocks(
                $stockLocationReferences,
                $productIds,
            );
        }
    }

    private function validateBeforeStockMovementWritten(array $stockMovements, Context $context): void
    {
    }

    private function getUserIdFromAdminApiContext(Context $context): ?string
    {
        $contextSource = $context->getSource();
        if ($contextSource instanceof AdminApiSource) {
            return $contextSource->getUserId();
        }

        return null;
    }

    /**
     * Validates all source and destination stock location types for valid pairs. Throws a
     * StockMovementServiceValidationException when an invalid combination of stock locations is used.
     *
     * @param StockMovement[] $stockMovements
     */
    private function validateSourceAndDestinationLocationTypes(array $stockMovements): void
    {
        $invalidCombinations = [];
        foreach ($stockMovements as $stockMovement) {
            $source = $stockMovement->getSource()->getLocationTypeTechnicalName();
            $destination = $stockMovement->getDestination()->getLocationTypeTechnicalName();

            if ($destination === LocationTypeDefinition::TECHNICAL_NAME_RETURN_ORDER) {
                if ($stockMovement->getSource()->getLocationTypeTechnicalName() === LocationTypeDefinition::TECHNICAL_NAME_ORDER) {
                    continue;
                }
                if ($stockMovement->getSource()->getLocationTypeTechnicalName() === LocationTypeDefinition::TECHNICAL_NAME_SPECIAL_STOCK_LOCATION
                    && $stockMovement->getSource()->getPrimaryKey() === SpecialStockLocationDefinition::TECHNICAL_NAME_UNKNOWN) {
                    continue;
                }

                $invalidCombinations[] = [
                    'source' => $source,
                    'destination' => $destination,
                ];
            }
        }

        if (count($invalidCombinations) > 0) {
            throw StockMovementServiceValidationException::invalidCombinationOfSourceAndDestinationStockLocations(
                $invalidCombinations,
            );
        }
    }

    /**
     * Validates that no stock movement is written for not-stock-managed products into a bin location.
     *
     * @param StockMovement[] $stockMovements
     */
    public function throwIfStockIsMovedIntoBinLocationForNonStockManagedProducts(array $stockMovements, Context $context): void
    {
        $productIdsWithBinLocationDestination = array_unique(array_filter(array_map(
            function (StockMovement $stockMovement): ?string {
                if ($stockMovement->getDestination()->getLocationTypeTechnicalName() === LocationTypeDefinition::TECHNICAL_NAME_BIN_LOCATION) {
                    return $stockMovement->getProductId();
                }

                return null;
            },
            $stockMovements,
        )));
        if (count($productIdsWithBinLocationDestination) === 0) {
            return;
        }
        /** @var PickwareProductCollection $products */
        $pickwareProducts = $this->entityManager->findBy(
            PickwareProductDefinition::class,
            ['productId' => $productIdsWithBinLocationDestination],
            $context,
        );
        $notStockManagedProductIds = array_unique($pickwareProducts->fmap(
            fn (PickwareProductEntity $pickwareProduct) => $pickwareProduct->getIsStockManagementDisabled() ? $pickwareProduct->getProductId() : null,
        ));
        if (count($notStockManagedProductIds) > 0) {
            throw StockMovementServiceValidationException::operationMovesStockToBinLocationsForNotStockManagedProducts($notStockManagedProductIds);
        }
    }
}
