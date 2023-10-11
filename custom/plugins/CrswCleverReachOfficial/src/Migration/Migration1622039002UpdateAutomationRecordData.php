<?php declare(strict_types=1);

namespace Crsw\CleverReachOfficial\Migration;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Contracts\RecoveryEmailStatus;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Entities\AutomationRecord;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Scheduler\Models\DailySchedule;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Scheduler\Models\Schedule;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\QueryFilter\Operators;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\QueryFilter\QueryFilter;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\RepositoryRegistry;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1622039002UpdateAutomationRecordData extends MigrationStep
{
    public const ENTITY_TABLE = 'cleverreach_automation_entity';

    public function getCreationTimestamp(): int
    {
        return 1622039002;
    }

    public function update(Connection $connection): void
    {
        $recordRepository = RepositoryRegistry::getRepository(AutomationRecord::getClassName());
        $scheduleRepository = RepositoryRegistry::getRepository(Schedule::getClassName());
        $records = $recordRepository->select();
        /** @var AutomationRecord $record */
        foreach ($records as $record) {
            $scheduleFilter = (new QueryFilter())->where('id', Operators::EQUALS, $record->getScheduleId());
            /** @var DailySchedule $schedule */
            $schedule = $scheduleRepository->selectOne($scheduleFilter);
            if ($schedule) {
                $schedule->setNextSchedule();
                $record->setScheduledTime($schedule->getNextSchedule());
            }

            $record->setStatus(RecoveryEmailStatus::PENDING);
            $record->setIsRecovered(false);

            $recordRepository->update($record);
        }
    }


    public function updateDestructive(Connection $connection): void
    {
        // No need for update destructive
    }
}
