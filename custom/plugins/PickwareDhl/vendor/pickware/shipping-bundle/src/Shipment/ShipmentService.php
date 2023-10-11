<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\ShippingBundle\Shipment;

use Exception;
use LogicException;
use Pickware\DalBundle\EntityCollectionExtension;
use Pickware\DalBundle\EntityManager;
use Pickware\HttpUtils\JsonApi\JsonApiError;
use Pickware\ShippingBundle\Carrier\Capabilities\MultiTrackingCapability;
use Pickware\ShippingBundle\Carrier\CarrierAdapterRegistryInterface;
use Pickware\ShippingBundle\Carrier\Model\CarrierDefinition;
use Pickware\ShippingBundle\Carrier\Model\CarrierEntity;
use Pickware\ShippingBundle\Config\ConfigService;
use Pickware\ShippingBundle\Config\Model\ShippingMethodConfigCollection;
use Pickware\ShippingBundle\Config\Model\ShippingMethodConfigDefinition;
use Pickware\ShippingBundle\Config\Model\ShippingMethodConfigEntity;
use Pickware\ShippingBundle\Parcel\Parcel;
use Pickware\ShippingBundle\ParcelHydration\ParcelHydrator;
use Pickware\ShippingBundle\ParcelPacking\ParcelPacker;
use Pickware\ShippingBundle\ParcelPacking\WeightBasedParcelPacker;
use Pickware\ShippingBundle\Shipment\Model\ShipmentCollection;
use Pickware\ShippingBundle\Shipment\Model\ShipmentDefinition;
use Pickware\ShippingBundle\Shipment\Model\ShipmentEntity;
use Pickware\ShippingBundle\Shipment\Model\TrackingCodeEntity;
use Pickware\ShopwareExtensionsBundle\OrderDelivery\PickwareOrderDeliveryCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryDefinition;
use Shopware\Core\Checkout\Order\OrderCollection;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Throwable;

class ShipmentService
{
    private const ERROR_CODE_NAMESPACE = 'PICKWARE_SHIPPING__SHIPMENT__';
    public const ERROR_CODE_ORDER_DELIVERY_MISSING = self::ERROR_CODE_NAMESPACE . 'ORDER_DELIVERY_MISSING';

    private ConfigService $configService;
    private EntityManager $entityManager;
    private ParcelHydrator $parcelHydrator;
    private CarrierAdapterRegistryInterface $carrierAdapterRegistry;
    private WeightBasedParcelPacker $parcelPacker;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        ConfigService $configService,
        EntityManager $entityManager,
        ParcelHydrator $parcelHydrator,
        CarrierAdapterRegistryInterface $carrierAdapterRegistry,
        ParcelPacker $parcelPacker,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->configService = $configService;
        $this->entityManager = $entityManager;
        $this->parcelHydrator = $parcelHydrator;
        $this->carrierAdapterRegistry = $carrierAdapterRegistry;
        $this->parcelPacker = $parcelPacker;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function createShipmentBlueprintForOrder(
        string $orderId,
        ShipmentBlueprintCreationConfiguration $shipmentBlueprintCreationConfiguration,
        Context $context
    ): ShipmentBlueprint {
        $shipmentBlueprintsWithOrderId = $this->createShipmentBlueprints(
            [
                $orderId => $shipmentBlueprintCreationConfiguration,
            ],
            false,
            $context,
        );

        return $shipmentBlueprintsWithOrderId[0]['shipmentBlueprint'];
    }

    /**
     * @return array Returns the shipment blueprints and each order id: array [
     *   'orderId' => String,
     *   'shipmentBlueprint' => array,
     * ]
     */
    public function createShipmentBlueprintsForOrders(array $shipmentBlueprintCreationConfigurationByOrderId, Context $context): array
    {
        return $this->createShipmentBlueprints($shipmentBlueprintCreationConfigurationByOrderId, false, $context);
    }

    public function createShipmentForOrder(
        ShipmentBlueprint $shipmentBlueprint,
        string $orderId,
        Context $context,
        array $shipmentPayload = []
    ): ShipmentsOperationResultSet {
        $shipmentPayload['id'] ??= Uuid::randomHex();
        $shipmentsOperationResultSet = $this->createShipments(
            [
                array_merge(
                    $shipmentPayload,
                    [
                        'orders' => [
                            [
                                'id' => $orderId,
                            ],
                        ],
                        'shipmentBlueprint' => $shipmentBlueprint,
                    ],
                ),
            ],
            $context,
        );

        $shipmentId = $shipmentPayload['id'];
        if (!$shipmentsOperationResultSet->isAnyOperationResultSuccessful()) {
            $this->entityManager->delete(ShipmentDefinition::class, [$shipmentId], $context);

            return $shipmentsOperationResultSet;
        }
        $this->updateTrackingCodesOfOrderDeliveries([$shipmentId], $context);

        return $shipmentsOperationResultSet;
    }

