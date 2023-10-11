<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

//use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\DateHistogramAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\EntityAggregation;

use Cbax\ModulAnalytics\Components\Base;

class SearchActivity
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

    public function getSearchActivity($parameters, $context)
    {
        $parameters['blacklistedStatesIds'] = [];
        $criteria = $this->base->getBaseCriteria('createdAt', $parameters, false);

        $criteria->addAggregation(
            new EntityAggregation('salesChannels', 'salesChannelId', 'sales_channel')
        );

        $criteria->addAggregation(
            new DateHistogramAggregation(
                'search-sum-day',
                'createdAt',
                DateHistogramAggregation::PER_DAY
            )
        );

        $result = $this->searchRepository->search($criteria, $context);
        $aggregation = $result->getAggregations()->get('search-sum-day');

        $data = [];
        foreach ($aggregation->getBuckets() as $bucket)
        {
            $data[] = [
                'date' => explode(' ', $bucket->getKey())[0],
                'formatedDate' => $this->base->getFormatedDate($bucket->getKey(), $parameters['adminLocalLanguage']),
                'count' => (int)round((float)$bucket->getCount(),0)
            ];
        }

        if ($parameters['format'] === 'csv') {
            return $this->base->exportCSV($data, $parameters['labels']);
        }

        return $data;
    }
}
