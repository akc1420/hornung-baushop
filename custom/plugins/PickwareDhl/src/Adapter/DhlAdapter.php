<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareDhl\Adapter;

use Pickware\DalBundle\EntityCollectionExtension;
use Pickware\DalBundle\EntityManager;
use Pickware\PickwareDhl\Adapter\Request\CreateShipmentOrderRequest;
use Pickware\PickwareDhl\Adapter\Request\DeleteShipmentOrderRequest;
use Pickware\PickwareDhl\Adapter\ShipmentOrder\ShipmentOrder;
use Pickware\PickwareDhl\ApiClient\DhlApiClientException;
use Pickware\PickwareDhl\ApiClient\DhlApiClientFactory;
use Pickware\PickwareDhl\Config\DhlConfig;
use Pickware\PickwareDhl\ReturnLabel\ParcelDeReturnsApiClientFactory;
use Pickware\PickwareDhl\ReturnLabel\Request\CreateReturnLabelRequest;
use Pickware\PickwareDhl\ReturnLabel\Request\GetAvailableReturnLocationRequest;
use Pickware\ShippingBundle\Carrier\AbstractCarrierAdapter;
use Pickware\ShippingBundle\Carrier\Capabilities\CancellationCapability;
use Pickware\ShippingBundle\Carrier\Capabilities\MultiTrackingCapability;
use Pickware\ShippingBundle\Carrier\Capabilities\ReturnShipmentsRegistrationCapability;
use Pickware\ShippingBundle\Config\Config;
use Pickware\ShippingBundle\Shipment\Country;
use Pickware\ShippingBundle\Shipment\Model\ShipmentCollection;
use Pickware\ShippingBundle\Shipment\Model\ShipmentDefinition;
use Pickware\ShippingBundle\Shipment\Model\ShipmentEntity;
use Pickware\ShippingBundle\Shipment\Model\TrackingCodeDefinition;
use Pickware\ShippingBundle\Shipment\Model\TrackingCodeEntity;
use Pickware\ShippingBundle\Shipment\ShipmentsOperationResult;
use Pickware\ShippingBundle\Shipment\ShipmentsOperationResultSet;
use Shopware\Core\Framework\Context;

class DhlAdapter extends AbstractCarrierAdapter implements MultiTrackingCapability, CancellationCapability, ReturnShipmentsRegistrationCapability
{
    public const TRACKING_CODE_TYPE_SHIPMENT_NUMBER = 'shipmentNumber';
    public const TRACKING_CODE_TYPE_RETURN_SHIPMENT_NUMBER = 'returnShipmentNumber';

    /**
     * @var DhlShipmentOrderFactory
     */
    private $shipmentOrderFactory;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var DhlApiClientFactory
     */
    private $dhlApiClientFactory;

    /**
     * @var DhlResponseProcessor
     */
    private $dhlResponseProcessor;
    private ParcelDeReturnsApiClientFactory $parcelDeReturnsApiClientFactory;

    public function __construct(
        DhlShipmentOrderFactory $shipmentOrderFactory,
        EntityManager $entityManager,
        DhlApiClientFactory $dhlApiClientFactory,
        DhlResponseProcessor $dhlResponseProcessor,
        ParcelDeReturnsApiClientFactory $parcelDeReturnsApiClientFactory
    ) {
        $this->shipmentOrderFactory = $shipmentOrderFactory;
        $this->entityManager = $entityManager;
        $this->dhlApiClientFactory = $dhlApiClientFactory;
        $this->dhlResponseProcessor = $dhlResponseProcessor;
        $this->parcelDeReturnsApiClientFactory = $parcelDeReturnsApiClientFactory;
    }

    /**
     * @param string[] $shipmentNumbers
     */
    public static function getTrackingUrlForShipmentNumbers(array $shipmentNumbers): string
    {
        return sprintf(
            'https://www.dhl.de/de/privatkunden/dhl-sendungsverfolgung.html?piececode=%s',
            implode(',', $shipmentNumbers),
        );
    }

