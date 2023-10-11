<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\DateHistogramAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;

use Cbax\ModulAnalytics\Components\Base;

class CustomerAge
{
    private $config;
    private $base;
    private $customerRepository;

    public function __construct(
        $config,
        Base $base,
        EntityRepositoryInterface $customerRepository
    )
    {
        $this->config = $config;
        $this->base = $base;
        $this->customerRepository = $customerRepository;
    }

    public function getCustomerAge($parameters, Context $context)
    {
        $criteria = new Criteria();
        $criteria->setLimit(1);

        $criteria->addFilter(new EqualsFilter('active', 1));

        if (!empty($parameters['customerGroupIds']))
        {
            $criteria->addFilter(new EqualsAnyFilter('groupId', $parameters['customerGroupIds']));
        }

        $criteria->addFilter(
            new NotFilter(
                NotFilter::CONNECTION_OR,
                [
                    new EqualsFilter('birthday', NULL),
                    new EqualsFilter('birthday', '0000-00-00'),
                ]
            )
        );

        if (!empty($parameters['salesChannelIds']))
        {
            $criteria->addFilter(new EqualsAnyFilter('salesChannelId', $parameters['salesChannelIds']));
        }

        $criteria->addAggregation(
            new DateHistogramAggregation(
                'customer_age',
                'birthday',
                DateHistogramAggregation::PER_YEAR
            )
        );

        try {
            $result = $this->customerRepository->search($criteria, $context);
            $aggregation = $result->getAggregations()->get('customer_age');

        } catch (\Exception $e) {
            return [];
        }

        $data = [];
        $seriesdata = [];

        foreach ($aggregation->getBuckets() as $bucket) {
            $key = explode(' ', $bucket->getKey())[0];
            $year = \DateTime::createFromFormat('Y-m-d', $key)->format('Y');
            $data[$year] = 0;
        }

        foreach ($aggregation->getBuckets() as $bucket) {
            $key = explode(' ', $bucket->getKey())[0];
            $year = \DateTime::createFromFormat('Y-m-d', $key)->format('Y');
            $sum = $bucket->getCount();
            $data[$year] += $sum;
        }

        $total = array_sum($data);
        $thisYear = date("Y");

        foreach ($data as $key => $value) {
            $seriesdata[] = [
                'age' => (string)($thisYear - $key),
                'percent' => round(100 * $value/$total, 1) . ' %',
                'count' => (int)$value
            ];
        }

        $seriesdata = array_reverse($seriesdata);

        if ($parameters['format'] === 'csv') {
            return $this->base->exportCSV($seriesdata, $parameters['labels']);
        }

        return $seriesdata;
    }
}



