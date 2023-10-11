<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

//use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\SumAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\EntityAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\FilterAggregation;

use Cbax\ModulAnalytics\Components\Base;

class SalesBySaleschannels
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

    public function getSalesBySaleschannels($parameters, $context)
    {
        $languageId = $this->base->getLanguageIdByLocaleCode($parameters['adminLocalLanguage'], $context);
        $modifiedContext = $this->base->getLanguageModifiedContext($context, $languageId);
        $parameters['salesChannelIds'] = null;

        $criteria = $this->base->getBaseCriteria('orderDate', $parameters);
        $filters = $this->base->getTransactionsFilters($parameters);

        $criteria->addAggregation(new EntityAggregation('salesChannels', 'salesChannelId', 'sales_channel'));

        if (!empty($this->config['grossOrNet']) && $this->config['grossOrNet'] == 'gross')
        {
            $criteria->addAggregation(
                new FilterAggregation(
                    'filter-sales-by-saleschannel',
                    new TermsAggregation(
                        'sales-by-saleschannel',
                        'salesChannelId',
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
                    'filter-sales-by-saleschannel',
                    new TermsAggregation(
                        'sales-by-saleschannel',
                        'salesChannelId',
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
        $sortedData = $this->base->getDataFromAggregations($result,'sales','sales-by-saleschannel','salesChannels');

        $seriesData = $this->base->limitData($sortedData, $this->config['chartLimit']);
        $gridData   = $this->base->limitData($sortedData, $this->config['gridLimit']);

        if ($parameters['format'] === 'csv') {
            return $this->base->exportCSV($sortedData, $parameters['labels']);
        }

        return ['gridData' => $gridData, 'seriesData' => $seriesData];
    }

}