    public function registerShipments(
        array $shipmentIds,
        Config $carrierConfig,
        Context $context
    ): ShipmentsOperationResultSet {
        /** @var ShipmentCollection $shipments */
        $shipments = $this->entityManager->findBy(ShipmentDefinition::class, ['id' => $shipmentIds], $context);
        $dhlConfig = new DhlConfig($carrierConfig);
        $dhlApiClientConfig = $dhlConfig->getDhlApiClientConfig();
        $dhlBcpApiClient = $this->dhlApiClientFactory->createDhlBcpApiClient($dhlApiClientConfig);

        $shipmentsOperationResultSet = new ShipmentsOperationResultSet();

        $shipmentOrders = $this->getShipmentOrdersForShipments(
            $shipments,
            $dhlConfig,
            $shipmentsOperationResultSet,
            $context,
        );

        if (!$shipmentOrders) {
            return $shipmentsOperationResultSet;
        }

        $response = $dhlBcpApiClient->sendRequest(new CreateShipmentOrderRequest($shipmentOrders));

        return $this->dhlResponseProcessor->processCreateShipmentOrderResponse(
            $response,
            $shipmentsOperationResultSet,
            $context,
        );
    }

    private function getShipmentOrdersForShipments(
        ShipmentCollection $shipments,
        DhlConfig $dhlConfig,
        ShipmentsOperationResultSet $shipmentsOperationResultSet,
        Context $context
    ): array {
        $shipmentOrders = $shipments->map(
            function (ShipmentEntity $shipment) use ($context, $dhlConfig, $shipmentsOperationResultSet) {
                $shipmentOrders = $this->shipmentOrderFactory->createShipmentOrdersForShipment(
                    $shipment->getId(),
                    $dhlConfig,
                    $context,
                );

                return array_filter(array_map(
                    function (ShipmentOrder $shipmentOrder) use ($shipment, $shipmentsOperationResultSet) {
                        try {
                            return $shipmentOrder->toArray();
                        } catch (DhlApiClientException $exception) {
                            $operationDescription = sprintf(
                                'Create label to %s %s',
                                $shipmentOrder->getReceiverAddress()->getFirstName(),
                                $shipmentOrder->getReceiverAddress()->getLastName(),
                            );

                            $shipmentsOperationResultSet->addShipmentOperationResult(
                                ShipmentsOperationResult::createFailedOperationResult(
                                    [$shipment->getId()],
                                    $operationDescription,
                                    [$exception->getMessage()],
                                ),
                            );

                            return null;
                        }
                    },
                    $shipmentOrders,
                ));
            },
        );

        return array_merge(...array_values($shipmentOrders));
    }

    public function generateTrackingUrlForTrackingCodes(array $trackingCodeIds, Context $context): string
    {
        $trackingCodes = $this->entityManager->findBy(
            TrackingCodeDefinition::class,
            ['id' => $trackingCodeIds],
            $context,
        );
        $shipmentNumbers = EntityCollectionExtension::getField($trackingCodes, 'trackingCode');

        return self::getTrackingUrlForShipmentNumbers($shipmentNumbers);
    }

