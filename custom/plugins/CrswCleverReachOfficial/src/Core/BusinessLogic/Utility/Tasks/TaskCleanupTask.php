<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Utility\Tasks;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Scheduler\ScheduledTask;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\QueryFilter\Operators;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\QueryFilter\QueryFilter;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\RepositoryRegistry;
use Crsw\CleverReachOfficial\Core\Infrastructure\Serializer\Serializer;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueItem;
use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\TimeProvider;

/**
 * Class TaskCleanupTask
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Utility\Tasks
 */
class TaskCleanupTask extends ScheduledTask
{
    /**
     * Limit in number of tasks that will be deleted. Since this task will be used in scheduler and run
     * every 5min it will be limited to fixed amount of tasks per run to avoid memory leaks.
     * Once we have batch delete this should be removed and redesigned!
     */
    const CLEANUP_LIMIT = 1000;

    /**
     * The class name of the task.
     *
     * @var string
     */
    private $taskType;
    /**
     * A list of task statuses.
     *
     * @var array
     */
    private $taskStatuses;
    /**
     * An age of the task in seconds.
     *
     * @var int
     */
    private $taskAge;
    /**
     * Current progress.
     *
     * @var float
     */
    private $progress;

    /**
     * TaskCleanupTask constructor.
     *
     * @param string $taskType The type of the task to delete.
     * @param array $taskStatuses The list of queue item statuses.
     * @param int $taskAge The min age of the task.
     */
    public function __construct($taskType, array $taskStatuses, $taskAge = 60)
    {
        $this->taskType = $taskType;
        $this->taskStatuses = $taskStatuses;
        $this->taskAge = $taskAge;
        $this->progress = 0.0;
    }

    /**
     * Defines whether schedulable task can be enqueued for execution if there is already instance with queued status.
     *
     * @return bool False indeicates that the schedulable task should not enqueued if there
     *      is already instance in queued status.
     */
    public function canHaveMultipleQueuedInstances()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public static function fromArray(array $array)
    {
        return new static(
            $array['task_type'],
            !empty($array['task_statuses']) ? $array['task_statuses'] : array(),
            $array['task_age']
        );
    }

    /**
     * @inheritdoc
     */
    public function serialize()
    {
        return Serializer::serialize($this->toArray());
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return array(
            'task_type' => $this->taskType,
            'task_statuses' => $this->taskStatuses,
            'task_age' => $this->taskAge,
        );
    }

    /**
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        list($this->taskType, $this->taskStatuses, $this->taskAge) = array_values(Serializer::unserialize($serialized));
    }

    /**
     * Runs task logic.
     *
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function execute()
    {
        // cleanup requested tasks
        $this->cleanupTasks($this->taskType, $this->taskStatuses, $this->taskAge, 90);

        // self cleanup
        $this->cleanupTasks(static::getClassName(), array(QueueItem::COMPLETED), 3600, 10);

        $this->reportProgress(100);
    }

    /**
     * Cleans up the tasks with the specified parameters.
     *
     * @param string $taskType The type of the task to delete.
     * @param array $taskStatuses The list of queue item statuses.
     * @param string $taskAge The min age of the task.
     * @param int $progressPart Progress report part of the overall task.
     *
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    private function cleanupTasks($taskType, array $taskStatuses, $taskAge, $progressPart)
    {
        /** @var TimeProvider $timeProvider */
        $timeProvider = ServiceRegister::getService(TimeProvider::CLASS_NAME);
        $time = $timeProvider->getCurrentLocalTime()->getTimestamp();
        $filter = new QueryFilter();
        /** @noinspection PhpUnhandledExceptionInspection */
        $filter->where('taskType', Operators::EQUALS, $taskType)
            ->where('status', Operators::IN, $taskStatuses)
            ->where('lastUpdateTimestamp', Operators::LESS_OR_EQUAL_THAN, $time - $taskAge)
            ->setLimit(static::CLEANUP_LIMIT);

        $repository = RepositoryRegistry::getQueueItemRepository();
        $queueItems = $repository->select($filter);
        $totalItems = count($queueItems);
        if ($totalItems > 0) {
            $progressStep = $progressPart / $totalItems;

            foreach ($queueItems as $item) {
                $repository->delete($item);
                $this->progress += $progressStep;
                $this->reportProgress($this->progress);
            }
        }
    }
}