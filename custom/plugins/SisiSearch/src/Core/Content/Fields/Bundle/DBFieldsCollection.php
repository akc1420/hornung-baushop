<?php

declare(strict_types=1);

namespace Sisi\Search\Core\Content\Fields\Bundle;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void              add(DBFieldsEntity $entity)
 * @method void              set(string $key, DBFieldsEntity $entity)
 * @method DBFieldsEntity[]    getIterator()
 * @method DBFieldsEntity[]    getElements()
 * @method DBFieldsEntity|null get(string $key)
 * @method DBFieldsEntity|null first()
 * @method DBFieldsEntity|null last()
 */
class DBFieldsCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return DBFieldsEntity::class;
    }
}
