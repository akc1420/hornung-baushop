<?php


namespace Crsw\CleverReachOfficial\Entity\Base\Collection;

use Crsw\CleverReachOfficial\Entity\Base\BaseEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * Class BaseEntityCollection
 *
 * @package Crsw\CleverReachOfficial\Entity\Base\Collection
 *
 * @method void              add(BaseEntity $entity)
 * @method void              set(string $key, BaseEntity $entity)
 * @method BaseEntity[]      getIterator()
 * @method BaseEntity[]      getElements()
 * @method BaseEntity|null   get(string $key)
 * @method BaseEntity|null   first()
 * @method BaseEntity|null   last()
 */
class BaseEntityCollection extends EntityCollection
{
    /**
     * @inheritDoc
     *
     * @return string
     */
    protected function getExpectedClass(): string
    {
        return BaseEntity::class;
    }
}