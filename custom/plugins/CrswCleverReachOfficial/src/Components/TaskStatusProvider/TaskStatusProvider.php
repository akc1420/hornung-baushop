<?php

namespace Crsw\CleverReachOfficial\Components\TaskStatusProvider;

use Crsw\CleverReachOfficial\Components\Entities\StateTransitionRecord;
use Crsw\CleverReachOfficial\Core\BusinessLogic\InitialSynchronization\Tasks\Composite\InitialSyncTask;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\QueryFilter\Operators;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\QueryFilter\QueryFilter;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueItem;
use Crsw\CleverReachOfficial\Service\BusinessLogic\StateTransition\StateTransitionRecordService;
use JsonException;

/**
 * Class TaskStatusProvider
 *
 * @package Crsw\CleverReachOfficial\Components\TaskStatusProvider
 */
class TaskStatusProvider
{
    /**
     * Gets task data.
     *
     * @param QueueItem $queueItem
     *
     * @return array
     *
     * @throws QueryFilterInvalidParamException
     * @throws QueueItemDeserializationException
     * @throws JsonException
     */
    public function getTaskData(QueueItem $queueItem): array
    {
        if ($queueItem->getStatus() === QueueItem::FAILED) {
            return $this->getFailedTaskData($queueItem);
        }

        if ($queueItem->getStatus() === QueueItem::COMPLETED) {
            return $this->getFinishedTaskData($queueItem);
        }

        return ['status' => $queueItem->getStatus()];
    }

    /**
     * @param QueueItem $queueItem
     *
     * @return mixed
     *
     * @throws QueryFilterInvalidParamException
     * @throws QueueItemDeserializationException
     * @throws JsonException
     */
    private function getFailedTaskData(QueueItem $queueItem): array
    {
        $filter = new QueryFilter();
        $filter->where('taskType', Operators::EQUALS, $queueItem->getTaskType())
            ->where('resolved', Operators::EQUALS, false);

        /** @var StateTransitionRecord $stateTransitionRecord */
        $stateTransitionRecord = $this->getStateTransitionService()->findOneBy($filter);

        if (!$stateTransitionRecord) {
            $data['status'] = $queueItem->getStatus();
            $data['errorMessage'] = $queueItem->getFailureDescription();

            return $data;
        }

        $data['status'] = $stateTransitionRecord->getStatus();
        $data['errorMessage'] = $stateTransitionRecord->getDescription();

        return $data;
    }

    /**
     * @param QueueItem $queueItem
     *
     * @return mixed
     *
     * @throws QueryFilterInvalidParamException
     * @throws QueueItemDeserializationException
     * @throws JsonException
     */
    private function getFinishedTaskData(QueueItem $queueItem): array
    {
        if ($queueItem->getTaskType() === InitialSyncTask::getClassName()) {
            $filter = new QueryFilter();
            $filter->where('taskType', Operators::EQUALS, $queueItem->getTaskType())
                ->where('resolved', Operators::EQUALS, false);

            /** @var StateTransitionRecord $stateTransitionRecord */
            $stateTransitionRecord = $this->getStateTransitionService()->findOneBy($filter);

            if (!$stateTransitionRecord) {
                return $data['status'] = $queueItem->getStatus();
            }

            $this->resolveRecords($stateTransitionRecord);

            $data['status'] = $stateTransitionRecord->getStatus();
        } else {
            $data['status'] = $queueItem->getStatus();
        }

        return $data;
    }

    /**
     * @param StateTransitionRecord $record
     */
    private function resolveRecords(StateTransitionRecord $record): void
    {
        $this->getStateTransitionService()->resolve($record);
    }

    /**
     * @return StateTransitionRecordService
     */
    private function getStateTransitionService(): StateTransitionRecordService
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(StateTransitionRecordService::class);
    }
}