    public function cancelShipments(
        array $shipmentIds,
        Config $carrierConfig,
        Context $context
    ): ShipmentsOperationResultSet {
        /** @var ShipmentCollection $shipments */
        $shipments = $this->entityManager->findBy(
            ShipmentDefinition::class,
            ['id' => $shipmentIds],
            $context,
            ['trackingCodes'],
        );

        $shipmentsOperationResultSet = new ShipmentsOperationResultSet();

        $shipmentNumbers = [];
        $shipmentNumbersShipmentsMapping = [];
        foreach ($shipments as $shipment) {
            $numberOfCancellableTrackingCodesForShipment = 0;
            foreach ($shipment->getTrackingCodes() as $trackingCode) {
                $metaInformation = $trackingCode->getMetaInformation();
                if ($metaInformation['type'] !== self::TRACKING_CODE_TYPE_SHIPMENT_NUMBER) {
                    continue;
                }
                if (isset($metaInformation['cancelled']) && $metaInformation['cancelled']) {
                    $operationDescription = sprintf('Cancel label %s', $trackingCode->getTrackingCode());
                    $shipmentsOperationResult = ShipmentsOperationResult::createSuccessfulOperationResult(
                        EntityCollectionExtension::getField($shipments, 'id'),
                        $operationDescription,
                    );
                    $shipmentsOperationResultSet->addShipmentOperationResult($shipmentsOperationResult);

                    continue;
                }
                $shipmentNumbers[] = $trackingCode->getTrackingCode();
                $shipmentNumbersShipmentsMapping[$trackingCode->getTrackingCode()][] = $shipment->getId();
                $numberOfCancellableTrackingCodesForShipment ++;
            }

            if ($numberOfCancellableTrackingCodesForShipment === 0) {
                $operationDescription = sprintf('Cancel shipment %s', $shipment->getId());
                $shipmentsOperationResult = ShipmentsOperationResult::createFailedOperationResult(
                    EntityCollectionExtension::getField($shipments, 'id'),
                    $operationDescription,
                    [
                        'This shipment has no tracking codes that can be used to cancel the shipment',
                    ],
                );
                $shipmentsOperationResultSet->addShipmentOperationResult($shipmentsOperationResult);
            }
        }

        if (count($shipmentNumbers) === 0) {
            return $shipmentsOperationResultSet;
        }

        $dhlConfig = new DhlConfig($carrierConfig);
        $dhlApiConfig = $dhlConfig->getDhlApiClientConfig();
        $dhlApiClient = $this->dhlApiClientFactory->createDhlBcpApiClient($dhlApiConfig);

        $result = $dhlApiClient->sendRequest(new DeleteShipmentOrderRequest($shipmentNumbers));

        $deletionStates = is_array($result->DeletionState) ? $result->DeletionState : [$result->DeletionState];
        $trackingCodePayload = [];
        foreach ($deletionStates as $deletionState) {
            $shipmentNumber = $deletionState->shipmentNumber;
            $operationDescription = sprintf('Cancel label %s', $shipmentNumber);

            $affectedShipmentIds = $shipmentNumbersShipmentsMapping[$shipmentNumber];
            $affectedShipments = $shipments->filter(fn (ShipmentEntity $shipment) => in_array($shipment->getId(), $affectedShipmentIds, true));

            if ($deletionState->Status->statusCode === 0) {
                $shipmentsOperationResult = ShipmentsOperationResult::createSuccessfulOperationResult(
                    EntityCollectionExtension::getField($affectedShipments, 'id'),
                    $operationDescription,
                );

                // Mark the tracking codes as cancelled
                foreach ($affectedShipments as $affectedShipment) {
                    $affectedTrackingCodes = $affectedShipment->getTrackingCodes()->filter(fn (TrackingCodeEntity $trackingCode) => $trackingCode->getTrackingCode() === $shipmentNumber);

                    foreach ($affectedTrackingCodes as $affectedTrackingCode) {
                        $metaInformation = $affectedTrackingCode->getMetaInformation();
                        $metaInformation['cancelled'] = true;
                        $trackingCodePayload[] = [
                            'id' => $affectedTrackingCode->getId(),
                            'metaInformation' => $metaInformation,
                        ];
                    }
                }
            } else {
                $shipmentsOperationResult = ShipmentsOperationResult::createFailedOperationResult(
                    EntityCollectionExtension::getField($affectedShipments, 'id'),
                    $operationDescription,
                    [$deletionState->Status->statusText],
                );
            }

            $shipmentsOperationResultSet->addShipmentOperationResult($shipmentsOperationResult);
        }

        if (count($trackingCodePayload) !== 0) {
            $this->entityManager->upsert(TrackingCodeDefinition::class, $trackingCodePayload, $context);
        }

        return $shipmentsOperationResultSet;
    }

