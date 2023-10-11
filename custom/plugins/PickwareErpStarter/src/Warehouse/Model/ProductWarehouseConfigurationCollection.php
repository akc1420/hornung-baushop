<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareErpStarter\Warehouse\Model;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void add(ProductWarehouseConfigurationEntity $entity)
 * @method void set(string $key, ProductWarehouseConfigurationEntity $entity)
 * @method ProductWarehouseConfigurationEntity[] getIterator()
 * @method ProductWarehouseConfigurationEntity[] getElements()
 * @method ProductWarehouseConfigurationEntity|null get(string $key)
 * @method ProductWarehouseConfigurationEntity|null first()
 * @method ProductWarehouseConfigurationEntity|null last()
 */
class ProductWarehouseConfigurationCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return ProductWarehouseConfigurationEntity::class;
    }
}
