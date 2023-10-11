<?php declare(strict_types=1);

namespace Swag\Security\Fixes\NEXT23325;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\CartValidatorInterface;
use Shopware\Core\Checkout\Cart\Error\ErrorCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Content\Product\Cart\ProductCartProcessor;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class ProductLineItemValidator implements CartValidatorInterface
{
    public function validate(Cart $cart, ErrorCollection $errors, SalesChannelContext $context): void
    {
        if (\defined('ProductCartProcessor::SKIP_PRODUCT_STOCK_VALIDATION')) {
            if (\method_exists($cart, 'getBehavior')) {
                $behavior = $cart->getBehavior();
            } else {
                $behavior = new CartBehavior($context->getPermissions());
            }

            if ($behavior !== null && $behavior->hasPermission(ProductCartProcessor::SKIP_PRODUCT_STOCK_VALIDATION)) {
                return;
            }
        }

        $productLineItems = array_filter($cart->getLineItems()->getFlat(), static function (LineItem $lineItem) {
            return $lineItem->getType() === LineItem::PRODUCT_LINE_ITEM_TYPE;
        });

        $quantities = [];
        $refs = [];
        foreach ($productLineItems as $lineItem) {
            $productId = $lineItem->getReferencedId();
            if ($productId === null) {
                continue;
            }

            $quantities[$productId] = $lineItem->getQuantity() + ($quantities[$productId] ?? 0);

            // only needed one time to check max quantity
            $refs[$productId] = $lineItem;
        }

        foreach ($quantities as $productId => $quantity) {
            $lineItem = $refs[$productId];
            $quantityInformation = $lineItem->getQuantityInformation();
            if ($quantityInformation === null) {
                continue;
            }

            $minPurchase = $quantityInformation->getMinPurchase();
            $available = $quantityInformation->getMaxPurchase() ?? 0;
            $steps = $quantityInformation->getPurchaseSteps() ?? 1;

            if ($available >= $quantity) {
                continue;
            }

            $maxAvailable = (int) (floor(($available - $minPurchase) / $steps) * $steps + $minPurchase);

            $cart->addErrors(
                new ProductStockReachedError($productId, (string) $lineItem->getLabel(), $maxAvailable, false)
            );
        }
    }
}
