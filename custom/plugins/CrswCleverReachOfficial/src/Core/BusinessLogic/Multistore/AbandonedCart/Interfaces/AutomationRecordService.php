<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Interfaces;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Entities\AutomationRecord;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Exceptions\FailedToCreateAutomationRecordException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Exceptions\FailedToDeleteAutomationRecordException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Exceptions\FailedToUpdateAutomationRecordException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\QueryFilter\QueryFilter;

/**
 * Interface AutomationRecordService
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Interfaces
 */
interface AutomationRecordService
{
    const CLASS_NAME = __CLASS__;

    /**
     * Creates an instance of a record.
     *
     * @param AutomationRecord $record
     *
     * @return AutomationRecord
     *
     * @throws FailedToCreateAutomationRecordException
     */
    public function create(AutomationRecord $record);

    /**
     * Updates Record.
     *
     * @param AutomationRecord $record
     *
     * @return AutomationRecord
     *
     * @throws FailedToUpdateAutomationRecordException
     */
    public function update(AutomationRecord $record);

    /**
     * Refreshes schedule time.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Entities\AutomationRecord $record
     *
     * @return void
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Exceptions\FailedToUpdateAutomationRecordException
     */
    public function refreshScheduleTime(AutomationRecord $record);

    /**
     * Provides Record identified by id.
     *
     * @param int $id
     *
     * @return AutomationRecord | null
     */
    public function find($id);

    /**
     * Provides Records identified by query.
     *
     * @param array $query
     *
     * @return AutomationRecord[]
     */
    public function findBy(array $query);

    /**
     * Provides AutomationRecords by provided criteria (condition, limit, offset, sorting)
     *
     * @param \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\QueryFilter\QueryFilter $filter
     *
     * @return AutomationRecord[]
     */
    public function filter(QueryFilter $filter);

    /**
     * Deletes Record identified by id.
     *
     * @param int $id
     *
     * @return void
     *
     * @throws FailedToDeleteAutomationRecordException
     */
    public function delete($id);

    /**
     * Deletes Records identified by query.
     *
     * @param array $query
     *
     * @return void
     *
     * @throws FailedToDeleteAutomationRecordException
     */
    public function deleteBy(array $query);

    /**
     * @param string $id
     *
     * @return mixed
     */
    public function triggerRecord($id);
}
