<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Form\Aggregate\FormGroup;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Swag\CmsExtensions\Form\Aggregate\FormGroupField\FormGroupFieldCollection;

/**
 * @extends EntityCollection<FormGroupEntity>
 *
 * @method void                        add(FormGroupEntity $entity)
 * @method void                        set(string $key, FormGroupEntity $entity)
 * @method \Generator<FormGroupEntity> getIterator()
 * @method FormGroupEntity[]           getElements()
 * @method FormGroupEntity|null        get(string $key)
 * @method FormGroupEntity|null        first()
 * @method FormGroupEntity|null        last()
 */
class FormGroupCollection extends EntityCollection
{
    public function getFields(): FormGroupFieldCollection
    {
        $collection = new FormGroupFieldCollection();

        /** @var FormGroupEntity $group */
        foreach ($this->elements as $group) {
            $fields = $group->getFields();

            if ($fields === null || $fields->count() === 0) {
                continue;
            }

            $collection->merge($fields);
        }

        return $collection;
    }

    protected function getExpectedClass(): string
    {
        return FormGroupEntity::class;
    }
}
