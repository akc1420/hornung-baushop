<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

//use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\SumAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\EntityAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\FilterAggregation;

use Cbax\ModulAnalytics\Components\Base;

class SalesByCustomer
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

    public function getSalesByCustomer($parameters, $context)
    {
        $criteria = $this->base->getBaseCriteria('orderDate', $parameters);
        $filters = $this->base->getTransactionsFilters($parameters);

        $criteria->addAssociation('orderCustomer');
        $criteria->addAggregation(new EntityAggregation('customers', 'orderCustomer.customerId', 'customer'));

        if (!empty($this->config['grossOrNet']) && $this->config['grossOrNet'] == 'gross')
        {
            $criteria->addAggregation(
                new FilterAggregation(
                    'filter-sales-by-customer',
                    new TermsAggregation(
                        'sales-by-customer',
                        'order.orderCustomer.customerId',
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
                    'filter-sales-by-customer',
                    new TermsAggregation(
                        'sales-by-customer',
                        'order.orderCustomer.customerId',
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
        $aggregation = $result->getAggregations()->get('sales-by-customer');
        $customers = $result->getAggregations()->get('customers')->getEntities()->getElements();

        $data = [];
        foreach ($aggregation->getBuckets() as $bucket)
        {
            $key = $bucket->getKey();
            if (empty($key)) continue;
            if (empty($customers[$key])) continue;
            $sum = $this->base->calculateAmountInSystemCurrency($bucket->getResult());

            $data[] = [
                'id' => $key,
                'number' => $customers[$key]->getCustomerNumber(),
                'name' => $customers[$key]->getFirstName() . ' ' . $customers[$key]->getLastName(),
                'count' => (int)$bucket->getCount(),
                'sum' => round($sum, 2)
            ];
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


