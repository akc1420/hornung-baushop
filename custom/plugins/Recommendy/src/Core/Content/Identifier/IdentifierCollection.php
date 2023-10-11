<?php declare(strict_types=1);

namespace Recommendy\Core\Content\Identifier;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void               add(IdentifierEntity $entity)
 * @method void               set(string $key, IdentifierEntity $entity)
 * @method IdentifierEntity[]    getIterator()
 * @method IdentifierEntity[]    getElements()
 * @method IdentifierEntity|null get(string $key)
 * @method IdentifierEntity|null first()
 * @method IdentifierEntity|null last()
 */
class IdentifierCollection extends EntityCollection
{
    /**
     * @return string
     */
    protected function getExpectedClass(): string
    {
        return IdentifierEntity::class;
    }
}