<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

//use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\SumAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\EntityAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;

use Cbax\ModulAnalytics\Components\Base;

class ProductSoonOutstock
{
    private $config;
    private $base;
    private $orderRepository;
    private $propertyGroupOptionRepository;
    //const LOOK_BACK_DAYS = 90;

    public function __construct(
        $config,
        Base $base,
        EntityRepositoryInterface $orderRepository,
        EntityRepositoryInterface $propertyGroupOptionRepository
    )
    {
        $this->config = $config;
        $this->base = $base;
        $this->orderRepository = $orderRepository;
        $this->propertyGroupOptionRepository = $propertyGroupOptionRepository;
    }

    public function getProductSoonOutstock($parameters, $context)
    {
        $canceledId = $this->base->getCanceledStateId($context);
        $languageId = $this->base->getLanguageIdByLocaleCode($parameters['adminLocalLanguage'], $context);
        $modifiedContext = $this->base->getLanguageModifiedContext($context, $languageId);

        $criteria = new Criteria();
        $criteria->setLimit(1);

        $criteria->addFilter(
            new RangeFilter('orderDate', [
                RangeFilter::GTE => date('Y-m-d', mktime(0, 0, 0, (int)date("m"), (int)date("d") - $this->config['lookBackDays'], (int)date("Y")))
            ])
        );

        $criteria->addFilter(
            new RangeFilter('lineItems.product.stock', [
                RangeFilter::GT => 0
            ])
        );

        $criteria->addFilter(
            new NotFilter(
                NotFilter::CONNECTION_OR,
                [
                    new EqualsFilter('stateId', $canceledId)
                ]
            )
        );

        if (!empty($parameters['salesChannelIds']))
        {
            $criteria->addFilter(new EqualsAnyFilter('salesChannelId', $parameters['salesChannelIds']));
        }

        $criteria->addAssociation('lineItems');
        $criteria->addAssociation('lineItems.product');
        $criteria->addAggregation(new EntityAggregation('products', 'lineItems.product.id', 'product'));
        $criteria->addAggregation(new EntityAggregation('parents', 'lineItems.product.parentId', 'product'));

        $criteria->addAggregation(
            new TermsAggregation(
                'count-by-product',
                'order.lineItems.product.id',
                null,
                null,
                new SumAggregation('sum-order', 'order.lineItems.quantity')
            )
        );

        $result = $this->orderRepository->search($criteria, $context);
        $aggregation = $result->getAggregations()->get('count-by-product');
        $products = $result->getAggregations()->get('products')->getEntities()->getElements();
        $parents = $result->getAggregations()->get('parents')->getEntities()->getElements();
        $optionIds = [];
        $optionSearch = [];
        $data = [];

        foreach ($products as $product)
        {
            if (!empty($product->getOptionIds()))
            {
                $optionIds = array_unique(array_merge($optionIds, $product->getOptionIds()));
            }
        }
        if (!empty($optionIds))
        {
            $optionCriteria = new Criteria();
            $optionCriteria->addFilter(new EqualsAnyFilter('id', $optionIds));
            $optionSearch = $this->propertyGroupOptionRepository->search($optionCriteria, $modifiedContext)->getElements();
        }

        foreach ($aggregation->getBuckets() as $bucket)
        {
            $key = $bucket->getKey();
            if (empty($key) || empty($products[$key])) continue;
            $productNumber = $products[$key]->getProductNumber();
            $stock = $products[$key]->getStock();
            $productName = $products[$key]->getTranslated()['name'];
            if (empty($productName) && !empty($products[$key]->getparentId()) && !empty($parents[$products[$key]->getparentId()]))
            {
                $productName = $parents[$products[$key]->getparentId()]->getTranslated()['name'];
            }
            if (empty($productName)) continue;
            if (!empty($products[$key]->getOptionIds()))
            {
                $optionNames = '';
                foreach ($products[$key]->getOptionIds() as $optionId)
                {
                    if (!empty($optionSearch[$optionId]) && !empty($optionSearch[$optionId]->getTranslated()['name']))
                    {
                        $optionNames .= ' ' . $optionSearch[$optionId]->getTranslated()['name'];
                    }
                }
                $productName .= ' - ' . $optionNames;
            }
            if (!empty($productName)) {
                $data[] = [
                    'id' => $key,
                    'number' => $productNumber,
                    'name' => $productName,
                    'sum' => (int)$this->getDaysStockLasting((int)$bucket->getResult()->getSum(), $stock),
                    'stock' => $stock
                ];
            }
        }

        $sortedData = $this->base->sortArrayByColumn($data, 'sum', 'ASC');

        //$seriesData = $this->base->limitData($sortedData, $this->config['chartLimit']);
        //$gridData   = $this->base->limitData($sortedData, $this->config['gridLimit']);
        $gridData = array_slice($sortedData, 0, $this->config['gridLimit']);

        if ($parameters['format'] === 'csv') {
            return $this->base->exportCSV($sortedData, $parameters['labels']);
        }

        return ['gridData' => $gridData];
    }

    private function getDaysStockLasting($sold, $stock)
    {
        return round($stock / ($sold / $this->config['lookBackDays']));
    }
}


