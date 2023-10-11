<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareErpStarter\GoodsReceipt\Model;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void add(GoodsReceiptEntity $entity)
 * @method void set(string $key, GoodsReceiptEntity $entity)
 * @method GoodsReceiptEntity[] getIterator()
 * @method GoodsReceiptEntity[] getElements()
 * @method GoodsReceiptEntity|null get(string $key)
 * @method GoodsReceiptEntity|null first()
 * @method GoodsReceiptEntity|null last()
 */
class GoodsReceiptCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return GoodsReceiptEntity::class;
    }
}
