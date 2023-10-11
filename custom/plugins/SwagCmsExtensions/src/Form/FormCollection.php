<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Form;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<FormEntity>
 *
 * @method void                   add(FormEntity $entity)
 * @method void                   set(string $key, FormEntity $entity)
 * @method \Generator<FormEntity> getIterator()
 * @method FormEntity[]           getElements()
 * @method FormEntity|null        get(string $key)
 * @method FormEntity|null        first()
 * @method FormEntity|null        last()
 */
class FormCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return FormEntity::class;
    }
}
