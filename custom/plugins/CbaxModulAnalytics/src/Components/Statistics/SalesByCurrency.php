<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\SumAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\EntityAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\FilterAggregation;

use Cbax\ModulAnalytics\Components\Base;

class SalesByCurrency
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

    public function getSalesByCurrency($parameters, Context $context)
    {
        $languageId = $this->base->getLanguageIdByLocaleCode($parameters['adminLocalLanguage'], $context);
        $modifiedContext = $this->base->getLanguageModifiedContext($context, $languageId);

        $criteria = $this->base->getBaseCriteria('orderDate', $parameters);
        $filters = $this->base->getTransactionsFilters($parameters);

        $criteria->addAggregation(new EntityAggregation('currencies', 'currencyId', 'currency'));

        if (!empty($this->config['grossOrNet']) && $this->config['grossOrNet'] == 'gross')
        {
            $criteria->addAggregation(
                new FilterAggregation(
                    'filter-sales-by-currency',
                    new TermsAggregation(
                        'sales-by-currency',
                        'currencyId',
                        null,
                        null,
                        new SumAggregation('sum-order', 'amountTotal')
                    ),
                    $filters
                )
            );

        } else {

            $criteria->addAggregation(
                new FilterAggregation(
                    'filter-sales-by-currency',
                    new TermsAggregation(
                        'sales-by-currency',
                        'currencyId',
                        null,
                        null,
                        new SumAggregation('sum-order', 'amountNet')
                    ),
                    $filters
                )
            );
        }

        $result = $this->orderRepository->search($criteria, $modifiedContext);

        //$sortedData = $this->base->getDataFromAggregations($result,'sales','sales-by-currency','currencies');

        $aggregation = $result->getAggregations()->get('sales-by-currency');
        $entityElements = $result->getAggregations()->get('currencies')->getEntities()->getElements();

        $data = [];
        foreach ($aggregation->getBuckets() as $bucket)
        {
            $name = !empty($entityElements[$bucket->getKey()]) ? $entityElements[$bucket->getKey()]->getTranslated()['name'] : '';

            if (!empty($name))
            {
                $entry = [
                    'name' => $name,
                    'count' => (int)$bucket->getCount(),
                    'sum' => round((float)$bucket->getResult()->getSum(), 2)
                ];
                if ($parameters['format'] !== 'csv') {
                    $entry['shortName'] = $entityElements[$bucket->getKey()]->getShortName() ?? null;
                }
                $data[] = $entry;
            }
        }

        $sortedData =  $this->base->sortArrayByColumn($data);

        $seriesData = $this->base->limitData($sortedData, $this->config['chartLimit']);
        $gridData   = $this->base->limitData($sortedData, $this->config['gridLimit']);

        if ($parameters['format'] === 'csv') {
            return $this->base->exportCSV($sortedData, $parameters['labels']);
        }

        return ['gridData' => $gridData, 'seriesData' => $seriesData];
    }

}


