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
 * @method void add(GoodsReceiptItemEntity $entity)
 * @method void set(string $key, GoodsReceiptItemEntity $entity)
 * @method GoodsReceiptItemEntity[] getIterator()
 * @method GoodsReceiptItemEntity[] getElements()
 * @method GoodsReceiptItemEntity|null get(string $key)
 * @method GoodsReceiptItemEntity|null first()
 * @method GoodsReceiptItemEntity|null last()
 */
class GoodsReceiptItemCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return GoodsReceiptItemEntity::class;
    }
}