    public function createShipmentsForOrders(
        array $shipmentPayloads,
        Context $context
    ): array {
        foreach ($shipmentPayloads as &$shipmentPayload) {
            $shipmentPayload['id'] ??= Uuid::randomHex();
        }
        unset($shipmentPayload);
        $shipmentsOperationResultSet = $this->createShipments($shipmentPayloads, $context);
        $shipmentIds = array_column($shipmentPayloads, 'id');
        /** @var ShipmentCollection $shipments */
        $shipments = $this->entityManager->findBy(
            ShipmentDefinition::class,
            [
                'id' => $shipmentIds,
            ],
            $context,
            [
                'orders',
            ],
        );

        $operationResultSetWithOrderId = [
            'successfullyOrPartlySuccessfullyProcessedShipmentIds' => $shipmentsOperationResultSet->getSuccessfullyOrPartlySuccessfullyProcessedShipmentIds(),
            'shipmentsOperationResultsWithOrderNumber' => [],
        ];
        /** @var ShipmentsOperationResult $shipmentsOperationResult */
        foreach ($shipmentsOperationResultSet->getShipmentsOperationResults() as $shipmentsOperationResult) {
            /** @var ShipmentEntity $shipment */
            $shipment = $shipments->get($shipmentsOperationResult->getProcessedShipmentIds()[0]);
            $operationResultSetWithOrderId['shipmentsOperationResultsWithOrderNumber'][] = [
                'orderNumber' => $shipment->getOrders()->first()->getOrderNumber(),
                'shipmentOperationResult' => $shipmentsOperationResult,
            ];
        }

        $this->entityManager->delete(
            ShipmentDefinition::class,
            $shipmentsOperationResultSet->getFailedProcessedShipmentIds(),
            $context,
        );

        $this->updateTrackingCodesOfOrderDeliveries(
            $shipmentsOperationResultSet->getSuccessfullyOrPartlySuccessfullyProcessedShipmentIds(),
            $context,
        );

        return $operationResultSetWithOrderId;
    }

    public function createReturnShipmentBlueprintForOrder(
        string $orderId,
        ShipmentBlueprintCreationConfiguration $shipmentBlueprintCreationConfiguration,
        Context $context
    ): ShipmentBlueprint {
        $returnShipmentBlueprintsWithOrderId = $this->createShipmentBlueprints(
            [
                $orderId => $shipmentBlueprintCreationConfiguration,
            ],
            true,
            $context,
        );

        return $returnShipmentBlueprintsWithOrderId[0]['shipmentBlueprint'];
    }

    public function createReturnShipmentForOrder(
        ShipmentBlueprint $shipmentBlueprint,
        string $orderId,
        Context $context,
        array $shipmentPayload = []
    ): ShipmentsOperationResultSet {
        return $this->createReturnShipments(
            [
                array_merge(
                    $shipmentPayload,
                    [
                        'orders' => [
                            [
                                'id' => $orderId,
                            ],
                        ],
                        'shipmentBlueprint' => $shipmentBlueprint,
                        'isReturnShipment' => true,
                    ],
                ),
            ],
            $context,
        );
    }

    /**
     * @return string[]
     */
    public function getTrackingUrlsForShipment(string $shipmentId, Context $context): array
    {
        /** @var ShipmentEntity $shipment */
        $shipment = $this->entityManager->findByPrimaryKey(ShipmentDefinition::class, $shipmentId, $context, [
            'trackingCodes',
        ]);
        $trackingCodes = $shipment->getTrackingCodes();

        if ($trackingCodes->count() === 0) {
            return [];
        }
        if ($trackingCodes->count() === 1 && $trackingCodes->first()->getTrackingUrl() !== null) {
            return [$trackingCodes->first()->getTrackingUrl()];
        }
        $carrierAdapter = $this->carrierAdapterRegistry->getCarrierAdapterByTechnicalName(
            $shipment->getCarrierTechnicalName(),
        );
        if ($carrierAdapter instanceof MultiTrackingCapability) {
            $trackingCodeIds = EntityCollectionExtension::getField($shipment->getTrackingCodes(), 'id');

            return [$carrierAdapter->generateTrackingUrlForTrackingCodes($trackingCodeIds, $context)];
        }

        return array_values(array_filter($trackingCodes->map(fn (TrackingCodeEntity $trackingCodeEntity) => $trackingCodeEntity->getTrackingUrl())));
    }

