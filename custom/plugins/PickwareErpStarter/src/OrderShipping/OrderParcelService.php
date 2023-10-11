<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareErpStarter\OrderShipping;

use InvalidArgumentException;
use Pickware\DalBundle\EntityManager;
use Pickware\PickwareErpStarter\Collection\ImmutableCollection;
use Pickware\PickwareErpStarter\Picking\PickingRequestFactory;
use Pickware\PickwareErpStarter\StockApi\StockLocationReference;
use Pickware\PickwareErpStarter\StockApi\StockMovement;
use Pickware\PickwareErpStarter\StockApi\StockMovementService;
use Pickware\PickwareErpStarter\Stocking\ProductQuantity;
use Pickware\ShopwareExtensionsBundle\OrderDelivery\OrderDeliveryCollectionExtension;
use Pickware\ShopwareExtensionsBundle\StateTransitioning\StateTransitionService;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryStates;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class OrderParcelService
{
    private StockMovementService $stockMovementService;
    private EntityManager $entityManager;
    private PickingRequestFactory $pickingRequestFactory;
    private StateTransitionService $stateTransitionService;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        StockMovementService $stockMovementService,
        EntityManager $entityManager,
        PickingRequestFactory $pickingRequestFactory,
        StateTransitionService $stateTransitionService,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->stockMovementService = $stockMovementService;
        $this->entityManager = $entityManager;
        $this->pickingRequestFactory = $pickingRequestFactory;
        $this->stateTransitionService = $stateTransitionService;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param ProductQuantityLocation[]|ImmutableCollection<ProductQuantityLocation> $stockToShip
     *     Source where the stock is shipped from
     * @param TrackingCode[] $trackingCodes
     */
    public function shipParcelForOrder($stockToShip, string $orderId, array $trackingCodes, Context $context): void
    {
        if (is_array($stockToShip)) {
            /** @deprecated tag:next-major Method will accept ImmutableCollection only as type for first param */
            $stockToShip = new ImmutableCollection($stockToShip);
        } elseif (!($stockToShip instanceof ImmutableCollection)) {
            throw new InvalidArgumentException(sprintf(
                'Parameter $productQuantityLocations must be either of type array or instance of %s.',
                ImmutableCollection::class,
            ));
        }

        $pickingRequest = $this->pickingRequestFactory->createPickingRequestForOrder($orderId, $context);
        $openQuantities = $pickingRequest->getProductQuantities();
        $quantitiesToShip = $stockToShip->map(
            fn(ProductQuantityLocation $stock) => new ProductQuantity($stock->getProductId(), $stock->getQuantity()),
        );
        $leftOverQuantities = ProductQuantityImmutableCollectionExtension::subtract($openQuantities, $quantitiesToShip);

        $isOverfulfilled = $leftOverQuantities
            ->containsElementSatisfying(fn(ProductQuantity $stock) => $stock->getQuantity() < 0);
        if ($isOverfulfilled) {
            throw OrderParcelException::overfulfillmentOfOrder($orderId);
        }

        $stockMovements = $stockToShip->map(
            fn (ProductQuantityLocation $productQuantityLocation) => StockMovement::create(
                [
                    'productId' => $productQuantityLocation->getProductId(),
                    'source' => $productQuantityLocation->getStockLocationReference(),
                    'destination' => StockLocationReference::order($orderId),
                    'quantity' => $productQuantityLocation->getQuantity(),
                ],
            ),
        );

        $this->stockMovementService->moveStock($stockMovements->asArray(), $context);

        // state transition
        // By re-calculating whether products still have to be shipped we can decide if this shipment is
        // a partial shipment.
        /** @var OrderEntity $order */
        $order = $this->entityManager->getByPrimaryKey(
            OrderDefinition::class,
            $orderId,
            $context,
            ['deliveries'],
        );
        $primaryOrderDelivery = OrderDeliveryCollectionExtension::primaryOrderDelivery(
            $order->getDeliveries(),
        );
        if (!$primaryOrderDelivery) {
            throw OrderParcelException::noOrderDeliveries($orderId);
        }

        $isPartialDelivery = $leftOverQuantities
            ->containsElementSatisfying(fn (ProductQuantity $stock) => $stock->getQuantity() > 0);
        $this->stateTransitionService->ensureOrderDeliveryState(
            $primaryOrderDelivery->getId(),
            $isPartialDelivery ? OrderDeliveryStates::STATE_PARTIALLY_SHIPPED : OrderDeliveryStates::STATE_SHIPPED,
            $context,
        );

        $this->eventDispatcher->dispatch(
            new ParcelShippedEvent(
                $stockToShip->asArray(),
                $orderId,
                $isPartialDelivery,
                $trackingCodes,
                $context,
            ),
        );
    }
}
