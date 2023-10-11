<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Form\Aggregate\FormTranslation;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<FormTranslationEntity>
 *
 * @method void                              add(FormTranslationEntity $entity)
 * @method void                              set(string $key, FormTranslationEntity $entity)
 * @method \Generator<FormTranslationEntity> getIterator()
 * @method FormTranslationEntity[]           getElements()
 * @method FormTranslationEntity|null        get(string $key)
 * @method FormTranslationEntity|null        first()
 * @method FormTranslationEntity|null        last()
 */
class FormTranslationCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return FormTranslationEntity::class;
    }
}
