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

use Pickware\PickwareErpStarter\Collection\ImmutableCollection;
use Pickware\PickwareErpStarter\Stocking\ProductQuantity;

class ProductQuantityImmutableCollectionExtension
{
    /**
     * @param ImmutableCollection<ProductQuantity> $minuend
     * @param ImmutableCollection<ProductQuantity> $subtrahend
     * @return ImmutableCollection<ProductQuantity>
     */
    public static function subtract(ImmutableCollection $minuend, ImmutableCollection $subtrahend): ImmutableCollection
    {
        $quantitiesByProductId = [];
        foreach ($minuend as $element) {
            $quantitiesByProductId[$element->getProductId()] ??= 0;
            $quantitiesByProductId[$element->getProductId()] += $element->getQuantity();
        }
        foreach ($subtrahend as $element) {
            $quantitiesByProductId[$element->getProductId()] ??= 0;
            $quantitiesByProductId[$element->getProductId()] -= $element->getQuantity();
        }

        return new ImmutableCollection(array_map(
            fn (string $productId, int $quantity) => new ProductQuantity($productId, $quantity),
            array_keys($quantitiesByProductId),
            array_values($quantitiesByProductId),
        ));
    }
}
