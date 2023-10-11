<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareErpStarter\ReturnOrder\Controller;

use Pickware\DalBundle\EntityManager;
use Pickware\DalBundle\EntityResponseService;
use Pickware\PickwareErpStarter\ReturnOrder\Model\ReturnOrderDefinition;
use Pickware\PickwareErpStarter\ReturnOrder\ReturnOrderService;
use Pickware\ValidationBundle\Annotation\JsonValidation;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ReturnOrderController
{
    private EntityManager $entityManager;
    private ReturnOrderService $returnOrderService;
    private EntityResponseService $entityResponseService;

    public function __construct(
        EntityManager $entityManager,
        ReturnOrderService $returnOrderService,
        EntityResponseService $entityResponseService
    ) {
        $this->entityManager = $entityManager;
        $this->returnOrderService = $returnOrderService;
        $this->entityResponseService = $entityResponseService;
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(
     *     "/api/_action/pickware-erp/get-returnable-order-line-items",
     *     name="api.action.pickware-erp.get-returnable-order-line-items",
     *     methods={"POST"}
     * )
     * @JsonValidation(schemaFilePath="payload-get-returnable-order-line-items.schema.json")
     */
    public function getReturnableOrderLineItems(Context $context, Request $request): Response
    {
        $orderIds = $request->get('orderIds');

        return new JsonResponse(
            $this->returnOrderService->getReturnableOrderLineItems($orderIds, $context),
            Response::HTTP_OK,
        );
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(
     *     "/api/_action/pickware-erp/create-completed-return-orders",
     *     name="api.action.pickware-erp.create-completed-return-orders",
     *     methods={"POST"}
     * )
     * @JsonValidation(schemaFilePath="payload-create-completed-return-orders.schema.json")
     */
    public function createCompletedReturnOrders(Context $context, Request $request): Response
    {
        $returnOrderPayloads = $request->get('returnOrders');
        $userId = $context->getSource()->getUserId();

        // Create stock adjustments to restock all order line item quantities
        $stockAdjustmentsByReturnOrderId = [];
        foreach ($returnOrderPayloads as $returnOrderPayload) {
            $stockAdjustmentsByReturnOrderId[$returnOrderPayload['id']] = [];
            foreach ($returnOrderPayload['lineItems'] as $returnOrderLineItemPayload) {
                $stockAdjustmentsByReturnOrderId[$returnOrderPayload['id']][] = [
                    'productId' => $returnOrderLineItemPayload['productId'],
                    'dispose' => 0,
                    'restock' => $returnOrderLineItemPayload['quantity'],
                ];
            }
        }

        $this->entityManager->runInTransactionWithRetry(
            function () use ($context, $returnOrderPayloads, $stockAdjustmentsByReturnOrderId, $userId): void {
                $returnOrderIds = array_column($returnOrderPayloads, 'id');
                $this->returnOrderService->requestReturnOrders($returnOrderPayloads, $context, $userId);
                $this->returnOrderService->approveReturnOrders($returnOrderIds, $context);
                $this->returnOrderService->moveStockIntoReturnOrders(
                    $this->returnOrderService->getReturnOrderLineItemQuantitiesByReturnOrderId($returnOrderIds, $context),
                    $context,
                    $userId,
                );
                $this->returnOrderService->completeReturnOrders($returnOrderIds, $context);
                $this->returnOrderService->moveStockFromReturnOrders(
                    $stockAdjustmentsByReturnOrderId,
                    $context,
                    $context->getSource()->getUserId(),
                );
            },
        );

        return $this->entityResponseService->makeEntityListingResponse(
            ReturnOrderDefinition::class,
            array_column($returnOrderPayloads, 'id'),
            $context,
            $request,
            $request->get('associations', []),
        );
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(
     *     "/api/_action/pickware-erp/request-and-approve-return-order",
     *     name="api.action.pickware-erp.request-and-approve-return-order",
     *     methods={"POST"}
     * )
     * @JsonValidation(schemaFilePath="payload-request-and-approve-return-order.schema.json")
     */
    public function requestAndApproveReturnOrder(Context $context, Request $request): Response
    {
        $returnOrderPayload = $request->get('returnOrder');
        $userId = $context->getSource()->getUserId();

        $this->entityManager->runInTransactionWithRetry(
            function () use ($context, $returnOrderPayload, $userId): void {
                $this->returnOrderService->requestReturnOrders([$returnOrderPayload], $context, $userId);
                $this->returnOrderService->approveReturnOrders([$returnOrderPayload['id']], $context, $userId);
                $this->returnOrderService->moveStockIntoReturnOrders(
                    $this->returnOrderService->getReturnOrderLineItemQuantitiesByReturnOrderId([$returnOrderPayload['id']], $context),
                    $context,
                    $userId,
                );
            },
        );

        return $this->entityResponseService->makeEntityDetailResponse(
            ReturnOrderDefinition::class,
            $returnOrderPayload['id'],
            $context,
            $request,
            $request->get('associations', []),
        );
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(
     *     "/api/_action/pickware-erp/complete-return-order",
     *     name="api.action.pickware-erp.complete-return-order",
     *     methods={"POST"}
     * )
     * @JsonValidation(schemaFilePath="payload-complete-return-order.schema.json")
     */
    public function completeReturnOrder(Context $context, Request $request): Response
    {
        $returnOrderId = $request->get('returnOrderId');
        $stockAdjustments = $request->get('stockAdjustments');

        $this->entityManager->runInTransactionWithRetry(
            function () use ($returnOrderId, $stockAdjustments, $context): void {
                $this->returnOrderService->completeReturnOrders([$returnOrderId], $context);
                $this->returnOrderService->moveStockFromReturnOrders(
                    [$returnOrderId => $stockAdjustments],
                    $context,
                    $context->getSource()->getUserId(),
                );
            },
        );

        return $this->entityResponseService->makeEntityDetailResponse(
            ReturnOrderDefinition::class,
            $returnOrderId,
            $context,
            $request,
            $request->get('associations', []),
        );
    }
}
