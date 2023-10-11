<?php

declare(strict_types=1);

namespace Sisi\Search\Core\Content\Task\Bundle;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void              add(DBSchedularEntity $entity)
 * @method void              set(string $key, DBSchedularEntity $entity)
 * @method DBSchedularEntity[]    getIterator()
 * @method DBSchedularEntity[]    getElements()
 * @method DBSchedularEntity|null get(string $key)
 * @method DBSchedularEntity|null first()
 * @method DBSchedularEntity|null last()
 */
class DBSchedularCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return DBSchedularEntity::class;
    }
}
