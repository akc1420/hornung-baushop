<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\MaxAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\DateHistogramAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\EntityAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\SumAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\AvgAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;

use Cbax\ModulAnalytics\Components\ConfigReaderHelper;
use Cbax\ModulAnalytics\Components\Base;

class SearchTrends
{
    private $config;
    private $base;
    private $searchRepository;

    public function __construct(
        $config,
        Base $base,
        EntityRepositoryInterface $searchRepository
    )
    {
        $this->config = $config;
        $this->base = $base;
        $this->searchRepository = $searchRepository;
    }

    //for later
    public function getSearchTrends($parameters, $context)
    {
        $parameters['blacklistedStatesIds'] = [];
        $criteria = $this->base->getBaseCriteria('createdAt', $parameters, false);

        return [];
    }
}
