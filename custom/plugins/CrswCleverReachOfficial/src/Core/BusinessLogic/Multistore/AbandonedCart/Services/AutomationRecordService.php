<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Services;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Contracts\RecoveryEmailStatus;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Entities\AutomationRecord;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Exceptions\FailedToCreateAutomationRecordException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Exceptions\FailedToDeleteAutomationRecordException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Exceptions\FailedToTriggerAutomationRecordException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Exceptions\FailedToUpdateAutomationRecordException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Interfaces\AutomationRecordService as BaseService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Tasks\Trigger\TriggerCartAutomationTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\ORM\Interfaces\ConditionallyDeletes;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Scheduler\Models\DailySchedule;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Scheduler\Models\Schedule;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\ConfigurationManager;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Interfaces\RepositoryInterface;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\QueryFilter\Operators;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\QueryFilter\QueryFilter;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\RepositoryRegistry;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueService;
use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\TimeProvider;

class AutomationRecordService implements BaseService
{
    /**
     * Creates an instance of a record.
     *
     * @param AutomationRecord $record
     *
     * @return AutomationRecord
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Exceptions\FailedToCreateAutomationRecordException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function create(AutomationRecord $record)
    {
        $automation = $this->getCartService()->find($record->getAutomationId());
        if ($automation === null) {
            throw new FailedToCreateAutomationRecordException('Automation does not exist.');
        }

        if (!$automation->isActive()) {
            throw new FailedToCreateAutomationRecordException('Automation is not active.');
        }

        $records = $this->findBy(array(
            'automationId' => $record->getAutomationId(),
            'email' => $record->getEmail(),
            'status' => RecoveryEmailStatus::PENDING,
        ));

        if (!empty($records)) {
            throw new FailedToCreateAutomationRecordException('Record already exists for receiver.');
        }

        $records = $this->findBy(array('automationId' => $record->getAutomationId(), 'cartId' => $record->getCartId()));
        if (!empty($records)) {
            throw new FailedToCreateAutomationRecordException('Record already exists for cart.');
        }

        $record->setStatus(RecoveryEmailStatus::PENDING);
        $record->setIsRecovered(false);

        // We have to save record first in order to get access to its ID.
        $this->getRepository()->save($record);

        $settings = $automation->getSettings();
        $scheduleId = $this->scheduleTrigger($record, $settings['delay']);
        $record->setScheduleId($scheduleId);
        $this->getRepository()->update($record);

        return $record;
    }

    /**
     * Updates Record.
     *
     * @param AutomationRecord $record
     *
     * @return AutomationRecord
     *
     * @throws FailedToUpdateAutomationRecordException
     */
    public function update(AutomationRecord $record)
    {
        try {
            $this->getRepository()->update($record);
        } catch (\Exception $e) {
            throw new FailedToUpdateAutomationRecordException($e->getMessage(), $e->getCode(), $e);
        }

        return $record;
    }

