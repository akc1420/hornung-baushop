<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

//use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\SumAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\FilterAggregation;

use Cbax\ModulAnalytics\Components\Base;

class CountByProducts
{
    private $config;
    private $base;
    private $orderRepository;
    private $productRepository;

    public function __construct(
        $config,
        Base $base,
        EntityRepositoryInterface $orderRepository,
        EntityRepositoryInterface $productRepository
    )
    {
        $this->config = $config;
        $this->base = $base;
        $this->orderRepository = $orderRepository;
        $this->productRepository = $productRepository;
    }

    public function getCountByProducts($parameters, $context)
    {
        $languageId = $this->base->getLanguageIdByLocaleCode($parameters['adminLocalLanguage'], $context);

        $criteria = $this->base->getBaseCriteria('orderDate', $parameters);
        $filters = $this->base->getTransactionsFilters($parameters);

        $criteria->addAssociation('lineItems');
        $criteria->addAssociation('lineItems.product');

        $criteria->addAggregation(
            new FilterAggregation(
                'filter-count-by-product',
                new TermsAggregation(
                    'count-by-product',
                    'order.lineItems.product.id',
                    null,
                    null,
                    new SumAggregation('sum-order', 'order.lineItems.quantity')
                ),
                $filters
            )
        );

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

        $data = [];
        foreach ($aggregation->getBuckets() as $bucket) {
            $key = $bucket->getKey();
            if (empty($key)) continue;
            $parentID = null;
            $productNumber = null;

            $productCriteria = new Criteria();
            $productCriteria->addFilter(new EqualsFilter('id', $key));
            $productCriteria->addAssociation('translations');
            $productSearch = $this->productRepository->search($productCriteria, $context)->first();
            if (!empty($productSearch)) {

                $productNumber = $productSearch->getProductNumber();
                $parentID = $productSearch->getParentId();
                $translation = $productSearch->getTranslations()->filterByLanguageId($languageId)->first();

                if (empty($translation) && $context->getLanguageId() != $languageId) {
                    $translation = $productSearch->getTranslations()->filterByLanguageId($context->getLanguageId())->first();
                }

                if (!empty($translation) && empty($translation->getName()) && $context->getLanguageId() != $languageId) {
                    $translation = $productSearch->getTranslations()->filterByLanguageId($context->getLanguageId())->first();
                }

                if ((empty($translation) && !empty($parentID)) || (!empty($translation) && empty($translation->getName()) && !empty($parentID))) {
                    $productCriteria = new Criteria();
                    $productCriteria->addFilter(new EqualsFilter('id', $parentID));
                    $productCriteria->addAssociation('translations');
                    $mainVariantSearch = $this->productRepository->search($productCriteria, $context)->first();

                    if (!empty($mainVariantSearch)) {
                        $translation = $mainVariantSearch->getTranslations()->filterByLanguageId($languageId)->first();
                    }

                    if (!empty($mainVariantSearch) && empty($translation) && $context->getLanguageId() != $languageId) {
                        $translation = $mainVariantSearch->getTranslations()->filterByLanguageId($context->getLanguageId())->first();
                    }
                }

                if (!empty($translation)) {
                    $data[] = [
                        'id' => $key,
                        'number' => $productNumber,
                        'name' => $translation->getName(),
                        'sum' => (int)$bucket->getResult()->getSum()
                    ];
                }
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






