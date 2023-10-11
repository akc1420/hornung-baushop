<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\ScrollNavigation\Aggregate\ScrollNavigationPageSettings;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<ScrollNavigationPageSettingsEntity>
 *
 * @method void                                           add(ScrollNavigationPageSettingsEntity $entity)
 * @method void                                           set(string $key, ScrollNavigationPageSettingsEntity $entity)
 * @method \Generator<ScrollNavigationPageSettingsEntity> getIterator()
 * @method ScrollNavigationPageSettingsEntity[]           getElements()
 * @method ScrollNavigationPageSettingsEntity|null        get(string $key)
 * @method ScrollNavigationPageSettingsEntity|null        first()
 * @method ScrollNavigationPageSettingsEntity|null        last()
 */
class ScrollNavigationPageSettingsCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return ScrollNavigationPageSettingsEntity::class;
    }
}
