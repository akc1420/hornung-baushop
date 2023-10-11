<?php

namespace Crsw\CleverReachOfficial\Components\StateTransition;

use Crsw\CleverReachOfficial\Components\Entities\StateTransitionRecord;
use Crsw\CleverReachOfficial\Components\Tasks\OrdersOlderThanOneYearSyncTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\InitialSynchronization\Tasks\Composite\InitialSyncTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\SecondarySynchronization\Tasks\Composite\SecondarySyncTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\TaskExecution\Events\QueueItemFailedEvent;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueItem;
use JsonException;

/**
 * Class QueueItemFailedListener
 *
 * @package Crsw\CleverReachOfficial\Components\StateTransition
 */
class QueueItemFailedListener extends QueueItemListener
{
    /**
     * Handles queue item failed event.
     *
     * @param QueueItemFailedEvent $event
     *
     * @throws QueryFilterInvalidParamException
     * @throws QueueItemDeserializationException
     * @throws JsonException
     */
    public static function handle(QueueItemFailedEvent $event): void
    {
        $allowedTaskTypes = [
            InitialSyncTask::getClassName(),
            SecondarySyncTask::getClassName(),
            OrdersOlderThanOneYearSyncTask::getClassName()
        ];

        $queueItem = $event->getQueueItem();
        $taskType = $queueItem->getTaskType();

        if (in_array($taskType, $allowedTaskTypes, true)) {
            $entity = new StateTransitionRecord();
            $entity->setStatus(QueueItem::FAILED);
            $entity->setTaskType($taskType);
            $entity->setResolved(false);
            $entity->setQueueItem($queueItem);
            $entity->setDescription($event->getFailureDescription());

            $existingRecord = self::getExistingRecord($taskType);

            self::updateOrCreateStateTransitionRecord($entity, $existingRecord);
        }
    }
}