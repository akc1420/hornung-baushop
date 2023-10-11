<?php

namespace Crsw\CleverReachOfficial\Entity\Schedule\Repositories;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Scheduler\Exceptions\ScheduleSaveException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Scheduler\Interfaces\ScheduleRepositoryInterface;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Scheduler\Models\Schedule;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\QueryFilter\Operators;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\QueryFilter\QueryFilter;
use Crsw\CleverReachOfficial\Entity\Base\Repositories\BaseRepository;
use Exception;
use JsonException;

/**
 * Class ScheduleRepository
 *
 * @package Crsw\CleverReachOfficial\Entity\Schedule\Repositories
 */
class ScheduleRepository extends BaseRepository implements ScheduleRepositoryInterface
{
    /**
     * Creates or updates given schedule. If schedule id is not set, new schedule will be created otherwise
     * update will be performed.
     *
     * @param Schedule $schedule Schedule to save
     * @param array $additionalWhere List of key/value pairs that must be satisfied upon saving schedule. Key is
     *  schedule property and value is condition value for that property. Example for MySql storage:
     *  $storage->save($schedule, array('lastUpdateTimestamp' => 123456798)) should produce query
     *  UPDATE schedule_storage_table SET .... WHERE .... AND lastUpdateTimestamp = 123456798
     *
     * @return int Id of saved queue item
     * @throws ScheduleSaveException if schedule could not be saved
     */
    public function saveWithCondition(Schedule $schedule, array $additionalWhere = array()): int
    {
        try {
            $scheduleId = $schedule->getId();

            if ($scheduleId === null || $scheduleId <= 0) {
                return $this->save($schedule);
            }

            $this->updateSchedule($schedule, $additionalWhere);

            return $scheduleId;
        } catch (Exception $e) {
            throw new ScheduleSaveException('Failed to save schedule. Error: ' . $e->getMessage());
        }
    }

    /**
     * @param Schedule $schedule
     * @param array $additionalWhere
     *
     * @throws ScheduleSaveException
     * @throws QueryFilterInvalidParamException|JsonException
     */
    protected function updateSchedule(Schedule $schedule, array $additionalWhere): void
    {
        $filter = new QueryFilter();
        $filter->where('id', Operators::EQUALS, $schedule->getId());

        foreach ($additionalWhere as $name => $value) {
            if ($value === null) {
                $filter->where($name, Operators::NULL);
            } else {
                $filter->where($name, Operators::EQUALS, $value ?: '');
            }
        }

        $item = $this->selectOne($filter);

        if ($item === null) {
            throw new ScheduleSaveException('Cannot update schedule with id ' . $schedule->getId() . '.');
        }

        $this->update($schedule);
    }
}