<?php


namespace Crsw\CleverReachOfficial\Components\EventHandlers;

use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueItem;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueService;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Task;

/**
 * Class BaseHandler
 *
 * @package Crsw\CleverReachOfficial\EventHandlers
 */
class BaseHandler
{
    /**
     * Checks if task can be handled.
     *
     * @return bool
     */
    public function canHandle(): bool
    {
        $initialSyncTask = $this->getQueueService()->findLatestByType('InitialSyncTask');

        return $initialSyncTask !== null && $initialSyncTask->getStatus() === QueueItem::COMPLETED;
    }

    /**
     * Enqueues task.
     *
     * @param Task $task
     */
    protected function enqueueTask(Task $task): void
    {
        try {
            $this->getQueueService()->enqueue($this->getConfigService()->getDefaultQueueName(), $task);
        } catch (QueueStorageUnavailableException $e) {
            Logger::logError($e->getMessage(), 'Integration');
        }
    }

    /**
     * @return Configuration
     */
    protected function getConfigService(): Configuration
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(Configuration::class);
    }

    /**
     * @return QueueService
     */
    protected function getQueueService(): QueueService
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(QueueService::class);
    }
}
