<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\TaskExecution;

use Crsw\CleverReachOfficial\Core\BusinessLogic\API\Http\Proxy;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Contracts\AuthorizationService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Tasks\Composite\ConnectTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Scheduler\ScheduleCheckTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\TaskExecution\Events\QueueItemAbortedEvent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\TaskExecution\Events\QueueItemEnqueuedEvent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\TaskExecution\Events\QueueItemFailedEvent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\TaskExecution\Events\QueueItemFinishedEvent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\TaskExecution\Events\QueueItemRequeuedEvent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\TaskExecution\Events\QueueItemStartedEvent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\TaskExecution\Events\QueueItemStateTransitionEventBus;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Utility\Tasks\TaskCleanupTask;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\ExecutionRequirementsNotMetException;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Interfaces\Priority;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueItem;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueService as BaseService;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Task;
use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Events\Event;

/**
 * Class QueueService
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\TaskExecution
 */
class QueueService extends BaseService
{
    const CLASS_NAME = __CLASS__;

    /**
     * @inheritDoc
     *
     * @param string $queueName
     * @param \Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Task $task
     * @param string $context
     * @param int $priority
     *
     * @return \Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueItem
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    public function enqueue($queueName, Task $task, $context = '', $priority = Priority::NORMAL)
    {
        $item = parent::enqueue($queueName, $task, $context, $priority);
        $this->fireStateTransitionEvent(new QueueItemEnqueuedEvent($queueName, $task, $context, $priority));

        return $item;
    }

    /**
     * @inheritDoc
     *
     * @param \Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueItem $queueItem
     *
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\AbortTaskExecutionException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    public function start(QueueItem $queueItem)
    {
        parent::start($queueItem);
        $this->fireStateTransitionEvent(new QueueItemStartedEvent($queueItem));
    }

    public function finish(QueueItem $queueItem)
    {
        parent::finish($queueItem);
        $this->fireStateTransitionEvent(new QueueItemFinishedEvent($queueItem));
    }

    /**
     * @inheritDoc
     *
     * @param \Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueItem $queueItem
     *
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    public function requeue(QueueItem $queueItem)
    {
        parent::requeue($queueItem);
        $this->fireStateTransitionEvent(new QueueItemRequeuedEvent($queueItem));
    }

    /**
     * @inheritDoc
     *
     * @param \Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueItem $queueItem
     * @param string $abortDescription
     *
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    public function abort(QueueItem $queueItem, $abortDescription)
    {
        parent::abort($queueItem, $abortDescription);
        $this->fireStateTransitionEvent(new QueueItemAbortedEvent($queueItem, $abortDescription));
    }

    /**
     * @inheritDoc
     *
     * @param \Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueItem $queueItem
     * @param string $failureDescription
     *
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    public function fail(QueueItem $queueItem, $failureDescription)
    {
        parent::fail($queueItem, $failureDescription);
        $this->fireStateTransitionEvent(new QueueItemFailedEvent($queueItem, $failureDescription));
    }

    /**
     * Performs execution requirements validation validation.
     *
     * @param \Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueItem $queueItem
     *
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\ExecutionRequirementsNotMetException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     */
    public function validateExecutionRequirements(QueueItem $queueItem)
    {
        if (!$this->getApiStatusProxy()->isAPIActive()) {
            throw new ExecutionRequirementsNotMetException('API not operational.');
        }

        if (!$this->requiresAuthorization($queueItem)) {
            return;
        }

        if (!$this->getAuthService()->getFreshOfflineStatus()) {
            return;
        }

        throw new ExecutionRequirementsNotMetException('User is offline.');
    }

    /**
     * Checks if task requires authorization for execution.
     *
     * @param \Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueItem $queueItem
     *
     * @return bool
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     */
    protected function requiresAuthorization(QueueItem $queueItem)
    {
        $authorizationFreeTasks = array(
            ConnectTask::getClassName(),
            ScheduleCheckTask::getClassName(),
            TaskCleanupTask::getClassName(),
        );

        return !in_array($queueItem->getTaskType(), $authorizationFreeTasks, true);
    }

    /**
     * @param Event $event
     */
    public function fireStateTransitionEvent(Event $event)
    {
        QueueItemStateTransitionEventBus::getInstance()->fire($event);
    }

    /**
     * Retrieves api status proxy.
     *
     * @return Proxy
     */
    private function getApiStatusProxy()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(Proxy::CLASS_NAME);
    }

    /**
     * Retrieves authorization service.
     *
     * @return AuthorizationService
     */
    private function getAuthService()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(AuthorizationService::CLASS_NAME);
    }
}