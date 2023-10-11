<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

//use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\SumAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\FilterAggregation;

use Cbax\ModulAnalytics\Components\Base;

class ProductsProfit
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

    public function getProductsProfit($parameters, $context)
    {
        $languageId = $this->base->getLanguageIdByLocaleCode($parameters['adminLocalLanguage'], $context);
        $modifiedContext = $this->base->getLanguageModifiedContext($context, $languageId);

        $productSearch = $this->base->getProductsForOverviews($parameters['salesChannelIds'], $modifiedContext, true);

        $criteria = $this->base->getBaseCriteria('orderDate', $parameters);
        $filters = $this->base->getTransactionsFilters($parameters);

        $criteria->addAssociation('lineItems');
        $criteria->getAssociation('lineItems')->addAssociation('product');

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

        $result = $this->orderRepository->search($criteria, $modifiedContext);

        $aggregation = $result->getAggregations()->get('count-by-product');

        $productSaleData = [];
        foreach ($aggregation->getBuckets() as $bucket)
        {
            $key = $bucket->getKey();
            if (empty($key)) continue;
            $productSaleData[$key] = (int)$bucket->getResult()->getSum();
        }

        $data = [];
        foreach ($productSearch as $product)
        {
            if ($product->getChildCount() > 0) continue;

            $purchasePrice = $this->base->calculatePurchasePrice($product, $productSearch, $modifiedContext);
            $purchasePrice = !empty($purchasePrice) ? $purchasePrice : 0;
            $cheapestPrice = $this->base->calculatePrice($product, $productSearch, $modifiedContext);
            if (empty($cheapestPrice)) {
                continue;
            }
            $productId = $product->getId();
            $productNumber = $product->getProductNumber();
            $sum = !empty($productSaleData[$productId]) ? $productSaleData[$productId] : 0;

            $profit = ($cheapestPrice - $purchasePrice) * (float)$sum;
            $markUp = round(100 * ($cheapestPrice - $purchasePrice) / $cheapestPrice, 2) . ' %';

            $productTransName = $this->base->getProductTranslatedName($product, $productSearch);

            $data[] = [
                'id' => $productId,
                'number' => $productNumber,
                'name' => $productTransName,
                'profit' => round($profit, 2),
                'markUp' => $markUp,
                'sum' => $sum,
                'pprice' => $purchasePrice,
                'cprice' => $cheapestPrice
            ];
        }

        $overall = array_sum(array_column($data, 'profit'));
        $sortingField = !empty($parameters['sorting'][0]) ? $parameters['sorting'][0] : 'profit';
        $direction = !empty($parameters['sorting'][1]) ? $parameters['sorting'][1] : 'DESC';
        $data = $this->base->sortArrayByColumn($data, $sortingField, $direction);

        if ($parameters['format'] === 'csv') {
            return $this->base->exportCSV($data, $parameters['labels']);
        }

        return ['overall' => $overall, 'gridData' => $data];
    }
}

