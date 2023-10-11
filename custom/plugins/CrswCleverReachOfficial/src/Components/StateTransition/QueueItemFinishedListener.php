<?php


namespace Crsw\CleverReachOfficial\Components\StateTransition;

use Crsw\CleverReachOfficial\Components\Entities\StateTransitionRecord;
use Crsw\CleverReachOfficial\Core\BusinessLogic\InitialSynchronization\Tasks\Composite\InitialSyncTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\TaskExecution\Events\QueueItemFinishedEvent;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueItem;
use JsonException;

/**
 * Class QueueItemFinishedListener
 *
 * @package Crsw\CleverReachOfficial\Components\StateTransition
 */
class QueueItemFinishedListener extends QueueItemListener
{
    /**
     * Handles queue item finished event.
     *
     * @param QueueItemFinishedEvent $event
     *
     * @throws QueryFilterInvalidParamException
     * @throws QueueItemDeserializationException
     * @throws JsonException
     */
    public static function handle(QueueItemFinishedEvent $event): void
    {
        $queueItem = $event->getQueueItem();
        $taskType = $queueItem->getTaskType();

        if ($taskType === InitialSyncTask::getClassName()) {
            $entity = new StateTransitionRecord();
            $entity->setStatus(QueueItem::COMPLETED);
            $entity->setTaskType($taskType);
            $entity->setResolved(false);
            $entity->setQueueItem($queueItem);

            $existingRecord = self::getExistingRecord($taskType);

            self::updateOrCreateStateTransitionRecord($entity, $existingRecord);
        }
    }
}