    public function cancelShipment(string $shipmentId, Context $context): ShipmentsOperationResultSet
    {
        /** @var ShipmentEntity $shipment */
        $shipment = $this->entityManager->findByPrimaryKey(ShipmentDefinition::class, $shipmentId, $context, [
            'carrier',
            'salesChannel',
        ]);

        $carrierConfiguration = $this->configService->getConfigForSalesChannel(
            $shipment->getCarrier()->getConfigDomain(),
            $shipment->getSalesChannelId(),
        );

        $methodName = '';
        if ($shipment->getIsReturnShipment()) {
            $returnCancellationCapability = $this->carrierAdapterRegistry->getReturnShipmentCancellationCapability(
                $shipment->getCarrierTechnicalName(),
            );
            $result = $returnCancellationCapability->cancelReturnShipments([$shipment->getId()], $carrierConfiguration, $context);
            $methodName = 'cancelReturnShipments';
        } else {
            $cancellationCapability = $this->carrierAdapterRegistry->getCancellationCapability(
                $shipment->getCarrierTechnicalName(),
            );
            $result = $cancellationCapability->cancelShipments([$shipment->getId()], $carrierConfiguration, $context);
            $methodName = 'cancelShipments';
        }
        if (!$result->didProcessAllShipments([$shipment->getId()])) {
            throw new LogicException(sprintf(
                'Implementation of method %s for carrier "%s" did not process every passed ' .
                'shipment. Please make sure that the method returns a %s that in ' .
                'total includes every passed shipment at least once.',
                $methodName,
                $shipment->getCarrier()->getTechnicalName(),
                ShipmentsOperationResultSet::class,
            ));
        }

        $resultForShipment = $result->getResultForShipment($shipment->getId());
        if ($resultForShipment !== ShipmentsOperationResultSet::RESULT_SUCCESSFUL) {
            return $result;
        }

        $shipmentPayload = [
            'id' => $shipment->getId(),
            'cancelled' => true,
        ];
        $this->entityManager->update(ShipmentDefinition::class, [$shipmentPayload], $context);

        $this->updateTrackingCodesOfOrderDeliveries([$shipment->getId()], $context);

        return $result;
    }

