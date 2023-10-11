<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

//use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\EntityAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\FilterAggregation;

use Cbax\ModulAnalytics\Components\Base;

class ProductByOrders
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

    public function getProductByOrders($parameters, $context)
    {
        $languageId = $this->base->getLanguageIdByLocaleCode($parameters['adminLocalLanguage'], $context);
        $modifiedContext = $this->base->getLanguageModifiedContext($context, $languageId);
        $filters = $this->base->getTransactionsFilters($parameters);

        $criteria = $this->base->getBaseCriteria('orderDate', $parameters);
        $criteria->addAssociation('lineItems');
        $criteria->addAssociation('lineItems.product');
        $criteria->addAggregation(new EntityAggregation('products', 'lineItems.product.id', 'product'));
        $criteria->addAggregation(new EntityAggregation('parents', 'lineItems.product.parentId', 'product'));

        $criteria->addAggregation(
            new FilterAggregation(
                'filter-orders-by-product',
                new TermsAggregation(
                    'orders-by-product',
                    'order.lineItems.product.id'
                ),
                $filters
            )
        );

        $result = $this->orderRepository->search($criteria, $modifiedContext);

        $sortedData = $this->base->getProductDataFromAggrgation($result, $modifiedContext, 'count', 'orders-by-product');
        $seriesData = $this->base->limitData($sortedData, $this->config['chartLimit']);
        $gridData   = $this->base->limitData($sortedData, $this->config['gridLimit']);

        if ($parameters['format'] === 'csv') {
            return $this->base->exportCSV($sortedData, $parameters['labels']);
        }

        return ['gridData' => $gridData, 'seriesData' => $seriesData];
    }
}


