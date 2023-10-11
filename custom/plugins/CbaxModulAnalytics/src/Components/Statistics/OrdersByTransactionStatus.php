<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

//use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\SumAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\EntityAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\FilterAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;

use Cbax\ModulAnalytics\Components\Base;

class OrdersByTransactionStatus
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

    public function getOrdersByTransactionStatus($parameters, $context)
    {
        $parameters['blacklistedStatesIds']['transaction'] = [];

        $languageId = $this->base->getLanguageIdByLocaleCode($parameters['adminLocalLanguage'], $context);
        $modifiedContext = $this->base->getLanguageModifiedContext($context, $languageId);

        $criteria = $this->base->getBaseCriteria('orderDate', $parameters);
        $filters = [
            new NotFilter(
                NotFilter::CONNECTION_OR,
                [
                    new EqualsAnyFilter('transactions.stateMachineState.technicalName', ['cancelled', 'failed'])
                ]
            )
        ];

        $criteria->addAssociation('transactions');
        $criteria->addAggregation(new EntityAggregation('transactionStates', 'transactions.stateId', 'state_machine_state'));

        if (!empty($this->config['grossOrNet']) && $this->config['grossOrNet'] == 'gross')
        {
            $criteria->addAggregation(
                new FilterAggregation(
                    'filter-orders-by-transaction-status',
                    new TermsAggregation(
                        'orders-by-transaction-status',
                        'order.transactions.stateId',
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
                    'filter-orders-by-transaction-status',
                    new TermsAggregation(
                        'orders-by-transaction-status',
                        'order.transactions.stateId',
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

        $result = $this->orderRepository->search($criteria, $modifiedContext);

        $sortedData = $this->base->getDataFromAggregations($result,'count','orders-by-transaction-status','transactionStates');
        $seriesData = $this->base->limitData($sortedData, $this->config['chartLimit']);
        $gridData   = $this->base->limitData($sortedData, $this->config['gridLimit']);

        if ($parameters['format'] === 'csv') {
            return $this->base->exportCSV($sortedData, $parameters['labels']);
        }

        return ['gridData' => $gridData, 'seriesData' => $seriesData];
    }
}