    private function createShipmentBlueprints(
        array $shipmentBlueprintCreationConfigurationByOrderId,
        bool $isReturnShipmentBlueprint,
        Context $context
    ): array {
        $orderIds = array_keys($shipmentBlueprintCreationConfigurationByOrderId);
        /** @var OrderCollection $orders */
        $orders = $this->entityManager->findBy(
            OrderDefinition::class,
            [
                'id' => $orderIds,
            ],
            $context,
            [
                'orderCustomer',
                'salesChannel',
                'deliveries.shippingOrderAddress.country',
                'deliveries.shippingOrderAddress.countryState',
            ],
        );

        $shipmentBlueprintsWithOrderId = [];
        $orderDeliveriesByOrderId = [];
        $shippingMethodIds = [];

        foreach ($orders as $order) {
            $orderDelivery = PickwareOrderDeliveryCollection::createFrom($order->getDeliveries())
                ->getPrimaryOrderDelivery();
            if (!$orderDelivery) {
                $shipmentBlueprintsWithOrderId[] = [
                    'status' => 'failed',
                    'orderId' => $order->getId(),
                    'shipmentBlueprint' => null,
                    'errors' => [
                        new JsonApiError([
                            'code' => self::ERROR_CODE_ORDER_DELIVERY_MISSING,
                            'title' => 'No order delivery',
                            'detail' => sprintf(
                                'No order delivery for order %s found.',
                                $order->getOrderNumber(),
                            ),
                            'meta' => [
                                'orderNumber' => $order->getOrderNumber(),
                            ],
                        ]),
                    ],
                ];
                $orderDeliveriesByOrderId[$order->getId()] = null;

                continue;
            }
            $shippingMethodIds[] = $orderDelivery->getShippingMethodId();
            $orderDeliveriesByOrderId[$order->getId()] = $orderDelivery;
        }

        /** @var ShippingMethodConfigCollection $shippingMethodConfig */
        $shippingMethodConfigs = $this->entityManager->findBy(
            ShippingMethodConfigDefinition::class,
            [
                'shippingMethodId' => $shippingMethodIds,
            ],
            $context,
            ['carrier'],
        );

        foreach ($orders as $order) {
            $orderDelivery = $orderDeliveriesByOrderId[$order->getId()];

            if (!$orderDelivery) {
                continue;
            }

            $commonConfig = $this->configService->getCommonShippingConfigForSalesChannel(
                $order->getSalesChannel()->getId(),
            );

            $shipmentBlueprint = new ShipmentBlueprint();
            $shipmentBlueprint->setSenderAddress($commonConfig->getSenderAddress());
            $shippingOrderAddress = $orderDelivery->getShippingOrderAddress();
            $receiverAddress = Address::fromShopwareOrderAddress($shippingOrderAddress);
            $receiverAddress->setEmail($order->getOrderCustomer()->getEmail());
            $shipmentBlueprint->setReceiverAddress($receiverAddress);

            // Swap sender and receiver for return shipment blueprints
            if ($isReturnShipmentBlueprint) {
                $shipmentBlueprint->setReceiverAddress($shipmentBlueprint->getSenderAddress());
                $shipmentBlueprint->setSenderAddress($receiverAddress);
            }

            /** @var ShippingMethodConfigEntity $shippingMethodConfig */
            $shippingMethodConfig = $shippingMethodConfigs->filterByProperty(
                'shippingMethodId',
                $orderDelivery->getShippingMethodId(),
            )->first();

            if ($shippingMethodConfig && $shippingMethodConfig->getCarrier()->isInstalled()) {
                $shipmentBlueprint->setCarrierTechnicalName($shippingMethodConfig->getCarrier()->getTechnicalName());
                if ($isReturnShipmentBlueprint) {
                    $shipmentBlueprint->setShipmentConfig($shippingMethodConfig->getReturnShipmentConfig());
                } else {
                    $shipmentBlueprint->setShipmentConfig($shippingMethodConfig->getShipmentConfig());
                }
            }

            $parcel = $this->parcelHydrator->hydrateParcelFromOrder($order->getId(), $context);
            $commonConfig->assignCustomsInformation($parcel->getCustomsInformation());

            $shipmentBlueprintCreationConfiguration = $shipmentBlueprintCreationConfigurationByOrderId[$order->getId()] ? : ShipmentBlueprintCreationConfiguration::makeDefault();

            $parcels = $this->repackParcels(
                $parcel,
                $shipmentBlueprintCreationConfiguration,
                $shippingMethodConfig,
            );

            $shipmentBlueprint->setCustomerReference($order->getOrderNumber());
            $shipmentBlueprint->setParcels($parcels);

            $this->eventDispatcher->dispatch(
                new ShipmentBlueprintCreatedEvent($shipmentBlueprint, $order->getId(), $context),
                ShipmentBlueprintCreatedEvent::EVENT_NAME,
            );

            $shipmentBlueprintsWithOrderId[] = [
                'status' => 'success',
                'orderId' => $order->getId(),
                'shipmentBlueprint' => $shipmentBlueprint,
                'errors' => null,
            ];
        }

        return $shipmentBlueprintsWithOrderId;
    }

    /**
     * Fill the payload for the shipments with necessary information so we can safe it to the DB
     */
    private function completeShipmentPayloads(array $shipmentPayloads, Context $context): array
    {
        $orderPayloads = array_merge(array_map(
            fn (array $shipmentPayload) => $shipmentPayload['orders'],
            $shipmentPayloads,
        ));

        foreach ($orderPayloads as $orderPayload) {
            $orderIds[] = array_map(
                fn (array $order) => $order['id'],
                $orderPayload,
            );
        }

        $orderIds = array_merge(...$orderIds);

        /** @var OrderCollection $orders */
        $orders = $this->entityManager->findBy(
            OrderDefinition::class,
            [
                'id' => $orderIds,
            ],
            $context,
            [
                'salesChannel',
                'deliveries',
            ],
        );

        foreach ($shipmentPayloads as &$shipmentPayload) {
            $shipmentPayload['id'] = $shipmentPayload['id'] ?? Uuid::randomHex();
            unset($shipmentPayload['carrier']);
            $shipmentPayload['carrierTechnicalName'] = $shipmentPayload['shipmentBlueprint']->getCarrierTechnicalName();
            unset($shipmentPayload['salesChannel']);
            $shipmentPayload['salesChannelId'] = $orders->get($shipmentPayload['orders'][0]['id'])->getSalesChannelId();
        }

        return $shipmentPayloads;
    }

