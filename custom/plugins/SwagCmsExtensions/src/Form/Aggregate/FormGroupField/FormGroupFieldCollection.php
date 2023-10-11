<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Form\Aggregate\FormGroupField;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<FormGroupFieldEntity>
 *
 * @method void                             add(FormGroupFieldEntity $entity)
 * @method void                             set(string $key, FormGroupFieldEntity $entity)
 * @method \Generator<FormGroupFieldEntity> getIterator()
 * @method FormGroupFieldEntity[]           getElements()
 * @method FormGroupFieldEntity|null        get(string $key)
 * @method FormGroupFieldEntity|null        first()
 * @method FormGroupFieldEntity|null        last()
 */
class FormGroupFieldCollection extends EntityCollection
{
    public function getFieldByTechnicalName(string $technicalName): ?FormGroupFieldEntity
    {
        return $this->filter(static fn (FormGroupFieldEntity $field) => $field->getTechnicalName() === $technicalName)->first();
    }

    protected function getExpectedClass(): string
    {
        return FormGroupFieldEntity::class;
    }
}
