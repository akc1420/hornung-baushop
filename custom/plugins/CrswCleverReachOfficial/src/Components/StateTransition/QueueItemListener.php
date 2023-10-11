<?php


namespace Crsw\CleverReachOfficial\Components\StateTransition;

use Crsw\CleverReachOfficial\Components\Entities\StateTransitionRecord;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Entity;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\QueryFilter\Operators;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\QueryFilter\QueryFilter;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Service\BusinessLogic\StateTransition\StateTransitionRecordService;
use JsonException;

/**
 * Class QueueItemListener
 *
 * @package Crsw\CleverReachOfficial\Components\StateTransition
 */
class QueueItemListener
{
    /**
     * Gets StateTransitionRecord with given task type.
     *
     * @param $taskType
     * @return Entity|null
     *
     * @throws QueryFilterInvalidParamException
     * @throws JsonException
     */
    public static function getExistingRecord($taskType): ?Entity
    {
        $filter = new QueryFilter();

        $filter->where('taskType', Operators::EQUALS, $taskType);

        return static::getStateTransitionService()->findBy($filter)[0];
    }

    /**
     * Updates or creates StateTransitionRecord.
     *
     * @param StateTransitionRecord $entity
     * @param Entity|null $record
     */
    public static function updateOrCreateStateTransitionRecord(
        StateTransitionRecord $entity,
        Entity $record = null
    ): void {
        if ($record) {
            $entity->setId($record->getId());
            static::getStateTransitionService()->update($entity);
        } else {
            static::getStateTransitionService()->create($entity);
        }
    }

    /**
     * @return StateTransitionRecordService
     */
    public static function getStateTransitionService(): StateTransitionRecordService
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(StateTransitionRecordService::class);
    }
}