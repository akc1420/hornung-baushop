<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

//use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\SumAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\EntityAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\FilterAggregation;

use Cbax\ModulAnalytics\Components\Base;

class SalesByLanguage
{
    private $config;
    private $base;
    private $orderRepository;
    private $languageRepository;

    public function __construct(
        $config,
        Base $base,
        EntityRepositoryInterface $orderRepository,
        EntityRepositoryInterface $languageRepository
    )
    {
        $this->config = $config;
        $this->base = $base;
        $this->orderRepository = $orderRepository;
        $this->languageRepository = $languageRepository;
    }

    public function getSalesByLanguage($parameters, $context)
    {
        $languageId = $this->base->getLanguageIdByLocaleCode($parameters['adminLocalLanguage'], $context);
        $modifiedContext = $this->base->getLanguageModifiedContext($context, $languageId);

        $criteria = $this->base->getBaseCriteria('orderDate', $parameters);
        $filters = $this->base->getTransactionsFilters($parameters);
        $criteria->addAggregation(new EntityAggregation('languages', 'languageId', 'language'));

        if (!empty($this->config['grossOrNet']) && $this->config['grossOrNet'] == 'gross')
        {
            $criteria->addAggregation(
                new FilterAggregation(
                    'filter-sales-by-language',
                    new TermsAggregation(
                        'sales-by-language',
                        'languageId',
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
                    'filter-sales-by-language',
                    new TermsAggregation(
                        'sales-by-language',
                        'languageId',
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

        $aggregation = $result->getAggregations()->get('sales-by-language');
        $languageIds = $result->getAggregations()->get('languages')->getEntities()->getKeys();
        $data = [];
        $languageResult = [];

        if (!empty($languageIds))
        {
            $languageCriteria = new Criteria();
            $languageCriteria->addFilter(new EqualsAnyFilter('id', $languageIds));
            $languageCriteria->addAssociation('locale');

            $languageResult = $this->languageRepository->search($languageCriteria, $modifiedContext)->getElements();
        }

        foreach ($aggregation->getBuckets() as $bucket)
        {
            $key = $bucket->getKey();
            if (empty($key)) continue;
            if (empty($languageResult[$key])) continue;
            $name = $languageResult[$key]->getLocale()->getTranslated()['name'];
            if (!empty($name))
            {
                $sum = $this->base->calculateAmountInSystemCurrency($bucket->getResult());
                $data[] = [
                    'name' => $name,
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



