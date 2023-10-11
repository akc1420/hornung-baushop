<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

//use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\DateHistogramAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\SumAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\FilterAggregation;

use Cbax\ModulAnalytics\Components\Base;

class SalesByTime
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

    public function getSalesByTime($parameters, $context)
    {
        $criteria = $this->base->getBaseCriteria('orderDate', $parameters);
        $filters = $this->base->getTransactionsFilters($parameters);

        if (!empty($this->config['grossOrNet']) && $this->config['grossOrNet'] == 'gross')
        {
            $criteria->addAggregation(
                new FilterAggregation(
                    'filter-order-sales-hour',
                    new DateHistogramAggregation(
                        'order-sales-hour',
                        'orderDateTime',
                        DateHistogramAggregation::PER_HOUR,
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
                    'filter-order-sales-hour',
                    new DateHistogramAggregation(
                        'order-sales-hour',
                        'orderDateTime',
                        DateHistogramAggregation::PER_HOUR,
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
        $aggregation = $result->getAggregations()->get('order-sales-hour');

        $data = [];
        $seriesdata = [];

        for ($i = 0; $i <= 23; $i++) {
            $data[$i] = ['count' => 0, 'sales' => 0];
        }

        foreach ($aggregation->getBuckets() as $bucket) {
            $key = $bucket->getKey();
            $hour = \DateTime::createFromFormat('Y-m-d H:i:s', $key)->format('G');
            $sum = $this->base->calculateAmountInSystemCurrency($bucket->getResult());
            $data[$hour]['count'] += (int)$bucket->getCount();
            $data[$hour]['sales'] += $sum;
        }

        foreach ($data as $key => $value) {
            $seriesdata[] = [
                'name' => $key . ':00',
                'count' => $value['count'],
                'sum' => round($value['sales'], 2)
            ];
        }

        if ($parameters['format'] === 'csv') {
            return $this->base->exportCSV($seriesdata, $parameters['labels']);
        }

        return $seriesdata;
    }
}



