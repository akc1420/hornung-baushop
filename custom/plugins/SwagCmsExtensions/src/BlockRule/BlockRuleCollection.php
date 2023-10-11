<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\BlockRule;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<BlockRuleEntity>
 *
 * @method void                        add(BlockRuleEntity $entity)
 * @method void                        set(string $key, BlockRuleEntity $entity)
 * @method \Generator<BlockRuleEntity> getIterator()
 * @method BlockRuleEntity[]           getElements()
 * @method BlockRuleEntity|null        get(string $key)
 * @method BlockRuleEntity|null        first()
 * @method BlockRuleEntity|null        last()
 */
class BlockRuleCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return BlockRuleEntity::class;
    }
}
