<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

//use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\SumAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\EntityAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\FilterAggregation;

use Cbax\ModulAnalytics\Components\Base;

class SalesByPayment
{
    private $config;
    private $base;
    private $orderRepository;

    public function __construct($config, Base $base, EntityRepositoryInterface $orderRepository)
    {
        $this->config = $config;
        $this->base = $base;
        $this->orderRepository = $orderRepository;
    }

    public function getSalesByPayment($parameters, $context)
    {
        $languageId = $this->base->getLanguageIdByLocaleCode($parameters['adminLocalLanguage'], $context);
        $modifiedContext = $this->base->getLanguageModifiedContext($context, $languageId);

        // damit Orders mit geänderter Zahlart nicht mehrfach gezählt werden
        $disregardedStates = ['cancelled', 'failed'];

        $criteria = $this->base->getBaseCriteria('orderDate', $parameters);

        if (empty($parameters['blacklistedStatesIds']['transaction']))
        {
            $criteria->addAssociation('transactions');
        }

        $criteria->addAggregation(new EntityAggregation('paymentMethods', 'transactions.paymentMethodId', 'payment_method'));
        if (empty($parameters['blacklistedStatesIds']['transaction']))
        {
            $filters = [
                new NotFilter(
                    NotFilter::CONNECTION_OR,
                    [
                        new EqualsAnyFilter('transactions.stateMachineState.technicalName', $disregardedStates)
                    ]
                )
            ];
        } else {
            $filters = [
                new NotFilter(
                    NotFilter::CONNECTION_OR,
                    [
                        new EqualsAnyFilter('transactions.stateMachineState.technicalName', $disregardedStates),
                        new EqualsAnyFilter('transactions.stateId', $parameters['blacklistedStatesIds']['transaction'])
                    ]
                )
            ];
        }

        // FilterAggregation wegen Transactions von Orders mit geänderter Zahlart
        if (!empty($this->config['grossOrNet']) && $this->config['grossOrNet'] == 'gross')
        {
            $criteria->addAggregation(
                new FilterAggregation(
                    'filter-sales-by-payment',
                    new TermsAggregation(
                        'sales-by-payment',
                        'order.transactions.paymentMethodId',
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
                    'filter-sales-by-payment',
                    new TermsAggregation(
                        'sales-by-payment',
                        'order.transactions.paymentMethodId',
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

        $sortedData = $this->base->getDataFromAggregations($result,'sales','sales-by-payment','paymentMethods');

        $seriesData = $this->base->limitData($sortedData, $this->config['chartLimit']);
        $gridData   = $this->base->limitData($sortedData, $this->config['gridLimit']);

        if ($parameters['format'] === 'csv') {
            return $this->base->exportCSV($sortedData, $parameters['labels']);
        }

        return ['gridData' => $gridData, 'seriesData' => $seriesData];
    }
}


