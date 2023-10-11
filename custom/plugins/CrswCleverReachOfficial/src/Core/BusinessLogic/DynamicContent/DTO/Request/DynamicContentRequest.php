<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\DTO\Request;

use Crsw\CleverReachOfficial\Core\Infrastructure\Data\DataTransferObject;

/**
 * Class DynamicContentRequest
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\DTO\Request
 */
class DynamicContentRequest extends DataTransferObject
{
    /**
     * @var string
     */
    protected $type;
    /**
     * @var string
     */
    protected $password;
    /**
     * @var string
     */
    protected $context;
    /**
     * @var SearchTerms
     */
    protected $searchTerms;

    /**
     * DynamicContentRequest constructor.
     *
     * @param string $type
     * @param string $password
     * @param null $context
     * @param SearchTerms $searchTerms
     */
    public function __construct($type, $password, $context = null, SearchTerms $searchTerms = null)
    {
        $this->type = $type;
        $this->password = $password;
        $this->searchTerms = $searchTerms;
        $this->context = $context;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @return SearchTerms|null
     */
    public function getSearchTerms()
    {
        return $this->searchTerms;
    }

    /**
     * @param SearchTerms|null $searchTerms
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
            'get' =>  $this->type,
            'password' =>  $this->password,
            'filters' => $this->searchTerms->toArray(),
        );
    }

    /**
     * @param array $data
     *
     * @return DynamicContentRequest
     */
    public static function fromArray(array $data)
    {
        return new static(static::getDataValue($data, 'get'), static::getDataValue($data, 'password'));
    }
}
