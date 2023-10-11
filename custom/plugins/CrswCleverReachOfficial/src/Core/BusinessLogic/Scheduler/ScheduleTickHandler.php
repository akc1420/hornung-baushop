<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Scheduler;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Configuration\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueService;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueItem;

/**
 * Class ScheduleTickHandler
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Scheduler
 */
class ScheduleTickHandler
{
    const CLASS_NAME = __CLASS__;

    /**
     * Queues ScheduleCheckTask.
     */
    public function handle()
    {
        /** @var QueueService $queueService */
        $queueService = ServiceRegister::getService(QueueService::CLASS_NAME);
        /** @var Configuration $configService */
        $configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        $task = $queueService->findLatestByType('ScheduleCheckTask');
        $threshold = $configService->getSchedulerTimeThreshold();

        if ($task && in_array($task->getStatus(), array(QueueItem::QUEUED, QueueItem::IN_PROGRESS), true)) {
            return;
        }

        if ($task === null || $task->getQueueTimestamp() + $threshold < time()) {
            $task = new ScheduleCheckTask();
            try {
                $queueService->enqueue($configService->getSchedulerQueueName(), $task);
            } catch (QueueStorageUnavailableException $ex) {
                Logger::logError(
                    'Failed to enqueue task ' . $task->getType(),
                    'Core',
                    array('trace' => $ex->getTraceAsString())
                );
            }
        }
    }
}
