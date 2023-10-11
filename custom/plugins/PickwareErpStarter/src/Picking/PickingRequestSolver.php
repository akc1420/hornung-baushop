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

use LogicException;
use Pickware\DalBundle\EntityManager;
use Pickware\PickwareErpStarter\OrderShipping\ProductQuantityLocation;
use Pickware\PickwareErpStarter\Warehouse\Model\BinLocationCollection;
use Pickware\PickwareErpStarter\Warehouse\Model\BinLocationDefinition;
use Pickware\PickwareErpStarter\Warehouse\Model\BinLocationEntity;
use Pickware\PickwareErpStarter\Warehouse\Model\WarehouseCollection;
use Pickware\PickwareErpStarter\Warehouse\Model\WarehouseDefinition;
use Pickware\PickwareErpStarter\Warehouse\Model\WarehouseEntity;
use Shopware\Core\Framework\Context;

class PickingRequestSolver
{
    private EntityManager $entityManager;
    private PickingStrategy $pickingStrategy;
    private RoutingStrategy $routingStrategy;
    private PickableStockProvider $pickableStockProvider;

    public function __construct(
        EntityManager $entityManager,
        PickingStrategy $pickingStrategy,
        RoutingStrategy $routingStrategy,
        ?PickableStockProvider $pickableStockProvider
    ) {
        $this->entityManager = $entityManager;
        $this->pickingStrategy = $pickingStrategy;
        $this->routingStrategy = $routingStrategy;
        if ($pickableStockProvider instanceof PickableStockProvider) {
            // Parameter is marked optional so the constructor is backwards-compatible
            $this->pickableStockProvider = $pickableStockProvider;
        }
    }

    public function solvePickingRequestInWarehouses(
        PickingRequest $pickingRequest,
        ?array $warehouseIds,
        Context $context
    ): PickingRequest {
        $this->assignPickableStockToPickingRequest($pickingRequest, $warehouseIds, $context);
        $this->pickingStrategy->apply($pickingRequest);
        $this->pickingStrategy->assignQuantityToPick($pickingRequest);
        $this->routingStrategy->apply($pickingRequest);

        return $pickingRequest;
    }

    /**
     * Assigns the pickable stock as PickLocation objects in the PickingRequest.
     *
     * This is basically only a conversion between different types, because the current picking strategy works with
     * PickLocation objects.
     */
    private function assignPickableStockToPickingRequest(
        PickingRequest $pickingRequest,
        ?array $warehouseIds,
        Context $context
    ): void {
        $pickableStock = $this->pickableStockProvider->getPickableStocks(
            $pickingRequest->getProductIds(),
            $warehouseIds,
            $context,
        );

        // The PickLocation contains information like bin location code and warehouse code. This information have to be
        // fetched here because the interface of the `PickableStockProvider` only returns the IDs of the locations.
        $binLocationIds = $pickableStock
            ->filter(fn (ProductQuantityLocation $element) => $element->getStockLocationReference()->isBinLocation())
            ->map(fn (ProductQuantityLocation $element) => $element->getStockLocationReference()->getPrimaryKey())
            ->deduplicate()
            ->asArray();
        $warehouseIds = $pickableStock
            ->filter(fn (ProductQuantityLocation $element) => $element->getStockLocationReference()->isWarehouse())
            ->map(fn (ProductQuantityLocation $element) => $element->getStockLocationReference()->getPrimaryKey())
            ->deduplicate()
            ->asArray();
        /** @var BinLocationCollection $binLocations */
        $binLocations = $this->entityManager->findBy(
            BinLocationDefinition::class,
            ['id' => $binLocationIds],
            $context,
            ['warehouse'],
        );
        /** @var WarehouseCollection $warehouses */
        $warehouses = $this->entityManager->findBy(WarehouseDefinition::class, ['id' => $warehouseIds], $context);

        foreach ($pickingRequest->getElements() as $productPickingRequest) {
            $pickLocations = $pickableStock
                ->filter(fn (ProductQuantityLocation $stock) => $stock->getProductId() === $productPickingRequest->getProductId())
                ->map(function (ProductQuantityLocation $stock) use ($warehouses, $binLocations): PickLocation {
                    $stockLocation = $stock->getStockLocationReference();
                    if ($stockLocation->isBinLocation()) {
                        /** @var BinLocationEntity $binLocation Variable cannot be null here */
                        $binLocation = $binLocations->get($stockLocation->getPrimaryKey());
                        $warehouse = $binLocation->getWarehouse();
                    } elseif ($stockLocation->isWarehouse()) {
                        $binLocation = null;
                        /** @var WarehouseEntity $binLocation Variable cannot be null here */
                        $warehouse = $warehouses->get($stockLocation->getPrimaryKey());
                    } else {
                        throw new LogicException(
                            'Expected locations of type warehouse or bin location, got: '
                            . $stockLocation->getLocationTypeTechnicalName(),
                        );
                    }

                    $pickLocation = new PickLocation(
                        $stockLocation->getLocationTypeTechnicalName(),
                        new PickLocationWarehouse(
                            $warehouse->getId(),
                            $warehouse->getName(),
                            $warehouse->getCode(),
                            $warehouse->getIsDefault(),
                            $warehouse->getCreatedAt(),
                        ),
                        $stock->getQuantity(),
                    );

                    if ($stockLocation->isBinLocation()) {
                        $pickLocation->setBinLocationId($binLocation->getId());
                        $pickLocation->setBinLocationCode($binLocation->getCode());
                    }

                    return $pickLocation;
                });

            $productPickingRequest->setPickLocations($pickLocations->asArray());
        }
    }

    public function usesProductOrthogonalPickingStrategy(): bool
    {
        return ($this->pickingStrategy instanceof ProductOrthogonalPickingStrategy);
    }
}
