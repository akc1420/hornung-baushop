<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\FilterAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\DateHistogramAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\SumAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Cbax\ModulAnalytics\Components\Base;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class SingleProduct
{
    private $base;
    private $productImpressionRepository;
    private $orderRepository;

    public function __construct(
        Base $base,
        EntityRepositoryInterface $productImpressionRepository,
        EntityRepositoryInterface $orderRepository
    )
    {
        $this->base = $base;
        $this->productImpressionRepository = $productImpressionRepository;
        $this->orderRepository = $orderRepository;
    }

    public function getSingleProduct($parameters, Context $context)
    {
        $productId = $parameters['productId'];
        if (empty($productId)) return [
            'seriesData' => [],
            'productName' => [],
            'seriesComparedata' => [],
            'compareProductNames' => [],
            'gridData' => []
        ];

        $compareIds = is_array($parameters['compareIds']) ? $parameters['compareIds'] : [];
        $productName = $this->base->getProductNameFromId($productId, $context) ?? 'Product 1';
        $seriesData = $this->getSeriesdata($productId, $parameters, $context);
        $gridData = array_filter($seriesData, function($value) {
            return $value['count'] > 0 || $value['clicks'] > 0;
        });
        $gridData = array_reverse($gridData);

        $seriesCompareData = [];
        $compareProductNames = [];

        foreach ($compareIds as $prodId)
        {
            $seriesCompareData[] = $this->getSeriesdata($prodId, $parameters, $context);
            $compareProductNames[] = $this->base->getProductNameFromId($prodId, $context);
        }

        return [
            'seriesData' => $seriesData,
            'productName' => $productName,
            'seriesCompareData' => $seriesCompareData,
            'compareProductNames' => $compareProductNames,
            'gridData' => $gridData
        ];
    }

    private function getSeriesdata($productId, $parameters, $context)
    {
        $range = $this->base->getDatesFromRange($parameters['startDate'], $parameters['endDate']);

        $criteria = $this->base->getBaseCriteria('date', $parameters, false);
        $criteria->setLimit(1000);
        $criteria->addFilter(new EqualsFilter('productId', $productId));
        $criteria->addSorting(new FieldSorting('date', FieldSorting::ASCENDING));

        $data = [];
        $result = $this->productImpressionRepository->search($criteria, $context)->getElements();
        if (!empty($result))
        {
            foreach ($result as $item)
            {
                $data[$item->getDate()->format('Y-m-d')] = [
                    'date' => $item->getDate()->format('Y-m-d'),
                    'formatedDate' => $this->base->getFormatedDate($item->getDate(), $parameters['adminLocalLanguage']),
                    'clicks' => (int)$item->getImpressions(),
                    'count' => 0
                ];
            }
        }

        $criteria = $this->base->getBaseCriteria('orderDate', $parameters);
        $filters = $this->base->getTransactionsFilters($parameters);
        $criteria->addAssociation('lineItems');
        $criteria->addFilter(new EqualsFilter('lineItems.productId', $productId));

        $criteria->addAggregation(
            new FilterAggregation(
                'filter-product-sales-day',
                new DateHistogramAggregation(
                    'product-sales-day',
                    'orderDate',
                    DateHistogramAggregation::PER_DAY,
                    null,
                    new SumAggregation('sum-order', 'lineItems.quantity')
                ),
                $filters
            )
        );

        $result = $this->orderRepository->search($criteria, $context);
        $aggregation = $result->getAggregations()->get('product-sales-day');

        foreach ($aggregation->getBuckets() as $bucket)
        {
            $day = explode(' ', $bucket->getKey())[0];
            if (!isset($data[$day]))
            {
                $data[$day] = [
                    'date' => $day,
                    'formatedDate' => $this->base->getFormatedDate($bucket->getKey(), $parameters['adminLocalLanguage']),
                    'clicks' => 0,
                    'count' => (int)$bucket->getResult()->getSum()
                ];
            } else {
                $data[$day]['count'] = (int)$bucket->getResult()->getSum();
            }
        }

        foreach ($range as $day)
        {
            if (!isset($data[$day]))
            {
                $data[$day] = [
                    'date' => $day,
                    'formatedDate' => $this->base->getFormatedDate($day, $parameters['adminLocalLanguage']),
                    'clicks' => 0,
                    'count' => 0
                ];
            }
        }
        ksort($data);

        return array_values($data);
    }

}






