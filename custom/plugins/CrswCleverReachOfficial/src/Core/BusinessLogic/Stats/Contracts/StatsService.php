<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Stats\Contracts;

/**
 * Interface StatsService
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Stats\Contracts
 */
interface StatsService
{
    const CLASS_NAME = __CLASS__;
    /**
     * Returns current number of subscribed recipients
     *
     * @return int
     */
    public function getSubscribed();

    /**
     * Returns current number of unsubscribed recipients
     *
     * @return int
     */
    public function getUnsubscribed();
}