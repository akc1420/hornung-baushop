<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

//use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\DateHistogramAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\SumAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\FilterAggregation;

use Cbax\ModulAnalytics\Components\Base;

class SalesAll
{
    private $config;
    private $base;
    private $orderRepository;

    public function __construct(
        $config,
        Base $base,
        EntityRepositoryInterface $orderRepository
    )
    {
        $this->config = $config;
        $this->base = $base;
        $this->orderRepository = $orderRepository;
    }

    public function getSalesAll($parameters, $context)
    {
        $criteria = $this->base->getBaseCriteria('orderDate', $parameters);
        $filters = $this->base->getTransactionsFilters($parameters);

        if (!empty($this->config['grossOrNet']) && $this->config['grossOrNet'] == 'gross')
        {
            $criteria->addAggregation(
                new FilterAggregation(
                    'filter-order-sales-day',
                    new DateHistogramAggregation(
                        'order-sales-day',
                        'orderDate',
                        DateHistogramAggregation::PER_DAY,
                        null,
                        new TermsAggregation(
                            'sales-by-currency',
                            'currencyFactor',
                            null,
                            null,
                            new SumAggregation('sum-order', 'amountTotal')
                        )
                    ),
                    $filters
                )
            );

        } else {

            $criteria->addAggregation(
                new FilterAggregation(
                    'filter-order-sales-day',
                    new DateHistogramAggregation(
                        'order-sales-day',
                        'orderDate',
                        DateHistogramAggregation::PER_DAY,
                        null,
                        new TermsAggregation(
                            'sales-by-currency',
                            'currencyFactor',
                            null,
                            null,
                            new SumAggregation('sum-order', 'amountNet')
                        )
                    ),
                    $filters
                )
            );

        }

        $result = $this->orderRepository->search($criteria, $context);
        $aggregation = $result->getAggregations()->get('order-sales-day');

        $data = [];
        foreach ($aggregation->getBuckets() as $bucket) {
            $sum = $this->base->calculateAmountInSystemCurrency($bucket->getResult());
            $data[] = [
                'date' => explode(' ', $bucket->getKey())[0],
                'formatedDate' => $this->base->getFormatedDate($bucket->getKey(), $parameters['adminLocalLanguage']),
                'count' => (int)$bucket->getCount(),
                'sum' => round($sum, 2)
            ];
        }

        if ($parameters['format'] === 'csv') {
            return $this->base->exportCSV($data, $parameters['labels']);
        }

        return $data;
    }
}
