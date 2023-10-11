<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\DTO\Response\Filter;

use Crsw\CleverReachOfficial\Core\Infrastructure\Data\DataTransferObject;

/**
 * Class FilterCollection
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\DTO
 */
class FilterCollection extends DataTransferObject
{
    /**
     * @var Filter[]
     */
    protected $filters = array();

    /**
     * @return Filter[]
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * @param Filter[] $filters
     */
    public function setFilters($filters)
    {
        $this->filters = $filters;
    }

    /**
     * @param Filter $filter
     */
    public function addFilter(Filter $filter)
    {
        $this->filters[] = $filter;
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        $data = array();
        foreach ($this->filters as $filter) {
            $data[] = $filter->toArray();
        }

        return $data;
    }
}