    private function createShipments(array $shipmentsPayloads, Context $context): ShipmentsOperationResultSet
    {
        $carrierTechnicalNames = array_values(array_unique(array_map(
            fn(array $shipmentPayload) => $shipmentPayload['shipmentBlueprint']->getCarrierTechnicalName(),
            $shipmentsPayloads,
        )));
        if (count($carrierTechnicalNames) !== 1) {
            throw new LogicException('Multiple carriers are not supported at this point.');
        }
        $carrierTechnicalName = $carrierTechnicalNames[0];

        /** @var CarrierEntity $carrier */
        $carrier = $this->entityManager->findByPrimaryKey(
            CarrierDefinition::class,
            $carrierTechnicalName,
            $context,
        );

        if (!$carrier->isInstalled()) {
            throw new Exception(sprintf('Carrier %s is not installed.', $carrier->getTechnicalName()));
        }

        $carrierAdapter = $this->carrierAdapterRegistry->getCarrierAdapterByTechnicalName($carrier->getTechnicalName());

        $shipmentsPayloads = $this->completeShipmentPayloads($shipmentsPayloads, $context);
        $this->entityManager->create(ShipmentDefinition::class, $shipmentsPayloads, $context);
        $shipmentIds = array_map(fn(array $shipmentPayload) => $shipmentPayload['id'], $shipmentsPayloads);

        $salesChannelIds = array_values(array_unique(array_map(
            fn(array $shipmentPayload) => $shipmentPayload['salesChannelId'],
            $shipmentsPayloads,
        )));
        if (count($salesChannelIds) !== 1) {
            throw new LogicException('Multiple sales channels are not supported at this point.');
        }
        $salesChannelId = $salesChannelIds[0];
        $carrierConfig = $this->configService->getConfigForSalesChannel($carrier->getConfigDomain(), $salesChannelId);

        try {
            $result = $carrierAdapter->registerShipments($shipmentIds, $carrierConfig, $context);
        } catch (Throwable $e) {
            $this->entityManager->delete(ShipmentDefinition::class, $shipmentIds, $context);

            throw $e;
        }

        if (!$result->didProcessAllShipments($shipmentIds)) {
            throw new LogicException(sprintf(
                'Implementation of method registerShipments for carrier adapter "%s" did not process every passed ' .
                'ShipmentEntity. Please make sure that the method returns a ShipmentsOperationResultSet that in ' .
                'total includes every passed ShipmentEntity at least once.',
                get_class($carrierAdapter),
            ));
        }

        return $result;
    }

