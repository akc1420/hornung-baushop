<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Quickview;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<QuickviewEntity>
 *
 * @method void                        add(QuickviewEntity $entity)
 * @method void                        set(string $key, QuickviewEntity $entity)
 * @method \Generator<QuickviewEntity> getIterator()
 * @method QuickviewEntity[]           getElements()
 * @method QuickviewEntity|null        get(string $key)
 * @method QuickviewEntity|null        first()
 * @method QuickviewEntity|null        last()
 */
class QuickviewCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return QuickviewEntity::class;
    }
}