    /**
     * Refreshes schedule time.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Entities\AutomationRecord $record
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Exceptions\FailedToUpdateAutomationRecordException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function refreshScheduleTime(AutomationRecord $record)
    {
        $automation = $this->getCartService()->find($record->getAutomationId());
        if ($automation === null) {
            throw new FailedToUpdateAutomationRecordException('Automation does not exist.');
        }

        try {
            /** @var Schedule $schedule */
            if ($schedule = $this->getSchedule($record->getScheduleId())) {
                $settings = $automation->getSettings();
                $this->setScheduleTime($record, $schedule, $settings['delay']);
                $this->getScheduleRepository()->update($schedule);
            }
        } catch (\Exception $e) {
            throw new FailedToUpdateAutomationRecordException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Provides Record identified by id.
     *
     * @param int $id
     *
     * @return AutomationRecord | null
     *
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function find($id)
    {
        $query = new QueryFilter();
        $query->where('id', Operators::EQUALS, $id);

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->getRepository()->selectOne($query);
    }

    /**
     * Provides Records identified by query.
     *
     * @param array $query
     *
     * @return AutomationRecord[]
     *
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function findBy(array $query)
    {
        $queryFilter = new QueryFilter();

        foreach ($query as $column => $value) {
            if ($value === null) {
                $queryFilter->where($column, Operators::NULL);
            } else {
                $queryFilter->where($column, Operators::EQUALS, $value);
            }
        }

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->getRepository()->select($queryFilter);
    }

    /**
     * Provides AutomationRecords by provided criteria
     *
     * @param \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\QueryFilter\QueryFilter $filter
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Entities\AutomationRecord[]
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function filter(QueryFilter $filter)
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->getRepository()->select($filter);
    }

    /**
     * Deletes Record identified by id.
     *
     * @param int $id
     *
     * @return void
     *
     * @throws FailedToDeleteAutomationRecordException
     */
    public function delete($id)
    {
        try {
            if ($record = $this->find($id)) {
                $this->deleteSchedule($record->getScheduleId());
                $this->getRepository()->delete($record);
            }
        } catch (\Exception $e) {
            throw new FailedToDeleteAutomationRecordException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function deleteBy(array $query)
    {
        try {
            $records = $this->findBy($query);
            $repository = $this->getRepository();
            foreach ($records as $record) {
                $this->deleteSchedule($record->getScheduleId());
                $repository->delete($record);
            }
        } catch (\Exception $e) {
            throw new FailedToDeleteAutomationRecordException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param string $recordId
     *
     * @return void
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Exceptions\FailedToTriggerAutomationRecordException
     */
    public function triggerRecord($recordId)
    {
        $record = $this->find($recordId);
        if (!$record) {
            throw new FailedToTriggerAutomationRecordException("Automation record not found for id: $recordId");
        }

        $queueName = $this->getConfigService()->getDefaultQueueName();
        $context = $this->getConfigManager()->getContext();

        /** @var QueueService $queueService */
        $queueService = ServiceRegister::getService(QueueService::CLASS_NAME);
        $queueService->enqueue($queueName, new TriggerCartAutomationTask($recordId), $context);

        $this->deleteSchedule($record->getScheduleId());
    }

    /**
     * Provides automation record repository.
     *
     * @return \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Interfaces\RepositoryInterface
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function getRepository()
    {
        return RepositoryRegistry::getRepository(AutomationRecord::getClassName());
    }

    /**
     * Provides cart automation service.
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Services\CartAutomationService | object
     */
    protected function getCartService()
    {
        return ServiceRegister::getService(CartAutomationService::CLASS_NAME);
    }

    /**
     * Schedules trigger for automation record.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Entities\AutomationRecord $record
     * @param int $delay
     *
     * @return int
     */
    protected function scheduleTrigger(AutomationRecord $record, $delay)
    {
        $queueName = $this->getConfigService()->getDefaultQueueName();
        $context = $this->getConfigManager()->getContext();

        $schedule = new DailySchedule(new TriggerCartAutomationTask($record->getId()), $queueName, $context);
        $this->setScheduleTime($record, $schedule, $delay);

        return $this->getScheduleRepository()->save($schedule);
    }

    /**
     * Sets schedule time.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Entities\AutomationRecord $record
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Scheduler\Models\Schedule $schedule
     * @param int $delay
     */
    protected function setScheduleTime(AutomationRecord $record, Schedule $schedule, $delay)
    {
        $currentTime = $this->getTimeProvider()->getCurrentLocalTime();
        $targetTime = $currentTime->modify("+{$delay} hour");
        $record->setScheduledTime($targetTime);
        $schedule->setHour((int)$targetTime->format('G'));
        $schedule->setMinute((int)$targetTime->format('i'));
        $schedule->setRecurring(false);
        $schedule->setNextSchedule();
    }

    /**
     * Deletes schedule.
     *
     * @param $scheduleId
     *
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\EntityClassException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    protected function deleteSchedule($scheduleId)
    {
        if (!$scheduleId) {
            return;
        }

        if ($schedule = $this->getSchedule($scheduleId)) {
            $this->getScheduleRepository()->delete($schedule);
        }
    }

    /**
     * Retrieves time provider.
     *
     * @return TimeProvider | object
     */
    private function getTimeProvider()
    {
        return ServiceRegister::getService(TimeProvider::CLASS_NAME);
    }

    /**
     * Retrieves schedule repository.
     *
     * @return RepositoryInterface | ConditionallyDeletes
     *
     * @noinspection PhpDocMissingThrowsInspection
     */
    private function getScheduleRepository()
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        return RepositoryRegistry::getRepository(Schedule::getClassName());
    }

    /**
     * Retrieves config service.
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Configuration\Configuration | object
     */
    private function getConfigService()
    {
        return ServiceRegister::getService(Configuration::CLASS_NAME);
    }

    /**
     * Retrieves configuration manager.
     *
     * @return ConfigurationManager | object
     */
    private function getConfigManager()
    {
        return ServiceRegister::getService(ConfigurationManager::CLASS_NAME);
    }

    /**
     * Provides schedule identified by id.
     *
     * @param int $scheduleId
     *
     * @return \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Entity|null
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\EntityClassException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    private function getSchedule($scheduleId)
    {
        $filter = new QueryFilter();
        $filter->where('id', Operators::EQUALS, $scheduleId);
        $repository = $this->getScheduleRepository();

        return $repository->selectOne($filter);
    }
}