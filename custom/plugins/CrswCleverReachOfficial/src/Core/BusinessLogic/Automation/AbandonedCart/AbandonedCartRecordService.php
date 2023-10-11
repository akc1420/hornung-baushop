<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Contracts\AbandonedCartRecordService as BaseService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\AbandonedCartRecord;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\AbandonedCartSettings;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\AbandonedCartTrigger;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Exceptions\FailedToCreateAbandonedCartRecordException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Tasks\AbandonedCartTriggerTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\ORM\Interfaces\ConditionallyDeletes;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Http\Proxy;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Scheduler\Models\DailySchedule;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Scheduler\Models\Schedule;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\ConfigurationManager;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Interfaces\RepositoryInterface;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\QueryFilter\Operators;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\QueryFilter\QueryFilter;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\RepositoryRegistry;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\TimeProvider;

class AbandonedCartRecordService implements BaseService
{
    /**
     * Retrieves abandoned cart record.
     *
     * @param string $groupId
     * @param string $poolId
     *
     * @return AbandonedCartRecord|null
     *
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function get($groupId, $poolId)
    {
        $filter = new QueryFilter();
        $filter->where('groupId', Operators::EQUALS, $groupId);
        $filter->where('poolId', Operators::EQUALS, $poolId);
        $filter->where('context', Operators::EQUALS, $this->getConfigManager()->getContext());

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->getRepository()->selectOne($filter);
    }

    /**
     * Retrieves abandoned cart record.
     *
     * @param int $id
     *
     * @return AbandonedCartRecord|null
     *
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function getById($id)
    {
        $filter = new QueryFilter();
        $filter->where('id', Operators::EQUALS, $id);
        $filter->where('context', Operators::EQUALS, $this->getConfigManager()->getContext());

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->getRepository()->selectOne($filter);
    }

    /**
     * Retrieves abandoned cart record.
     *
     * @param string $email
     *
     * @return AbandonedCartRecord|null
     *
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function getByEmail($email)
    {
        $filter = new QueryFilter();
        $filter->where('email', Operators::EQUALS, $email);
        $filter->where('context', Operators::EQUALS, $this->getConfigManager()->getContext());

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->getRepository()->selectOne($filter);
    }

    /**
     * Retrieves abandoned cart record.
     *
     * @param string $cartId
     *
     * @return AbandonedCartRecord|null
     *
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function getByCartId($cartId)
    {
        $filter = new QueryFilter();
        $filter->where('cartId', Operators::EQUALS, $cartId);
        $filter->where('context', Operators::EQUALS, $this->getConfigManager()->getContext());

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->getRepository()->selectOne($filter);
    }

    /**
     * Creates abandoned cart record.
     *
     * @param AbandonedCartTrigger $trigger
     *
     * @return AbandonedCartRecord
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Exceptions\FailedToCreateAbandonedCartRecordException
     */
    public function create(AbandonedCartTrigger $trigger)
    {
        $record = new AbandonedCartRecord();
        $record->setContext($this->getConfigManager()->getContext());
        $record->setTrigger($trigger);
        $record->setPoolId($trigger->getPoolId());
        $record->setGroupId($trigger->getGroupId());
        $record->setCartId($trigger->getCartId());
        $record->setCustomerId($trigger->getCustomerId());

        if (($settings = $this->getAbandonedCartSettingsService()->get()) === null) {
            throw new FailedToCreateAbandonedCartRecordException("Settings not provided.");
        }

        try {
            $receiver = $this->getReceiverProxy()->getReceiver($trigger->getGroupId(), $trigger->getPoolId());
        } catch (\Exception $e) {
            throw new FailedToCreateAbandonedCartRecordException(
                "Receiver [{$trigger->getGroupId()}:{$trigger->getPoolId()}] not found.",
                $e->getCode(),
                $e
            );
        }

        $record->setEmail($receiver->getEmail());

        $this->getRepository()->save($record);

        // Scheduled task requires record id. Therefore we must save record, before the schedule is created.
        $scheduleId = $this->addSchedule($record, $settings);
        $record->setScheduleId($scheduleId);

        $this->getRepository()->update($record);

        return $record;
    }

    /**
     * Updates abandoned cart record.
     *
     * @param AbandonedCartRecord $record
     *
     * @return void
     */
    public function update(AbandonedCartRecord $record)
    {
        $this->getRepository()->update($record);
    }

    /**
     * Deletes abandoned cart record.
     *
     * @param string $groupId
     * @param string $poolId
     *
     * @return void
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function delete($groupId, $poolId)
    {
        $record = $this->get($groupId, $poolId);
        if ($record === null) {
            return;
        }

        $this->doDelete($record);
    }

    /**
     * Deletes record.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\AbandonedCartRecord $record
     *
     * @return void
     *
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function deleteRecord(AbandonedCartRecord $record)
    {
        $this->doDelete($record);
    }

    /**
     * Deletes all records with associated schedules.
     *
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    public function deleteAllRecords()
    {
        // delete all records
        $query = new QueryFilter();
        $query->where('context', Operators::EQUALS, $this->getConfigManager()->getContext());
        $this->getRepository()->deleteWhere();

        // delete all schedules for abandoned cart
        $query = new QueryFilter();
        $query->where('context', Operators::EQUALS, $this->getConfigManager()->getContext());
        $query->where('taskType', Operators::EQUALS, 'AbandonedCartTriggerTask');
        $this->getScheduleRepository()->deleteWhere($query);
    }

    /**
     * Creates abandoned cart trigger schedule.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\AbandonedCartRecord $record
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\AbandonedCartSettings $settings
     *
     * @return int
     */
    private function addSchedule(AbandonedCartRecord $record, AbandonedCartSettings $settings)
    {
        $currentTime = $this->getTimeProvider()->getCurrentLocalTime();
        $targetTime = $currentTime->modify("+{$settings->getDelay()} hour");

        $queueName = $this->getConfigService()->getDefaultQueueName();
        $context = $this->getConfigManager()->getContext();

        $schedule = new DailySchedule(new AbandonedCartTriggerTask($record->getId()), $queueName, $context);
        $schedule->setHour((int)$targetTime->format('G'));
        $schedule->setMinute((int)$targetTime->format('i'));
        $schedule->setRecurring(false);
        $schedule->setNextSchedule();

        return $this->getScheduleRepository()->save($schedule);
    }

    /**
     * Retrieves abandoned cart repository.
     *
     * @return RepositoryInterface | ConditionallyDeletes
     *
     * @noinspection PhpDocMissingThrowsInspection
     */
    private function getRepository()
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        return RepositoryRegistry::getRepository(AbandonedCartRecord::getClassName());
    }

    /**
     * Retrieves receiver proxy.
     *
     * @return Proxy | object
     */
    private function getReceiverProxy()
    {
        return ServiceRegister::getService(Proxy::CLASS_NAME);
    }

    /**
     * Retrieves abandoned cart settings service
     *
     * @return AbandonedCartSettingsService |  object
     */
    private function getAbandonedCartSettingsService()
    {
        return ServiceRegister::getService(AbandonedCartSettingsService::CLASS_NAME);
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
     * Deletes record.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\AbandonedCartRecord $record
     *
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    private function doDelete(AbandonedCartRecord $record)
    {
        $filter = new QueryFilter();
        $filter->where('id', Operators::EQUALS, $record->getScheduleId());
        if (($schedule = $this->getScheduleRepository()->selectOne($filter)) !== null) {
            $this->getScheduleRepository()->delete($schedule);
        }

        $this->getRepository()->delete($record);
    }
}