<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\ScrollNavigation\Aggregate\ScrollNavigationTranslation;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<ScrollNavigationTranslationEntity>
 *
 * @method void                                          add(ScrollNavigationTranslationEntity $entity)
 * @method void                                          set(string $key, ScrollNavigationTranslationEntity $entity)
 * @method \Generator<ScrollNavigationTranslationEntity> getIterator()
 * @method ScrollNavigationTranslationEntity[]           getElements()
 * @method ScrollNavigationTranslationEntity|null        get(string $key)
 * @method ScrollNavigationTranslationEntity|null        first()
 * @method ScrollNavigationTranslationEntity|null        last()
 */
class ScrollNavigationTranslationCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return ScrollNavigationTranslationEntity::class;
    }
}
