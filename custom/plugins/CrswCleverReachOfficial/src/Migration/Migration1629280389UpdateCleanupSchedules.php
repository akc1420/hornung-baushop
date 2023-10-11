<?php

namespace Crsw\CleverReachOfficial\Migration;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Contracts\AuthorizationService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Scheduler\Models\DailySchedule;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Scheduler\Models\HourlySchedule;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Scheduler\Models\MinuteSchedule;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Scheduler\Models\MonthlySchedule;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Scheduler\Models\Schedule;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Utility\Tasks\TaskCleanupTask;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Interfaces\RepositoryInterface;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\RepositoryRegistry;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueItem;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1629280389UpdateCleanupSchedules extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1629280389;
    }

    public function update(Connection $connection): void
    {
        if ($this->isAuthorized()) {
            $this->registerTaskCleanupSchedule();
        }

    }

    public function updateDestructive(Connection $connection): void
    {
        // No need for update destructive
    }

    /**
     * @return bool
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     */
    private function isAuthorized(): bool
    {
        /** @var AuthorizationService $authService */
        $authService = ServiceRegister::getService(AuthorizationService::CLASS_NAME);
        try {
            return $authService->getAuthInfo() !== null;
        } catch (FailedToRetrieveAuthInfoException $exception) {
            return false;
        }
    }

    /**
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    private function registerTaskCleanupSchedule(): void
    {
        $this->registerTaskCleanup('ScheduleCheckTask', 'minute');
        $this->registerTaskCleanup('ReceiverSyncTask', 'daily');
        $this->registerTaskCleanup('CreateSegmentsTask', 'daily');
        $this->registerTaskCleanup('DeactivateReceiverTask', 'daily');
        $this->registerTaskCleanup('SubscribeReceiverTask', 'daily');
        $this->registerTaskCleanup('UnsubscribeReceiverTask', 'daily');
        $this->registerTaskCleanup('OrderSyncTask', 'hourly');
        $this->registerTaskCleanup('ConnectTask', 'daily');
        $this->registerTaskCleanup('UpdateSyncSettingsTask', 'monthly');
    }

    /**
     * @param string $taskType
     * @param string $scheduleType
     *
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    private function registerTaskCleanup(string $taskType, string $scheduleType): void
    {
        $task = new TaskCleanupTask($taskType, [QueueItem::COMPLETED]);
        $queueName = 'system-cleanup';

        $schedule = '';

        switch ($scheduleType) {
            case 'minute':
                $schedule = new MinuteSchedule($task, $queueName);
                $schedule->setInterval(5);
                $schedule->setRecurring(true);
                $schedule->setNextSchedule();
                break;
            case 'hourly':
                $schedule = new HourlySchedule($task, $queueName);
                $schedule->setRecurring(true);
                $schedule->setNextSchedule();
                break;
            case 'daily':
                $schedule = new DailySchedule($task, $queueName);
                $schedule->setRecurring(true);
                $schedule->setNextSchedule();
                break;
            case 'monthly':
                $schedule = new MonthlySchedule($task, $queueName);
                $schedule->setRecurring(true);
                $schedule->setNextSchedule();
                break;
        }

        if (!$schedule) {
            return;
        }

        $this->getScheduleRepo()->save($schedule);
    }

    /**
     * @return RepositoryInterface
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    private function getScheduleRepo(): RepositoryInterface
    {
        return RepositoryRegistry::getRepository(Schedule::getClassName());
    }
}
