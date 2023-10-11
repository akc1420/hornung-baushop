<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Form\Aggregate\FormGroupTranslation;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<FormGroupTranslationEntity>
 *
 * @method void                                   add(FormGroupTranslationEntity $entity)
 * @method void                                   set(string $key, FormGroupTranslationEntity $entity)
 * @method \Generator<FormGroupTranslationEntity> getIterator()
 * @method FormGroupTranslationEntity[]           getElements()
 * @method FormGroupTranslationEntity|null        get(string $key)
 * @method FormGroupTranslationEntity|null        first()
 * @method FormGroupTranslationEntity|null        last()
 */
class FormGroupTranslationCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return FormGroupTranslationEntity::class;
    }
}
