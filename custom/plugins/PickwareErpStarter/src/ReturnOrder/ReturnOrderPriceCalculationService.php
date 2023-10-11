<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareErpStarter\ReturnOrder;

use Pickware\DalBundle\EntityManager;
use Pickware\PickwareErpStarter\PriceCalculation\CartPriceCalculator;
use Pickware\PickwareErpStarter\PriceCalculation\PriceCalculationContext;
use Pickware\PickwareErpStarter\PriceCalculation\PriceCalculator;
use Pickware\PickwareErpStarter\ReturnOrder\Model\ReturnOrderCollection;
use Pickware\PickwareErpStarter\ReturnOrder\Model\ReturnOrderDefinition;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\PriceCollection;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Framework\Context;

class ReturnOrderPriceCalculationService
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

    /**
     * @deprecated next-major will be removed. Use `recalculateReturnOrders` instead.
     */
    public function recalculateReturnOrder(string $returnOrderId, Context $context): void
    {
        $this->recalculateReturnOrders([$returnOrderId], $context);
    }

    public function recalculateReturnOrders(array $returnOrderIds, Context $context): void
    {
        /** @var ReturnOrderCollection $returnOrders */
        $returnOrders = $this->entityManager->findBy(
            ReturnOrderDefinition::class,
            ['id' => $returnOrderIds],
            $context,
            [
                'lineItems',
                'refund',
                'order',
            ],
        );
        if (count($returnOrderIds) > $returnOrders->count()) {
            ReturnOrderException::returnOrderNotFound($returnOrderIds, $returnOrders->getKeys());
        }

        $updatePayloads = [];
        foreach ($returnOrders as $returnOrder) {
            $priceCalculationContext = new PriceCalculationContext(
                $returnOrder->getTaxStatus(),
                $returnOrder->getOrder()->getItemRounding(),
                $returnOrder->getOrder()->getTotalRounding(),
            );

            $returnOrderLineItemUpdatePayloads = [];
            $lineItemPrices = new PriceCollection();
            foreach ($returnOrder->getLineItems() as $returnOrderLineItem) {
                // Recalculate each return order line item price. The PriceCalculator only supports
                // QuantityPriceDefinition (as for now)
                if ($returnOrderLineItem->getPriceDefinition()->getType() !== QuantityPriceDefinition::TYPE) {
                    $lineItemPrices->add($returnOrderLineItem->getPrice());
                    continue;
                }
                $newLineItemPrice = $this->priceCalculator->calculatePrice(
                    $returnOrderLineItem->getPriceDefinition(),
                    $priceCalculationContext,
                );
                $returnOrderLineItemUpdatePayloads[] = [
                    'id' => $returnOrderLineItem->getId(),
                    'price' => $newLineItemPrice,
                ];
                $lineItemPrices->add($newLineItemPrice);
            }

            $cartPrice = $this->cartPriceCalculator->calculateCartPrice($lineItemPrices, $priceCalculationContext);

            $updatePayloads[] = [
                'id' => $returnOrder->getId(),
                'price' => $cartPrice,
                // All return order line item prices are updates within the same operation
                'lineItems' => $returnOrderLineItemUpdatePayloads,
                'refund' => [
                    'id' => $returnOrder->getRefund()->getId(),
                    'moneyValue' => [
                        'value' => $cartPrice->getTotalPrice(),
                        'currency' => [
                            'isoCode' => $returnOrder->getRefund()->getCurrencyIsoCode(),
                        ],
                    ],
                ],
            ];
        }

        $this->entityManager->update(ReturnOrderDefinition::class, $updatePayloads, $context);
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

    public function createEmptyCalculatedPrice(): CalculatedPrice
    {
        return new CalculatedPrice(
            0.0,
            0.0,
            new CalculatedTaxCollection(),
            new TaxRuleCollection(),
        );
    }
}
