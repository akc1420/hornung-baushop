<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Form\Aggregate\FormGroupFieldTranslation;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<FormGroupFieldTranslationEntity>
 *
 * @method void                                        add(FormGroupFieldTranslationEntity $entity)
 * @method void                                        set(string $key, FormGroupFieldTranslationEntity $entity)
 * @method \Generator<FormGroupFieldTranslationEntity> getIterator()
 * @method FormGroupFieldTranslationEntity[]           getElements()
 * @method FormGroupFieldTranslationEntity|null        get(string $key)
 * @method FormGroupFieldTranslationEntity|null        first()
 * @method FormGroupFieldTranslationEntity|null        last()
 */
class FormGroupFieldTranslationCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return FormGroupFieldTranslationEntity::class;
    }
}
