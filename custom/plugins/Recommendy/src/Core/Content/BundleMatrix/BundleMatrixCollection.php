<?php declare(strict_types=1);

namespace Recommendy\Core\Content\BundleMatrix;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void               add(BundleMatrixEntity $entity)
 * @method void               set(string $key, BundleMatrixEntity $entity)
 * @method BundleMatrixEntity[]    getIterator()
 * @method BundleMatrixEntity[]    getElements()
 * @method BundleMatrixEntity|null get(string $key)
 * @method BundleMatrixEntity|null first()
 * @method BundleMatrixEntity|null last()
 */
class BundleMatrixCollection extends EntityCollection
{
    /**
     * @return string
     */
    protected function getExpectedClass(): string
    {
        return BundleMatrixEntity::class;
    }
}