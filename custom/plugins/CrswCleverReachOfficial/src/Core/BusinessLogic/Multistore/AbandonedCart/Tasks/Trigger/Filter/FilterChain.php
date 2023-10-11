<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Tasks\Trigger\Filter;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\DTO\Trigger;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Entities\AutomationRecord;

/**
 * Class FilterChain
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Tasks\Trigger\Filter
 */
class FilterChain
{
    /**
     * List of trigger filters.
     *
     * @var Filter[]
     */
    protected static $filters = array();

    /**
     * Passes record and trigger through registered filters.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Entities\AutomationRecord $record
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\DTO\Trigger $trigger
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Exceptions\FailedToPassFilterException
     */
    public static function execute(AutomationRecord $record, Trigger $trigger)
    {
        foreach (static::getRegisteredFilters() as $filter) {
            $filter->pass($record, $trigger);
        }
    }

    /**
     * Appends the filter to the list of filters.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Tasks\Trigger\Filter\Filter $filter
     */
    public static function append(Filter $filter)
    {
        static::$filters[] = $filter;
    }

    /**
     * Prepends the filter to the list of filters.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Tasks\Trigger\Filter\Filter $filter
     */
    public static function prepend(Filter $filter)
    {
        array_unshift(static::$filters, $filter);
    }

    /**
     * Retrieves currently registered filters.
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Tasks\Trigger\Filter\Filter[]
     */
    public static function peek()
    {
        return static::$filters;
    }

    /**
     * Removes registered filters.
     */
    public static function reset()
    {
        static::$filters = array();
    }

    /**
     * Retrieves filters.
     *
     * @return array
     */
    protected static function getRegisteredFilters()
    {
        return static::$filters;
    }
}