<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\SumAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\FilterAggregation;

use Cbax\ModulAnalytics\Components\Base;

class SalesByAffiliates
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

    public function getSalesByAffiliates($parameters, Context $context)
    {
        $criteria = $this->base->getBaseCriteria('orderDate', $parameters);
        $filters = $this->base->getTransactionsFilters($parameters);

        if (!empty($this->config['grossOrNet']) && $this->config['grossOrNet'] == 'gross')
        {
            $criteria->addAggregation(
                new FilterAggregation(
                    'filter-sales-by-affiliates',
                    new TermsAggregation(
                        'sales-by-affiliates',
                        'affiliateCode',
                        null,
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
                    'filter-sales-by-affiliates',
                    new TermsAggregation(
                        'sales-by-affiliates',
                        'affiliateCode',
                        null,
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
        $aggregation = $result->getAggregations()->get('sales-by-affiliates');

        $data = [];
        foreach ($aggregation->getBuckets() as $bucket) {
            //todo evtl Orders ohne Partner optional ausgeben
            if (!empty($bucket->getKey()))
            {
                $sum = $this->base->calculateAmountInSystemCurrency($bucket->getResult());
                $data[] = [
                    'name' => $bucket->getKey(),
                    'count' => (int)$bucket->getCount(),
                    'sum' => round($sum, 2)
                ];
            }
        }

        $sortedData = $this->base->sortArrayByColumn($data);
        $seriesData = $this->base->limitData($sortedData, $this->config['chartLimit']);
        $gridData   = $this->base->limitData($sortedData, $this->config['gridLimit']);

        if ($parameters['format'] === 'csv') {
            return $this->base->exportCSV($sortedData, $parameters['labels']);
        }

        return ['gridData' => $gridData, 'seriesData' => $seriesData];
    }

}


