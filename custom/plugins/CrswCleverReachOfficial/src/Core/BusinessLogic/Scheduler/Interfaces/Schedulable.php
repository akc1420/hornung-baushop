<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Scheduler\Interfaces;

/**
 * Interface Schedulable
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Scheduler\Interfaces
 */
interface Schedulable
{
    /**
     * Defines whether schedulable task can be enqueued for execution if there is already instance with queued status.
     *
     * @return bool False indicates that the schedulable task should not enqueued if there
     *      is already instance in queued status.
     */
    public function canHaveMultipleQueuedInstances();
}