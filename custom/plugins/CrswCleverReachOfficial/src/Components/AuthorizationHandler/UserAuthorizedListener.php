<?php

namespace Crsw\CleverReachOfficial\Components\AuthorizationHandler;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Tasks\Composite\Components\UpdateUserInfoTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Scheduler\Models\DailySchedule;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Scheduler\Models\HourlySchedule;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Scheduler\Models\MinuteSchedule;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Scheduler\Models\MonthlySchedule;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Scheduler\Models\Schedule;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Stats\Task\SaveReceiverStatsTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Utility\Tasks\TaskCleanupTask;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Interfaces\RepositoryInterface;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\RepositoryRegistry;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueItem;

/**
 * Class UserAuthorizedListener
 *
 * @package Crsw\CleverReachOfficial\Components\AuthorizationHandler
 */
class UserAuthorizedListener
{
    /**
     * Handles UserAuthorizedEvent.
     *
     * @param UserAuthorizedEvent $event
     * @throws RepositoryNotRegisteredException
     */
    public static function handle(UserAuthorizedEvent $event): void
    {
        if (!$event->isReAuth()) {
            self::registerSchedules();
        }
    }

    /**
     * @throws RepositoryNotRegisteredException
     */
    protected static function registerSchedules(): void
    {
        self::registerUpdateUserSchedule();
        self::registerTaskCleanupSchedule();
        self::registerSaveReceiverStatsSchedule();
    }

    /**
     * @throws RepositoryNotRegisteredException
     */
    protected static function registerTaskCleanupSchedule(): void
    {
        self::registerTaskCleanup('ScheduleCheckTask', 'minute');
        self::registerTaskCleanup('ReceiverSyncTask', 'daily');
        self::registerTaskCleanup('CreateSegmentsTask', 'daily');
        self::registerTaskCleanup('DeactivateReceiverTask', 'daily');
        self::registerTaskCleanup('SubscribeReceiverTask', 'daily');
        self::registerTaskCleanup('UnsubscribeReceiverTask', 'daily');
        self::registerTaskCleanup('OrderSyncTask', 'hourly');
        self::registerTaskCleanup('ConnectTask', 'daily');
        self::registerTaskCleanup('UpdateSyncSettingsTask', 'monthly');
    }

    /**
     * @throws RepositoryNotRegisteredException
     */
    protected static function registerUpdateUserSchedule(): void
    {
        $task = new UpdateUserInfoTask();
        $queueName = static::getConfigService()->getDefaultQueueName();
        $schedule = new DailySchedule($task, $queueName);
        $schedule->setHour(2);
        $schedule->setMinute(15);
        $schedule->setRecurring(true);
        $schedule->setNextSchedule();

        self::getScheduleRepo()->save($schedule);
    }

    /**
     * @throws RepositoryNotRegisteredException
     */
    protected static function registerSaveReceiverStatsSchedule(): void
    {
        $task = new SaveReceiverStatsTask();
        $queueName = static::getConfigService()->getDefaultQueueName();
        $schedule = new DailySchedule($task, $queueName);
        $schedule->setNextSchedule();

        self::getScheduleRepo()->save($schedule);
    }

    /**
     * @param string $taskType
     * @param string $scheduleType
     *
     * @throws RepositoryNotRegisteredException
     */
    protected static function registerTaskCleanup(string $taskType, string $scheduleType): void
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

        self::getScheduleRepo()->save($schedule);
    }

    /**
     * @return Configuration | object
     */
    protected static function getConfigService()
    {
        return ServiceRegister::getService(Configuration::CLASS_NAME);
    }

    /**
     * @return RepositoryInterface
     *
     * @throws RepositoryNotRegisteredException
     */
    protected static function getScheduleRepo(): RepositoryInterface
    {
        return RepositoryRegistry::getRepository(Schedule::getClassName());
    }
}
