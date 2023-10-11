<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Pipeline;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\AbandonedCartTrigger;

abstract class Pipeline
{
    /**
     * List of trigger filters.
     *
     * @var \Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Pipeline\Filter[]
     */
    protected static $filters = array();

    /**
     * Passes trigger through the filter.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\AbandonedCartTrigger $trigger
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Exceptions\FailedToPassFilterException
     */
    public static function execute(AbandonedCartTrigger $trigger)
    {
        foreach (static::getRegisteredFilters() as $filter) {
            $filter->pass($trigger);
        }
    }

    /**
     * Appends the filter to the list of filters.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Pipeline\Filter $filter
     */
    public static function append(Filter $filter)
    {
        static::$filters[] = $filter;
    }

    /**
     * Prepends the filter to the list of filters.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Pipeline\Filter $filter
     */
    public static function prepend(Filter $filter)
    {
        array_unshift(static::$filters, $filter);
    }

    /**
     * Retrieves currently registered filters.
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Pipeline\Filter[]
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