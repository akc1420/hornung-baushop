<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Scheduler;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Scheduler\Exceptions\ScheduleSaveException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Scheduler\Interfaces\Schedulable;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Scheduler\Interfaces\ScheduleRepositoryInterface;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Scheduler\Models\Schedule;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\QueryFilter\Operators;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\QueryFilter\QueryFilter;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\RepositoryRegistry;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueItem;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueService;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Task;
use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\TimeProvider;

/**
 * Class ScheduleCheckTask
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Scheduler
 */
class ScheduleCheckTask extends Task
{
    /**
     * @var ScheduleRepositoryInterface
     */
    private $repository;

    /**
     * Runs task logic.
     *
     * @throws RepositoryNotRegisteredException
     * @throws QueryFilterInvalidParamException
     * @throws ScheduleSaveException
     */
    public function execute()
    {
        /** @var QueueService $queueService */
        $queueService = ServiceRegister::getService(QueueService::CLASS_NAME);

        foreach ($this->getSchedules() as $schedule) {
            try {
                if ($schedule->isRecurring()) {
                    $lastUpdateTimestamp = $schedule->getLastUpdateTimestamp();
                    $schedule->setNextSchedule();
                    $schedule->setLastUpdateTimestamp($this->now()->getTimestamp());
                    $this->getScheduleRepository()->saveWithCondition(
                        $schedule,
                        array('lastUpdateTimestamp' => $lastUpdateTimestamp)
                    );
                } else {
                    $this->getScheduleRepository()->delete($schedule);
                }

                $task = $schedule->getTask();

                if (!($task instanceof Schedulable)) {
                    Logger::logError("Cannot schedule task that is not schedulable: [{$task->getClassName()}].");

                    continue;
                }

                if (
                    !$task->canHaveMultipleQueuedInstances() &&
                    $this->isAlreadyEnqueued($schedule->getTaskType(), $schedule->getContext())
                ) {
                    Logger::logInfo("Scheduled task [{$task->getClassName()}] already enqueued.");

                    continue;
                }

                $queueService->enqueue($schedule->getQueueName(), $task, $schedule->getContext());
            } catch (QueueStorageUnavailableException $ex) {
                Logger::logError(
                    'Failed to enqueue task for schedule:' . $schedule->getId(),
                    'Core',
                    array('trace' => $ex->getTraceAsString())
                );
            }
        }

        $this->reportProgress(100);
    }

    /**
     * Returns an array of Schedules that are due for execution
     *
     * @return Schedule[]
     *
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryNotRegisteredException
     */
    private function getSchedules()
    {
        $queryFilter = new QueryFilter();
        $queryFilter->where('nextSchedule', Operators::LESS_OR_EQUAL_THAN, $this->now());
        $queryFilter->where('isEnabled', Operators::EQUALS, true);
        $queryFilter->orderBy('nextSchedule', QueryFilter::ORDER_ASC);
        $queryFilter->setLimit(1000);

        return $this->getScheduleRepository()->select($queryFilter);
    }

    /**
     * Returns current date and time
     *
     * @return \DateTime
     */
    protected function now()
    {
        /** @var TimeProvider $timeProvider */
        $timeProvider = ServiceRegister::getService(TimeProvider::CLASS_NAME);

        return $timeProvider->getCurrentLocalTime();
    }

    /**
     * Returns repository instance
     *
     * @return ScheduleRepositoryInterface
     * @throws RepositoryNotRegisteredException
     */
    private function getScheduleRepository()
    {
        if ($this->repository === null) {
            /** @var ScheduleRepositoryInterface $repository */
            $this->repository = RepositoryRegistry::getRepository(Schedule::getClassName());
        }

        return $this->repository;
    }

    private function isAlreadyEnqueued($taskType, $context)
    {
        $result = false;

        $lastTask = $this->getQueueService()->findLatestByType($taskType, $context);
        if ($lastTask && in_array($lastTask->getStatus(), array(QueueItem::QUEUED, QueueItem::IN_PROGRESS), true)) {
            $result = true;
        }

        return $result;
    }

    /**
     * Retrieves queue service instance.
     *
     * @return QueueService | object Queue Service instance.
     */
    private function getQueueService()
    {
        return ServiceRegister::getService(QueueService::CLASS_NAME);
    }
}
