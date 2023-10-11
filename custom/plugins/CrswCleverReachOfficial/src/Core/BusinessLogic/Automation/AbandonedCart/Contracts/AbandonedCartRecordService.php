<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Contracts;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\AbandonedCartRecord;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\AbandonedCartTrigger;

interface AbandonedCartRecordService
{
    const CLASS_NAME = __CLASS__;

    /**
     * Retrieves abandoned cart record.
     *
     * @param string $groupId
     * @param string $poolId
     *
     * @return AbandonedCartRecord|null
     */
    public function get($groupId, $poolId);

    /**
     * Retrieves abandoned cart record.
     *
     * @param int $id
     *
     * @return AbandonedCartRecord|null
     */
    public function getById($id);

    /**
     * Retrieves abandoned cart record.
     *
     * @param string $email
     *
     * @return AbandonedCartRecord|null
     */
    public function getByEmail($email);

    /**
     * Retrieves abandoned cart record.
     *
     * @param string $cartId
     *
     * @return AbandonedCartRecord|null
     */
    public function getByCartId($cartId);

    /**
     * Creates abandoned cart record.
     *
     * @param AbandonedCartTrigger $trigger
     *
     * @return AbandonedCartRecord
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Exceptions\FailedToCreateAbandonedCartRecordException
     */
    public function create(AbandonedCartTrigger $trigger);

    /**
     * Updates abandoned cart record.
     *
     * @param AbandonedCartRecord $record
     *
     * @return void
     */
    public function update(AbandonedCartRecord $record);

    /**
     * Deletes abandoned cart record.
     *
     * @param string $groupId
     * @param string $poolId
     *
     * @return void
     */
    public function delete($groupId, $poolId);

    /**
     * Deletes record.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\AbandonedCartRecord $record
     *
     * @return void
     */
    public function deleteRecord(AbandonedCartRecord $record);

    /**
     * Deletes all created records with associated schedules.
     *
     * @return void
     */
    public function deleteAllRecords();
}