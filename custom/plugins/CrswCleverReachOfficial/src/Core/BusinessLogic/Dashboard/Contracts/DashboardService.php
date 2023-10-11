<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Dashboard\Contracts;

interface DashboardService
{
    const CLASS_NAME = __CLASS__;

    /**
     * Checks whether sync statics have been displayed.
     *
     * @return bool
     */
    public function isSyncStatisticsDisplayed();

    /**
     * Sets sync statistics displayed status.
     *
     * @param bool $status
     *
     * @return void
     */
    public function setSyncStatisticsDisplayed($status);

    /**
     * Retrieves the count of synced receivers during the initial sync.
     *
     * @return int
     */
    public function getSyncedReceiversCount();

    /**
     * Sets the number of synced receivers.
     *
     * @param int $count
     *
     * @return void
     */
    public function setSyncedReceiversCount($count);


    /**
     * Returns date of the last secondary sync task
     *
     * @return string|null
     */
    public function getLastSyncJobTime();
}