<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\ShippingBundle\Shipment\Controller;

use Pickware\HttpUtils\ResponseFactory;
use Pickware\ShippingBundle\Carrier\CarrierAdapterException;
use Pickware\ShippingBundle\Config\ConfigException;
use Pickware\ShippingBundle\Notifications\NotificationService;
use Pickware\ShippingBundle\Shipment\ShipmentBlueprint;
use Pickware\ShippingBundle\Shipment\ShipmentBlueprintCreationConfiguration;
use Pickware\ShippingBundle\Shipment\ShipmentService;
use Pickware\ValidationBundle\Annotation\JsonValidation;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ShipmentController
{
    private ShipmentService $shipmentService;
    private NotificationService $notificationService;

    public function __construct(ShipmentService $shipmentService, NotificationService $notificationService)
    {
        $this->shipmentService = $shipmentService;
        $this->notificationService = $notificationService;
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(
     *     "/api/_action/pickware-shipping/shipment/create-shipment-blueprint-for-order",
     *     name="api.action.pickware-shipping.shipment.create-shipment-blueprint-for-order",
     *     methods={"POST"},
     * )
     * @JsonValidation(schemaFilePath="create-shipment-blueprint-for-order-payload.schema.json")
     */
    public function createShipmentBlueprintForOrder(Request $request, Context $context): Response
    {
        $orderId = $request->request->getAlnum('orderId');
        $shipmentBlueprintCreationConfigurationParameter = $request->request->get('configuration');
        $shipmentBlueprintCreationConfiguration = ShipmentBlueprintCreationConfiguration::fromArray(
            $shipmentBlueprintCreationConfigurationParameter ?? [],
        );

        $notifications = $this->notificationService->collectNotificationsInCallback(
            function () use ($context, $orderId, $shipmentBlueprintCreationConfiguration, &$shipmentBlueprint): void {
                $shipmentBlueprint = $this->shipmentService->createShipmentBlueprintForOrder(
                    $orderId,
                    $shipmentBlueprintCreationConfiguration,
                    $context,
                );
            },
        );

        return new JsonResponse([
            'shipmentBlueprint' => $shipmentBlueprint,
            'notifications' => $notifications,
        ]);
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(
     *     "/api/_action/pickware-shipping/shipment/create-shipment-blueprints-for-orders",
     *     name="api.action.pickware-shipping.shipment.create-shipment-blueprints-for-orders",
     *     methods={"POST"},
     * )
     * @JsonValidation(schemaFilePath="create-shipment-blueprints-for-orders-payload.schema.json")
     */
    public function createShipmentBlueprintsForOrders(Request $request, Context $context): Response
    {
        $orderIds = $request->get('orderIds');

        $notifications = $this->notificationService->collectNotificationsInCallback(
            function () use ($context, $orderIds, &$shipmentBlueprintsWithOrderId): void {
                $shipmentBlueprintCreationConfigurationByOrderId = [];
                foreach ($orderIds as $orderId) {
                    $shipmentBlueprintCreationConfigurationByOrderId[$orderId] = ShipmentBlueprintCreationConfiguration::makeDefault();
                }
                $shipmentBlueprintsWithOrderId = $this->shipmentService->createShipmentBlueprintsForOrders(
                    $shipmentBlueprintCreationConfigurationByOrderId,
                    $context,
                );
            },
        );

        return new JsonResponse([
            'shipmentBlueprintsWithOrderId' => $shipmentBlueprintsWithOrderId,
            'notifications' => $notifications,
        ]);
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(
     *     "/api/_action/pickware-shipping/shipment/create-shipment-for-order",
     *     name="api.action.pickware-shipping.shipment.create-shipment-for-order",
     *     methods={"POST"},
     * )
     * @JsonValidation(schemaFilePath="create-shipment-for-order-payload.schema.json")
     */
    public function createShipmentForOrder(Request $request, Context $context): Response
    {
        $orderId = $request->request->getAlnum('orderId');
        $shipmentBlueprintArray = $request->get('shipmentBlueprint');

        $shipmentBlueprint = ShipmentBlueprint::fromArray($shipmentBlueprintArray);
        try {
            $result = $this->shipmentService->createShipmentForOrder($shipmentBlueprint, $orderId, $context);
        } catch (ConfigException | CarrierAdapterException $exception) {
            return $exception
                ->serializeToJsonApiError()
                ->setStatus(Response::HTTP_BAD_REQUEST)
                ->toJsonApiErrorResponse();
        }

        return new JsonResponse($result);
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(
     *     "/api/_action/pickware-shipping/shipment/create-shipments-for-orders",
     *     name="api.action.pickware-shipping.shipment.create-shipments-for-orders",
     *     methods={"POST"},
     * )
     * @JsonValidation(schemaFilePath="create-shipments-for-orders-payload.schema.json")
     */
    public function createShipmentsForOrders(Request $request, Context $context): Response
    {
        $shipmentBlueprintsWithOrderIdArrays = $request->get('shipmentBlueprintsWithOrderId');

        $shipmentPayloads = [];
        foreach ($shipmentBlueprintsWithOrderIdArrays as $shipmentBlueprintWithOrderArray) {
            $shipmentPayloads[] = [
                'orders' => [
                    ['id' => $shipmentBlueprintWithOrderArray['orderId']],
                ],
                'shipmentBlueprint' => ShipmentBlueprint::fromArray($shipmentBlueprintWithOrderArray['shipmentBlueprint']),
            ];
        }

        try {
            $result = $this->shipmentService->createShipmentsForOrders($shipmentPayloads, $context);
        } catch (ConfigException | CarrierAdapterException $exception) {
            return $exception
                ->serializeToJsonApiError()
                ->setStatus(Response::HTTP_BAD_REQUEST)
                ->toJsonApiErrorResponse();
        }

        return new JsonResponse($result);
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(
     *     "/api/pickware-shipping-shipment/{shipmentId}/aggregated-tracking-urls",
     *     name="api.pickware_shipping_shipment.aggregated-tracking-urls",
     *     methods={"GET"},
     *     requirements={"shipmentId"="[a-fA-F0-9]{32}"}
     * )
     */
    public function shipmentAggregatedTrackingUrls(string $shipmentId, Context $context): JsonResponse
    {
        $urls = $this->shipmentService->getTrackingUrlsForShipment($shipmentId, $context);

        return new JsonResponse($urls);
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(
     *     "/api/_action/pickware-shipping/shipment/cancel-shipment",
     *     name="api.action.pickware-shipping-shipment.cancel",
     *     methods={"POST"},
     * )
     */
    public function cancelShipment(Request $request, Context $context): JsonResponse
    {
        $shipmentId = $request->request->getAlnum('shipmentId');
        if (!$shipmentId) {
            return ResponseFactory::createParameterMissingResponse('shipmentId');
        }
        $result = $this->shipmentService->cancelShipment($shipmentId, $context);

        return new JsonResponse($result);
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(
     *     "/api/_action/pickware-shipping/shipment/create-return-shipment-blueprint-for-order",
     *     name="api.action.pickware-shipping.shipment.create-return-shipment-blueprint-for-order",
     *     methods={"POST"},
     * )
     * @JsonValidation(schemaFilePath="create-shipment-blueprint-for-order-payload.schema.json")
     */
    public function createReturnShipmentBlueprintForOrder(Request $request, Context $context): Response
    {
        $orderId = $request->request->getAlnum('orderId');
        $shipmentBlueprintCreationConfigurationParameter = $request->request->get('configuration');
        $shipmentBlueprintCreationConfiguration = ShipmentBlueprintCreationConfiguration::fromArray(
            $shipmentBlueprintCreationConfigurationParameter ?? [],
        );

        $notifications = $this->notificationService->collectNotificationsInCallback(
            function () use ($context, $orderId, $shipmentBlueprintCreationConfiguration, &$returnShipmentBlueprint): void {
                $returnShipmentBlueprint = $this->shipmentService->createReturnShipmentBlueprintForOrder(
                    $orderId,
                    $shipmentBlueprintCreationConfiguration,
                    $context,
                );
            },
        );

        return new JsonResponse([
            'shipmentBlueprint' => $returnShipmentBlueprint,
            'notifications' => $notifications,
        ]);
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(
     *     "/api/_action/pickware-shipping/shipment/create-return-shipment-for-order",
     *     name="api.action.pickware-shipping.shipment.create-return-shipment-for-order",
     *     methods={"POST"},
     * )
     * @JsonValidation(schemaFilePath="create-shipment-for-order-payload.schema.json")
     */
    public function createReturnShipmentForOrder(Request $request, Context $context): Response
    {
        $orderId = $request->request->getAlnum('orderId');
        $returnShipmentBlueprintArray = $request->get('shipmentBlueprint');

        $returnShipmentBlueprint = ShipmentBlueprint::fromArray($returnShipmentBlueprintArray);
        try {
            $result = $this->shipmentService->createReturnShipmentForOrder($returnShipmentBlueprint, $orderId, $context);
        } catch (ConfigException | CarrierAdapterException $exception) {
            return $exception
                ->serializeToJsonApiError()
                ->setStatus(Response::HTTP_BAD_REQUEST)
                ->toJsonApiErrorResponse();
        }

        return new JsonResponse($result);
    }
}
