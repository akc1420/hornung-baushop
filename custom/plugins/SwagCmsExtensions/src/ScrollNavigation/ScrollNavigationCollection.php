<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\ScrollNavigation;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<ScrollNavigationEntity>
 *
 * @method void                               add(ScrollNavigationEntity $entity)
 * @method void                               set(string $key, ScrollNavigationEntity $entity)
 * @method \Generator<ScrollNavigationEntity> getIterator()
 * @method ScrollNavigationEntity[]           getElements()
 * @method ScrollNavigationEntity|null        get(string $key)
 * @method ScrollNavigationEntity|null        first()
 * @method ScrollNavigationEntity|null        last()
 */
class ScrollNavigationCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return ScrollNavigationEntity::class;
    }
}
