<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareErpStarter\GoodsReceipt\Controller;

use Pickware\DalBundle\EntityManager;
use Pickware\PickwareErpStarter\GoodsReceipt\GoodsReceiptCreationService;
use Pickware\PickwareErpStarter\GoodsReceipt\GoodsReceiptPriceCalculationService;
use Pickware\PickwareErpStarter\GoodsReceipt\Model\GoodsReceiptDefinition;
use Pickware\ValidationBundle\Annotation\JsonValidation;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GoodsReceiptController
{
    private EntityManager $entityManager;
    private GoodsReceiptCreationService $goodsReceiptCreationService;
    private GoodsReceiptPriceCalculationService $goodsReceiptPriceCalculationService;

    public function __construct(
        EntityManager $entityManager,
        GoodsReceiptCreationService $goodsReceiptCreationService,
        GoodsReceiptPriceCalculationService $goodsReceiptPriceCalculationService
    ) {
        $this->entityManager = $entityManager;
        $this->goodsReceiptCreationService = $goodsReceiptCreationService;
        $this->goodsReceiptPriceCalculationService = $goodsReceiptPriceCalculationService;
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(
     *     "/api/_action/pickware-erp/goods-receipt/create-goods-receipt-for-supplier-order",
     *     methods={"POST"}
     * )
     * @JsonValidation(schemaFilePath="payload-create-goods-receipt-for-supplier-order.schema.json")
     */
    public function createGoodsReceiptForSupplierOrder(Request $request, Context $context): Response
    {
        $this->entityManager->runInTransactionWithRetry(function () use ($request, $context): void {
            $goodsReceiptId = $request->get('goodsReceiptId');
            $existingGoodsReceipt = $this->entityManager->findByPrimaryKey(GoodsReceiptDefinition::class, $goodsReceiptId, $context);
            if ($existingGoodsReceipt !== null) {
                return;
            }

            $this->goodsReceiptCreationService->createGoodsReceiptForSupplierOrder(
                $goodsReceiptId,
                $request->get('supplierOrderId'),
                $request->get('lineItemQuantities'),
                $context->getSource()->getUserId(),
                $context,
            );
        });

        return new Response('', Response::HTTP_ACCEPTED);
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(
     *     "/api/_action/pickware-erp/goods-receipt/recalculate-goods-receipts",
     *     methods={"POST"}
     * )
     * @JsonValidation(schemaFilePath="payload-recalculate-goods-receipts.schema.json")
     */
    public function recalculateGoodsReceipts(Request $request, Context $context): Response
    {
        $goodsReceiptIds = $request->get('goodsReceiptIds');
        $this->goodsReceiptPriceCalculationService->recalculateGoodsReceipts($goodsReceiptIds, $context);

        return new Response('', Response::HTTP_ACCEPTED);
    }
}
