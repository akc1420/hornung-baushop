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

use Pickware\DalBundle\EntityManager;
use Pickware\PickwareErpStarter\Picking\PickingRequestService;
use Pickware\PickwareErpStarter\Stock\Model\StockDefinition;
use Pickware\PickwareErpStarter\Stock\OrderStockInitializer;
use Pickware\PickwareErpStarter\StockApi\StockLocationReference;
use Pickware\PickwareErpStarter\StockApi\StockMovementService;
use Pickware\PickwareErpStarter\Warehouse\Model\WarehouseDefinition;
use Pickware\PickwareErpStarter\Warehouse\Model\WarehouseEntity;
use Pickware\ShopwareExtensionsBundle\OrderDelivery\OrderDeliveryCollectionExtension;
use Pickware\ShopwareExtensionsBundle\OrderDelivery\PickwareOrderDeliveryCollection;
use Psr\EventDispatcher\EventDispatcherInterface;
use Shopware\Core\Checkout\Document\DocumentCollection;
use Shopware\Core\Checkout\Document\DocumentDefinition;
use Shopware\Core\Checkout\Document\DocumentEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryDefinition;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Content\MailTemplate\Subscriber\MailSendSubscriber;
use Shopware\Core\Content\MailTemplate\Subscriber\MailSendSubscriberConfig;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\System\StateMachine\Transition;

class OrderShippingService
{
    private EntityManager $entityManager;
    private PickingRequestService $pickingRequestService;
    private StockMovementService $stockMovementService;
    private StateMachineRegistry $stateMachineRegistry;
    private EventDispatcherInterface $eventDispatcher;
    private OrderParcelService $orderParcelService;

    public function __construct(
        EntityManager $entityManager,
        PickingRequestService $pickingRequestService,
        StockMovementService $stockMovementService,
        StateMachineRegistry $stateMachineRegistry,
        EventDispatcherInterface $eventDispatcher,
        OrderParcelService $orderParcelService
    ) {
        $this->entityManager = $entityManager;
        $this->pickingRequestService = $pickingRequestService;
        $this->stockMovementService = $stockMovementService;
        $this->stateMachineRegistry = $stateMachineRegistry;
        $this->eventDispatcher = $eventDispatcher;
        $this->orderParcelService = $orderParcelService;
    }

    public function shipMultipleOrdersCompletely(
        array $orderIds,
        string $warehouseId,
        array $documentIdsByOrderId,
        MailSendSubscriberConfig $mailSendSubscriberConfig,
        Context $context
    ): void {
        if (count($orderIds) === 0) {
            return;
        }

        $this->checkLiveVersion($context);

        /** @var PreOrderShippingValidationEvent $preOrderShippingValidationEvent */
        $preOrderShippingValidationEvent = $this->eventDispatcher->dispatch(
            new PreOrderShippingValidationEvent($context, $orderIds),
            PreOrderShippingValidationEvent::EVENT_NAME,
        );

        if (count($preOrderShippingValidationEvent->getErrors()) > 0) {
            throw OrderShippingException::preOrderShippingValidationErrors($preOrderShippingValidationEvent->getErrors());
        }

        /** @var WarehouseEntity $warehouse */
        $warehouse = $this->entityManager->getByPrimaryKey(
            WarehouseDefinition::class,
            $warehouseId,
            $context,
        );
        $orders = $this->getOrders($orderIds, $context, ['deliveries']);

        $this->entityManager->runInTransactionWithRetry(
            function () use ($documentIdsByOrderId, $mailSendSubscriberConfig, $context, $warehouse, $orders): void {
                foreach ($orders as $order) {
                    $context->removeExtension(MailSendSubscriber::MAIL_CONFIG_EXTENSION);

                    $this->lockProductStocks($order->getId(), $context);

                    $pickingRequest = $this->pickingRequestService->createAndSolvePickingRequestForOrderInWarehouses(
                        $order->getId(),
                        [$warehouse->getId()],
                        $context,
                    );

                    if (!$pickingRequest->isCompletelyPickable()) {
                        throw new NotEnoughStockException($warehouse, $order, $pickingRequest->getStockShortage());
                    }

                    $orderDelivery = PickwareOrderDeliveryCollection::createFrom($order->getDeliveries())
                        ->getPrimaryOrderDelivery();

                    if (!$orderDelivery) {
                        continue;
                    }

                    $mailSendSubscriberConfig->setDocumentIds($documentIdsByOrderId[$order->getId()] ?? []);

                    $context->addExtension(
                        MailSendSubscriber::MAIL_CONFIG_EXTENSION,
                        $mailSendSubscriberConfig,
                    );

                    $this->stateMachineRegistry->transition(
                        new Transition(
                            OrderDeliveryDefinition::ENTITY_NAME,
                            $orderDelivery->getId(),
                            StateMachineTransitionActions::ACTION_SHIP,
                            'stateId',
                        ),
                        $context,
                    );

                    // Shipping orders (moving stock _from_ a warehouse) does not run in batches, as the picking
                    // strategy depends on the current stock distribution. Move stock after each application of the
                    // picking strategy.
                    $this->stockMovementService->moveStock(
                        $pickingRequest->createStockMovementsWithDestination(
                            StockLocationReference::order($order->getId()),
                        ),
                        $context,
                    );
                }
            },
        );
    }

