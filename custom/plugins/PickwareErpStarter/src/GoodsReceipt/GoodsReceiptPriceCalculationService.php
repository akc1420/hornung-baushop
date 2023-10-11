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
use Pickware\PickwareErpStarter\GoodsReceipt\Model\GoodsReceiptCollection;
use Pickware\PickwareErpStarter\GoodsReceipt\Model\GoodsReceiptDefinition;
use Pickware\PickwareErpStarter\GoodsReceipt\Model\GoodsReceiptEntity;
use Pickware\PickwareErpStarter\GoodsReceipt\Model\GoodsReceiptItemEntity;
use Pickware\PickwareErpStarter\PriceCalculation\CartPriceCalculator;
use Pickware\PickwareErpStarter\PriceCalculation\PriceCalculationContext;
use Pickware\PickwareErpStarter\PriceCalculation\PriceCalculator;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\PriceCollection;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Framework\Context;

class GoodsReceiptPriceCalculationService
{
    private EntityManager $entityManager;
    private PriceCalculator $priceCalculator;
    private CartPriceCalculator $cartPriceCalculator;

    public function __construct(
        EntityManager $entityManager,
        PriceCalculator $priceCalculator,
        CartPriceCalculator $cartPriceCalculator
    ) {
        $this->entityManager = $entityManager;
        $this->priceCalculator = $priceCalculator;
        $this->cartPriceCalculator = $cartPriceCalculator;
    }

    public function recalculateGoodsReceipts(array $goodsReceiptIds, Context $context): void
    {
        /** @var GoodsReceiptCollection $goodsReceipts */
        $goodsReceipts = $this->entityManager->findBy(
            GoodsReceiptDefinition::class,
            ['id' => $goodsReceiptIds],
            $context,
            ['items'],
        );
        if (count($goodsReceiptIds) > $goodsReceipts->count()) {
            throw new InvalidArgumentException(
                sprintf(
                    'Goods receipts with the IDs "%s" were not found',
                    implode(', ', array_diff($goodsReceiptIds, $goodsReceipts->getIds())),
                ),
            );
        }

        $updatePayloads = [];
        /** @var GoodsReceiptEntity $goodsReceipt */
        foreach ($goodsReceipts as $goodsReceipt) {
            // If necessary price properties are missing _no_ price can be recalculated (goods receipt price or line
            // item prices).
            $canCalculateAnyPrice = $goodsReceipt->getTaxStatus() && $goodsReceipt->getItemRounding() && $goodsReceipt->getTotalRounding();
            if (!$canCalculateAnyPrice) {
                continue;
            }

            $priceCalculationContext = new PriceCalculationContext(
                $goodsReceipt->getTaxStatus(),
                $goodsReceipt->getItemRounding(),
                $goodsReceipt->getTotalRounding(),
            );

            $goodsReceiptItemUpdatePayloads = [];
            $lineItemPrices = new PriceCollection();
            /** @var GoodsReceiptItemEntity $goodsReceiptItem */
            foreach ($goodsReceipt->getItems() as $goodsReceiptItem) {
                // Recalculate each goods receipt item price if the item has a price definition
                if (!$goodsReceiptItem->getPriceDefinition()) {
                    continue;
                }
                // The PriceCalculator only supports QuantityPriceDefinition (as for now)
                if ($goodsReceiptItem->getPriceDefinition()->getType() !== QuantityPriceDefinition::TYPE) {
                    $lineItemPrices->add($goodsReceiptItem->getPrice());
                    continue;
                }
                $newLineItemPrice = $this->priceCalculator->calculatePrice(
                    $goodsReceiptItem->getPriceDefinition(),
                    $priceCalculationContext,
                );
                $goodsReceiptItemUpdatePayloads[] = [
                    'id' => $goodsReceiptItem->getId(),
                    'price' => $newLineItemPrice,
                ];
                $lineItemPrices->add($newLineItemPrice);
            }

            // Only calculate the goods receipt (total) price, if all line items have prices
            if (count($lineItemPrices) === count($goodsReceipt->getItems())) {
                $cartPrice = $this->cartPriceCalculator->calculateCartPrice($lineItemPrices, $priceCalculationContext);
            } else {
                $cartPrice = null;
            }

            $updatePayloads[] = [
                'id' => $goodsReceipt->getId(),
                'price' => $cartPrice,
                'items' => $goodsReceiptItemUpdatePayloads,
            ];
        }

        $this->entityManager->update(GoodsReceiptDefinition::class, $updatePayloads, $context);
    }

    /**
     * @param string $taxState See Shopware\Core\Checkout\Cart\Price\Struct\CartPrice
     */
    public function createEmptyCartPrice(string $taxState): CartPrice
    {
        return new CartPrice(
            0.0,
            0.0,
            0.0,
            new CalculatedTaxCollection(),
            new TaxRuleCollection(),
            $taxState,
            0.0,
        );
    }
}
