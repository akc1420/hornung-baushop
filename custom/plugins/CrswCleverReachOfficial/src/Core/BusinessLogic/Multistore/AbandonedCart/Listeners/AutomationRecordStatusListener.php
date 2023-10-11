<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Listeners;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Contracts\RecoveryEmailStatus;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Interfaces\AutomationRecordService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Interfaces\AutomationRecordTrigger;
use Crsw\CleverReachOfficial\Core\BusinessLogic\TaskExecution\Events\QueueItemAbortedEvent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\TaskExecution\Events\QueueItemEnqueuedEvent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\TaskExecution\Events\QueueItemFailedEvent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\TaskExecution\Events\QueueItemFinishedEvent;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueItem;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Task;

/**
 * Class AutomationRecordStatusListener
 *
 * @package Crsw\CleverReachOfficial\Core\Tests\BusinessLogic\Multistore\AbandonedCart\Listeners
 */
class AutomationRecordStatusListener
{
    /**
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\TaskExecution\Events\QueueItemEnqueuedEvent $event
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Exceptions\FailedToUpdateAutomationRecordException
     */
    public function onEnqueue(QueueItemEnqueuedEvent $event)
    {
        $task = $event->getTask();
        if ($this->isEventTaskValid($task)) {
            $this->updateRecord($task->getRecordId(), RecoveryEmailStatus::SENDING);
        }
    }

    /**
     * @param QueueItemFailedEvent $event
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Exceptions\FailedToUpdateAutomationRecordException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     */
    public function onFail(QueueItemFailedEvent $event)
    {
        $task = $event->getQueueItem()->getTask();
        if ($task && $this->isEventTaskValid($task) && $event->getQueueItem()->getStatus() === QueueItem::FAILED) {
            $this->updateRecord($task->getRecordId(), RecoveryEmailStatus::NOT_SENT, $event->getFailureDescription());
        }
    }

    /**
     * @param QueueItemAbortedEvent $event
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Exceptions\FailedToUpdateAutomationRecordException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     */
    public function onAbort(QueueItemAbortedEvent $event)
    {
        $task = $event->getQueueItem()->getTask();
        if ($task && $this->isEventTaskValid($task)) {
            $this->updateRecord($task->getRecordId(), RecoveryEmailStatus::NOT_SENT, $event->getAbortDescription());
        }
    }



    /**
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\TaskExecution\Events\QueueItemFinishedEvent $event
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Exceptions\FailedToUpdateAutomationRecordException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     */
    public function onComplete(QueueItemFinishedEvent $event)
    {
        $task = $event->getQueueItem()->getTask();
        if ($task && $this->isEventTaskValid($task)) {
            $this->updateRecord($task->getRecordId(), RecoveryEmailStatus::SENT);
        }
    }

    /**
     * @param int $recordId
     * @param string $status
     * @param string $errorMessage
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Exceptions\FailedToUpdateAutomationRecordException
     */
    private function updateRecord($recordId, $status, $errorMessage = '')
    {
        /** @var \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Interfaces\AutomationRecordService $recordService */
        $recordService = ServiceRegister::getService(AutomationRecordService::CLASS_NAME);
        $record = $recordService->find($recordId);
        if ($record) {
            $record->setStatus($status);
            $record->setErrorMessage($errorMessage);
            if ($status === RecoveryEmailStatus::SENT) {
                $record->setSentTime(new \DateTime());
            }

            $recordService->update($record);
        }
    }

    /**
     * @param \Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Task $task
     *
     * @return bool
     */
    private function isEventTaskValid(Task $task)
    {
        return $task instanceof AutomationRecordTrigger;
    }
}
