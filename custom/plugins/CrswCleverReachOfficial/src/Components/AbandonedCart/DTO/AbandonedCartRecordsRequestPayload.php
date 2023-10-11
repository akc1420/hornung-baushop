<?php

namespace Crsw\CleverReachOfficial\Components\AbandonedCart\DTO;

use Crsw\CleverReachOfficial\Core\Infrastructure\Data\DataTransferObject;

/**
 * Class AbandonedCartRecordsFilterParameters
 *
 * @package Crsw\CleverReachOfficial\Components\AbandonedCart\DTO
 */
class AbandonedCartRecordsRequestPayload extends DataTransferObject
{
    /**
     * @var int
     */
    private $limit;
    /**
     * @var int
     */
    private $page;
    /**
     * @var string
     */
    private $term;
    /**
     * @var string
     */
    private $sortBy;
    /**
     * @var string
     */
    private $sortDirection;
    /**
     * @var array
     */
    private $filters;

    /**
     * AbandonedCartRecordsFilterParameters constructor.
     *
     * @param int $limit
     * @param int $page
     * @param string $term
     * @param string $sortBy
     * @param string $sortDirection
     * @param array $filters
     */
    public function __construct(
        int $limit,
        int $page,
        string $term,
        string $sortBy,
        string $sortDirection,
        array $filters
    ) {
        $this->limit = $limit;
        $this->page = $page;
        $this->term = $term;
        $this->sortBy = $sortBy;
        $this->sortDirection = $sortDirection;
        $this->filters = $filters;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'limit' => $this->limit,
            'page' => $this->page,
            'term' => $this->term,
            'sortBy' => $this->sortBy,
            'sortDirection' => $this->sortDirection,
            'filters' => $this->filters,
        ];
    }

    /**
     * @param array $data
     *
     * @return AbandonedCartRecordsRequestPayload
     */
    public static function fromArray(array $data): AbandonedCartRecordsRequestPayload
    {
        return new static(
            static::getDataValue($data, 'limit', 25),
            static::getDataValue($data, 'page', 1),
            static::getDataValue($data, 'term'),
            static::getDataValue($data, 'sortBy'),
            static::getDataValue($data, 'sortDirection', 'DESC'),
            static::getDataValue($data, 'filters', [])
        );
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @return int
     */
    public function getPage(): int
    {
        return $this->page;
    }

    /**
     * @return string
     */
    public function getTerm(): string
    {
        return $this->term;
    }

    /**
     * @return string
     */
    public function getSortBy(): string
    {
        return $this->sortBy;
    }

    /**
     * @return string
     */
    public function getSortDirection(): string
    {
        return $this->sortDirection;
    }

    /**
     * @return array
     */
    public function getFilters(): array
    {
        return $this->filters;
    }
}