    private function createReturnShipments(array $shipmentsPayloads, Context $context): ShipmentsOperationResultSet
    {
        $carrierTechnicalNames = array_values(array_unique(array_map(
            fn(array $shipmentPayload) => $shipmentPayload['shipmentBlueprint']->getCarrierTechnicalName(),
            $shipmentsPayloads,
        )));
        if (count($carrierTechnicalNames) !== 1) {
            throw new LogicException('Multiple carriers are not supported at this point.');
        }
        $carrierTechnicalName = $carrierTechnicalNames[0];

        /** @var CarrierEntity $carrier */
        $carrier = $this->entityManager->findByPrimaryKey(
            CarrierDefinition::class,
            $carrierTechnicalName,
            $context,
        );

        if (!$carrier->isInstalled()) {
            throw new Exception(sprintf('Carrier %s is not installed.', $carrier->getTechnicalName()));
        }

        $carrierAdapter = $this->carrierAdapterRegistry->getReturnShipmentsCapability($carrier->getTechnicalName());

        $shipmentsPayloads = $this->completeShipmentPayloads($shipmentsPayloads, $context);
        $this->entityManager->create(ShipmentDefinition::class, $shipmentsPayloads, $context);
        $shipmentIds = array_map(fn(array $shipmentPayload) => $shipmentPayload['id'], $shipmentsPayloads);

        $salesChannelIds = array_values(array_unique(array_map(
            fn(array $shipmentPayload) => $shipmentPayload['salesChannelId'],
            $shipmentsPayloads,
        )));
        if (count($salesChannelIds) !== 1) {
            throw new LogicException('Multiple sales channels are not supported at this point.');
        }
        $salesChannelId = $salesChannelIds[0];

        $carrierConfig = $this->configService->getConfigForSalesChannel($carrier->getConfigDomain(), $salesChannelId);

        try {
            /** @var ShipmentsOperationResultSet $result */
            $result = $carrierAdapter->registerReturnShipments(
                $shipmentIds,
                $carrierConfig,
                $context,
            );
        } catch (Throwable $e) {
            $this->entityManager->delete(ShipmentDefinition::class, $shipmentIds, $context);

            throw $e;
        }

        if (!$result->didProcessAllShipments($shipmentIds)) {
            throw new LogicException(sprintf(
                'Implementation of method registerReturnShipments for carrier adapter "%s" did not process every passed ' .
                'ShipmentEntity. Please make sure that the method returns a ShipmentsOperationResultSet that in ' .
                'total includes every passed ShipmentEntity at least once.',
                $carrier->getTechnicalName(),
            ));
        }

        if (!$result->isAnyOperationResultSuccessful()) {
            $this->entityManager->delete(
                ShipmentDefinition::class,
                $shipmentIds,
                $context,
            );

            return $result;
        }

        $this->updateTrackingCodesOfOrderDeliveries($shipmentIds, $context);

        return $result;
    }

    private function updateTrackingCodesOfOrderDeliveries(array $shipmentIds, Context $context): void
    {
        /** @var ShipmentCollection $shipments */
        $shipments = $this->entityManager->findBy(
            ShipmentDefinition::class,
            ['id' => $shipmentIds],
            $context,
            [
                'trackingCodes',
                'orders',
                'orders.deliveries',
            ],
        );

        $payload = [];
        foreach ($shipments as $shipment) {
            $shipmentTrackingCodes = $shipment->getTrackingCodes()->map(fn (TrackingCodeEntity $trackingCode) => $trackingCode->getTrackingCode());

            foreach ($shipment->getOrders() as $order) {
                $orderDelivery = PickwareOrderDeliveryCollection::createFrom($order->getDeliveries())
                    ->getPrimaryOrderDelivery();

                $trackingCodes = $orderDelivery->getTrackingCodes();
                if ($shipment->isCancelled()) {
                    // Remove tracking codes from list
                    $updatedTrackingCodes = array_values(array_diff($trackingCodes, $shipmentTrackingCodes));
                } else {
                    // Add tracking codes to list
                    $updatedTrackingCodes = array_values(array_unique(array_merge($shipmentTrackingCodes, $trackingCodes)));
                }

                $payload[] = [
                    'id' => $orderDelivery->getId(),
                    'trackingCodes' => $updatedTrackingCodes,
                ];
            }
        }

        if (count($payload) > 0) {
            $this->entityManager->update(OrderDeliveryDefinition::class, $payload, $context);
        }
    }

    private function repackParcels(
        Parcel $parcel,
        ShipmentBlueprintCreationConfiguration $shipmentBlueprintCreationConfiguration,
        ShippingMethodConfigEntity $shippingMethodConfig = null
    ): array {
        if ($shippingMethodConfig) {
            $parcelPackingConfig = $shippingMethodConfig->getParcelPackingConfiguration();
            $parcel->setDimensions($parcelPackingConfig->getDefaultBoxDimensions());
            if ($shipmentBlueprintCreationConfiguration->getSkipParcelRepacking()) {
                // By setting the container limit to "infinity" we disable the repacking in multiple packages but still
                // get the filler weight and the weight overwrite set if necessary
                $parcelPackingConfig = $parcelPackingConfig->createCopy();
                $parcelPackingConfig->setMaxParcelWeight(null); // null == infinity
            }
            $parcels = $this->parcelPacker->repackParcel(
                $parcel,
                $parcelPackingConfig,
            );
        } else {
            $parcels = [$parcel];
        }

        if (count($parcels) > 1) {
            foreach ($parcels as $i => $repackedParcel) {
                if ($repackedParcel->getCustomerReference() === null) {
                    continue;
                }

                $repackedParcel->setCustomerReference(
                    sprintf('%s-%d', $repackedParcel->getCustomerReference(), $i + 1),
                );
            }
        }

        return $parcels;
    }
}
