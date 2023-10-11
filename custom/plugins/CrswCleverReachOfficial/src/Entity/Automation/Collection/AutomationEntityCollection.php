<?php


namespace Crsw\CleverReachOfficial\Entity\Automation\Collection;

use Crsw\CleverReachOfficial\Entity\Automation\AutomationEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * Class AutomationEntityCollection
 *
 * @package Crsw\CleverReachOfficial\Entity\Automation\Collection
 *
 * @method void              add(AutomationEntity $entity)
 * @method void              set(string $key, AutomationEntity $entity)
 * @method AutomationEntity[]      getIterator()
 * @method AutomationEntity[]      getElements()
 * @method AutomationEntity|null   get(string $key)
 * @method AutomationEntity|null   first()
 * @method AutomationEntity|null   last()
 */
class AutomationEntityCollection extends EntityCollection
{
    /**
     * @inheritDoc
     *
     * @return string
     */
    protected function getExpectedClass(): string
    {
        return AutomationEntity::class;
    }
}