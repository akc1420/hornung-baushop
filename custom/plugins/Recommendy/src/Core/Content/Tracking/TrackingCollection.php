<?php declare(strict_types=1);

namespace Recommendy\Core\Content\Tracking;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void               add(TrackingEntity $entity)
 * @method void               set(string $key, TrackingEntity $entity)
 * @method TrackingEntity[]    getIterator()
 * @method TrackingEntity[]    getElements()
 * @method TrackingEntity|null get(string $key)
 * @method TrackingEntity|null first()
 * @method TrackingEntity|null last()
 */
class TrackingCollection extends EntityCollection
{
    /**
     * @return string
     */
    protected function getExpectedClass(): string
    {
        return TrackingEntity::class;
    }
}