    /**
     * @return ProductQuantityLocation[]
     */
    public function shipOrderCompletely(string $orderId, string $warehouseId, Context $context): array
    {
        $this->checkLiveVersion($context);

        /** @var PreOrderShippingValidationEvent $preOrderShippingValidationEvent */
        $preOrderShippingValidationEvent = $this->eventDispatcher->dispatch(
            new PreOrderShippingValidationEvent($context, [$orderId]),
            PreOrderShippingValidationEvent::EVENT_NAME,
        );

        if (count($preOrderShippingValidationEvent->getErrors()) > 0) {
            throw OrderShippingException::preOrderShippingValidationErrors(
                $preOrderShippingValidationEvent->getErrors(),
            );
        }

        /** @var WarehouseEntity $warehouse */
        $warehouse = $this->entityManager->getByPrimaryKey(
            WarehouseDefinition::class,
            $warehouseId,
            $context,
        );

        /** @var OrderEntity $order */
        $order = $this->entityManager->getByPrimaryKey(OrderDefinition::class, $orderId, $context, ['deliveries']);

        // Throw an exception after the transaction completed, because throwing inside would mark any parent transaction
        // as roll-back-only where committing is not possible anymore. This can be done as long as no data has been
        // modified up to the point where the exception would be thrown, as otherwise it would be committed.
        $exceptionToThrow = null;

        $productQuantityLocations = $this->entityManager->runInTransactionWithRetry(
            function () use ($context, $warehouse, $order, &$exceptionToThrow): array {
                $this->lockProductStocks($order->getId(), $context);

                $pickingRequest = $this->pickingRequestService->createAndSolvePickingRequestForOrderInWarehouses(
                    $order->getId(),
                    [$warehouse->getId()],
                    $context,
                );
                if (!$pickingRequest->isCompletelyPickable()) {
                    // Not all quantity of the order line items could be distributed among the pick locations
                    $exceptionToThrow = new NotEnoughStockException(
                        $warehouse,
                        $order,
                        $pickingRequest->getStockShortage(),
                    );

                    return [];
                }

                $primaryOrderDelivery = OrderDeliveryCollectionExtension::primaryOrderDelivery($order->getDeliveries());
                if ($primaryOrderDelivery === null) {
                    $exceptionToThrow = OrderShippingException::noOrderDeliveries($order->getId());
                }
                $productQuantityLocations = $pickingRequest->createProductQuantityLocations();
                if ($exceptionToThrow === null) {
                    $this->orderParcelService->shipParcelForOrder(
                        $productQuantityLocations,
                        $order->getId(),
                        array_map(
                            fn (string $code) => new TrackingCode(
                                $code,
                                null,
                            ),
                            $primaryOrderDelivery->getTrackingCodes(),
                        ),
                        $context,
                    );
                }

                return $productQuantityLocations;
            },
        );

        if ($exceptionToThrow) {
            throw $exceptionToThrow;
        }

        return $productQuantityLocations;
    }

    public function getDocumentIdsByOrderId(array $orderIds, array $documentTypes, bool $skipDocumentsAlreadySent, Context $context): array
    {
        /** @var DocumentCollection $documents */
        $documents = $this->entityManager->findBy(
            DocumentDefinition::class,
            [
                'orderId' => $orderIds,
                'documentType.technicalName' => $documentTypes,
            ],
            $context,
        );

        $documentIdsByOrderId = [];
        /** @var DocumentEntity $document */
        foreach ($documents as $document) {
            if ($document->getSent() && $skipDocumentsAlreadySent) {
                continue;
            }
            $documentIdsByOrderId[$document->getOrderId()][] = $document->getId();
        }

        return $documentIdsByOrderId;
    }

    private function lockProductStocks(string $orderId, Context $context): void
    {
        $this->entityManager->lockPessimistically(
            StockDefinition::class,
            [
                'product.orderLineItems.order.id' => $orderId,
                'product.orderLineItems.type' => OrderStockInitializer::ORDER_STOCK_RELEVANT_LINE_ITEM_TYPES,
            ],
            $context,
        );
    }

    private function checkLiveVersion(Context $context): void
    {
        if ($context->getVersionId() !== Defaults::LIVE_VERSION) {
            throw OrderShippingException::notInLiveVersion();
        }
    }

    private function ensureWarehouseExists(string $warehouseId, Context $context): void
    {
        $this->entityManager->getByPrimaryKey(
            WarehouseDefinition::class,
            $warehouseId,
            $context,
        );
    }

    private function ensureOrderExists(string $orderId, Context $context): void
    {
        $this->entityManager->getByPrimaryKey(OrderDefinition::class, $orderId, $context);
    }

    /**
     * @return OrderEntity[]
     */
    private function getOrders(array $orderIds, Context $context, array $associations = []): array
    {
        return $this->entityManager->findBy(
            OrderDefinition::class,
            new Criteria($orderIds),
            $context,
            $associations,
        )->getElements();
    }
}
