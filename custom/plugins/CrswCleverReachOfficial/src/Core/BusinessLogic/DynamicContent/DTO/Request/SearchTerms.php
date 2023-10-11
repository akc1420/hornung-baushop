<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\DTO\Request;

use Crsw\CleverReachOfficial\Core\Infrastructure\Data\DataTransferObject;

/**
 * Class SearchTerms
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\DTO\Request
 */
class SearchTerms extends DataTransferObject
{
    /**
     * @var array
     */
    protected $searchTerms = array();

    public function add($key, $value)
    {
        $this->searchTerms[$key] = $value;
    }

    /**
     * Returns value by its key
     *
     * @param string $key
     *
     * @return mixed|string
     */
    public function getValue($key)
    {
        return static::getDataValue($this->searchTerms, $key, null);
    }

    /**
     * @return array
     */
    public function getSearchTerms()
    {
        return $this->searchTerms;
    }

    /**
     * @param array $searchTerms
     */
    public function setSearchTerms($searchTerms)
    {
        $this->searchTerms = $searchTerms;
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return array(
            'searchTerms' => $this->searchTerms,
        );
    }
}