    public function registerReturnShipments(array $shipmentIds, Config $carrierConfig, Context $context): ShipmentsOperationResultSet
    {
        /** @var ShipmentCollection $shipments */
        $shipments = $this->entityManager->findBy(ShipmentDefinition::class, ['id' => $shipmentIds], $context);
        $dhlConfig = new DhlConfig($carrierConfig);

        $parcelDeReturnApiClient = $this->parcelDeReturnsApiClientFactory->createApiClient($dhlConfig->getDhlApiClientConfig());

        $shipmentsOperationResultSet = new ShipmentsOperationResultSet();

        foreach ($shipments as $shipment) {
            $operationDescription = sprintf(
                'Create return label for %s %s',
                $shipment->getShipmentBlueprint()->getSenderAddress()->getFirstName(),
                $shipment->getShipmentBlueprint()->getSenderAddress()->getLastName(),
            );
            if (!$shipment->getShipmentBlueprint()->getSenderAddress() || !$shipment->getShipmentBlueprint()->getSenderAddress()->getCountryIso()) {
                $operationResult = ShipmentsOperationResult::createFailedOperationResult(
                    [$shipment->getId()],
                    $operationDescription,
                    ['Sender country is missing'],
                );
                $shipmentsOperationResultSet->addShipmentOperationResult($operationResult);

                continue;
            }
            $senderCountry = new Country($shipment->getShipmentBlueprint()->getSenderAddress()->getCountryIso());
            try {
                $response = $parcelDeReturnApiClient->sendRequest(new GetAvailableReturnLocationRequest($senderCountry));
            } catch (DhlApiClientException $e) {
                $operationResult = ShipmentsOperationResult::createFailedOperationResult(
                    [$shipment->getId()],
                    $operationDescription,
                    [$e->getMessage()],
                );
                $shipmentsOperationResultSet->addShipmentOperationResult($operationResult);

                continue;
            }
            $responseJson = json_decode(
                $response->getBody()->__toString(),
                false,
                512,
                JSON_THROW_ON_ERROR,
            );
            if (count($responseJson) === 0) {
                $operationResult = ShipmentsOperationResult::createFailedOperationResult(
                    [$shipment->getId()],
                    $operationDescription,
                    [sprintf('No DHL returns receiver found for country %s', $senderCountry->getIso3Code())],
                );
                $shipmentsOperationResultSet->addShipmentOperationResult($operationResult);

                continue;
            }
            // Currently we do not support to choose the reciever if multiple are present for a single country.
            // Because of this we always use the first receiverId that DHL returns.
            $receiverId = $responseJson[0]->receiverId;

            try {
                $shipmentOrders = $this->shipmentOrderFactory->createReturnShipmentOrdersForShipment(
                    $shipment->getShipmentBlueprint(),
                    $dhlConfig,
                );
            } catch (DhlApiClientException | DhlAdapterException $e) {
                $operationResult = ShipmentsOperationResult::createFailedOperationResult(
                    [$shipment->getId()],
                    $operationDescription,
                    [$e->getMessage()],
                );
                $shipmentsOperationResultSet->addShipmentOperationResult($operationResult);

                continue;
            }

            foreach ($shipmentOrders as $shipmentOrder) {
                $shipmentOrder->setReceiverId($receiverId);
                try {
                    $response = $parcelDeReturnApiClient->sendRequest(new CreateReturnLabelRequest($shipmentOrder));
                } catch (DhlApiClientException $e) {
                    $operationResult = ShipmentsOperationResult::createFailedOperationResult(
                        [$shipment->getId()],
                        $operationDescription,
                        [$e->getMessage()],
                    );
                    $shipmentsOperationResultSet->addShipmentOperationResult($operationResult);

                    continue;
                }

                $this->dhlResponseProcessor->processCreateReturnShipmentOrderResponse(
                    $response,
                    $shipment->getId(),
                    $shipmentOrder->getParcel()->getCustomerReference(),
                    $context,
                );
                $shipmentsOperationResultSet->addShipmentOperationResult(
                    ShipmentsOperationResult::createSuccessfulOperationResult(
                        [$shipment->getId()],
                        sprintf(
                            $operationDescription . ', parcel %s',
                            $shipmentOrder->getParcel()->getCustomerReference(),
                        ),
                    ),
                );
            }
        }

        return $shipmentsOperationResultSet;
    }
}
