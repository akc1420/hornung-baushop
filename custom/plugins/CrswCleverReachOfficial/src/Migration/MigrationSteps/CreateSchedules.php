<?php


namespace Crsw\CleverReachOfficial\Migration\MigrationSteps;


use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Tasks\Composite\Components\UpdateUserInfoTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Scheduler\Models\DailySchedule;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Scheduler\Models\MinuteSchedule;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Scheduler\Models\Schedule;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Stats\Task\SaveReceiverStatsTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Utility\Tasks\TaskCleanupTask;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Interfaces\RepositoryInterface;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\RepositoryRegistry;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueItem;
use Crsw\CleverReachOfficial\Migration\Exceptions\FailedToExecuteMigrationStepException;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Configuration\ConfigService;

/**
 * Class CreateSchedules
 *
 * @package Crsw\CleverReachOfficial\Migration\MigrationSteps
 */
class CreateSchedules extends Step
{
    /**
     * Create schedules.
     *
     * @throws FailedToExecuteMigrationStepException
     */
    public function execute(): void
    {
        try {
            $this->registerUpdateUserSchedule();
            $this->registerTaskCleanupSchedule();
            $this->registerSaveReceiverStatsSchedule();
        } catch (RepositoryNotRegisteredException $e) {
            throw new FailedToExecuteMigrationStepException(
                'Failed to execute CreateSchedules step because: ' . $e->getMessage()
            );
        }
    }

    /**
     * @throws RepositoryNotRegisteredException
     */
    private function registerUpdateUserSchedule(): void
    {
        $task = new UpdateUserInfoTask();
        $queueName = $this->getConfigService()->getDefaultQueueName();
        $schedule = new DailySchedule($task, $queueName);
        $schedule->setHour(2);
        $schedule->setMinute(15);
        $schedule->setRecurring(true);
        $schedule->setNextSchedule();

        $this->getScheduleRepo()->save($schedule);
    }

    /**
     * @throws RepositoryNotRegisteredException
     */
    private function registerTaskCleanupSchedule(): void
    {
        $task = new TaskCleanupTask('ScheduleCheckTask', [QueueItem::COMPLETED]);
        $queueName = $this->getConfigService()->getDefaultQueueName();
        $schedule = new MinuteSchedule($task, $queueName);
        $schedule->setInterval(5);
        $schedule->setRecurring(true);
        $schedule->setNextSchedule();

        $this->getScheduleRepo()->save($schedule);
    }

    /**
     * @throws RepositoryNotRegisteredException
     */
    private function registerSaveReceiverStatsSchedule(): void
    {
        $task = new SaveReceiverStatsTask();
        $queueName = $this->getConfigService()->getDefaultQueueName();
        $schedule = new DailySchedule($task, $queueName);
        $schedule->setNextSchedule();

        $this->getScheduleRepo()->save($schedule);
    }

    /**
     * @return ConfigService | object
     */
    protected function getConfigService()
    {
        return ServiceRegister::getService(Configuration::class);
    }

    /**
     * @return RepositoryInterface
     *
     * @throws RepositoryNotRegisteredException
     */
    protected function getScheduleRepo(): RepositoryInterface
    {
        return RepositoryRegistry::getRepository(Schedule::getClassName());
    }
}