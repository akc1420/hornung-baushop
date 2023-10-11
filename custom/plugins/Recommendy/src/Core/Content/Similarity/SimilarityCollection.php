<?php declare(strict_types=1);

namespace Recommendy\Core\Content\Similarity;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void               add(SimilarityEntity $entity)
 * @method void               set(string $key, SimilarityEntity $entity)
 * @method SimilarityEntity[]    getIterator()
 * @method SimilarityEntity[]    getElements()
 * @method SimilarityEntity|null get(string $key)
 * @method SimilarityEntity|null first()
 * @method SimilarityEntity|null last()
 */
class SimilarityCollection extends EntityCollection
{
    /**
     * @return string
     */
    protected function getExpectedClass(): string
    {
        return SimilarityEntity::class;
    }
}