<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

//use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\DateHistogramAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\SumAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\FilterAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Defaults;

use Cbax\ModulAnalytics\Components\Base;

class QuickOverview
{
    private $config;
    private $base;
    private $orderRepository;
    private $customerRepository;
    private $connection;

    public function __construct(
        $config,
        Base $base,
        EntityRepositoryInterface $orderRepository,
        EntityRepositoryInterface $customerRepository,
        Connection $connection
    )
    {
        $this->config = $config;
        $this->base = $base;
        $this->orderRepository = $orderRepository;
        $this->customerRepository = $customerRepository;
        $this->connection = $connection;
    }

    public function getQuickOverview($parameters, $context)
    {
        $criteria = $this->base->getBaseCriteria('orderDate', $parameters);
        $filters = $this->base->getTransactionsFilters($parameters);

        $criteria->addAggregation(
            new FilterAggregation(
                'filter-order-sales-day',
                new DateHistogramAggregation(
                    'order-sales-day',
                    'orderDate',
                    DateHistogramAggregation::PER_DAY,
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

        $criteria->addAggregation(
            new FilterAggregation(
                'filter-order-netto-sales-day',
                new DateHistogramAggregation(
                    'order-netto-sales-day',
                    'orderDate',
                    DateHistogramAggregation::PER_DAY,
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

        $orderResult = $this->orderRepository->search($criteria, $context);

        $salesAggregation = $orderResult->getAggregations()->get('order-sales-day');
        $nettoAggregation = $orderResult->getAggregations()->get('order-netto-sales-day');

        //Erstbestellungen
        $qb = $this->connection->createQueryBuilder();
        $query = $qb
            ->select([
                'orders.order_date as date',
                'COUNT(orders.id) as count'
            ])
            ->from('`order`', 'orders')

            ->innerJoin('orders', 'order_customer', 'orderCustomer', 'orders.id = orderCustomer.order_id')
            ->andWhere('orderCustomer.version_id = :versionId')
            ->andWhere('NOT ((SELECT count(ocs.id) FROM order_customer as ocs WHERE orderCustomer.customer_id = ocs.customer_id AND ocs.created_at < orders.order_date) > 0)')
            ->andWhere('orders.version_id = :versionId')
            ->andWhere('orders.order_date >= :start')
            ->andWhere('orders.order_date <= :end')
            ->setParameters([
                'start' => $parameters['startDate'],
                'end' => $parameters['endDate'],
                'versionId' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION)
            ])
            ->groupBy('date')
            ->orderBy('date', 'ASC');

        $query = $this->base->setMoreQueryConditions($query, $parameters, $context);

        $firstTimedata = $query->execute()->fetchAll();
        $firstTimeOrders = [];

        if (!empty($firstTimedata))
        {
            foreach ($firstTimedata as $item)
            {
                $firstTimeOrders[$item['date']] = $item['count'];
            }
        }

        //Visitors
        $qb = $this->connection->createQueryBuilder();
        $query = $qb
            ->select([
                'visitors.date as date',
                'SUM(visitors.unique_visits) as visits',
                'SUM(visitors.page_impressions) as impressions'
            ])
            ->from('`cbax_analytics_visitors`', 'visitors')
            ->andWhere('visitors.date >= :start')
            ->andWhere('visitors.date <= :end')
            ->setParameters([
                'start' => $parameters['startDate'],
                'end' => $parameters['endDate']
            ])
            ->groupBy('date')
            ->orderBy('date', 'ASC');

        $visitorsdata = $query->execute()->fetchAll();

        $visitors = [];

        if (!empty($visitorsdata))
        {
            foreach ($visitorsdata as $item)
            {
                $visitors[$item['date'] . ' 00:00:00'] = [$item['visits'], $item['impressions']];
            }
        }
        /////

        $parameters['blacklistedStatesIds'] = [];
        $customerCriteria = $this->base->getBaseCriteria('createdAt', $parameters, false);

        $customerCriteria->addAggregation(
            new DateHistogramAggregation(
                'new-customer-count-day',
                'createdAt',
                DateHistogramAggregation::PER_DAY
            )
        );

        $customerFilters = [
            new RangeFilter('orderCount', [
                RangeFilter::GT => 0
            ])
        ];
        $customerCriteria->addAggregation(
            new FilterAggregation(
                'filter-new-paying-customer-count-day',
                new DateHistogramAggregation(
                    'new-paying-customer-count-day',
                    'createdAt',
                    DateHistogramAggregation::PER_DAY
                ),
                $customerFilters
            )
        );

        $customerResult = $this->customerRepository->search($customerCriteria, $context);
        $customerAggregation = $customerResult->getAggregations()->get('new-customer-count-day');
        $payingCustomerAggregation = $customerResult->getAggregations()->get('new-paying-customer-count-day');

        $allKeys = array_keys($visitors);
        foreach ($salesAggregation->getBuckets() as $bucket)
        {
            if (!in_array($bucket->getKey(), $allKeys))
            {
                $allKeys[] = $bucket->getKey();
            }
        }
        foreach ($customerAggregation->getBuckets() as $bucket)
        {
            if (!in_array($bucket->getKey(), $allKeys))
            {
                $allKeys[] = $bucket->getKey();
            }
        }

        rsort($allKeys);

        $data = [];
        $salesBuckets = [];
        $nettoBuckets = [];
        $customerBuckets = [];
        $payingCustomerBuckets = [];

        foreach ($salesAggregation->getBuckets() as $bucket)
        {
            $salesBuckets[$bucket->getKey()] = $bucket;
        }
        foreach ($nettoAggregation->getBuckets() as $bucket)
        {
            $nettoBuckets[$bucket->getKey()] = $bucket;
        }
        foreach ($customerAggregation->getBuckets() as $bucket)
        {
            $customerBuckets[$bucket->getKey()] = $bucket;
        }
        foreach ($payingCustomerAggregation->getBuckets() as $bucket)
        {
            $payingCustomerBuckets[$bucket->getKey()] = $bucket;
        }

        foreach ($allKeys as $key)
        {
            $entry = [
                'date' => $this->base->getFormatedDate($key, $parameters['adminLocalLanguage']),
                'count' => 0,
                'firstTimeCount' => 0,
                'sales' => 0,
                'avg' => 0,
                'netto' => 0,
                'new' => 0,
                'paying' => 0,
                'visitors' => 0,
                'impressions' => 0
            ];

            if (!empty($visitors[$key]))
            {
                $entry['visitors'] = (int)$visitors[$key][0];
                $entry['impressions'] = (int)$visitors[$key][1];
            }

            if (!empty($salesBuckets[$key]))
            {
                $rawDate = explode(' ', $key)[0];
                $sum = $this->base->calculateAmountInSystemCurrency($salesBuckets[$key]->getResult());
                $entry['sales'] = round($sum, 2);
                $entry['count'] = (int)$salesBuckets[$key]->getCount();
                $entry['firstTimeCount'] = !empty($firstTimeOrders[$rawDate]) ? (int)$firstTimeOrders[$rawDate] : 0;
                $entry['avg'] = round($sum/$entry['count'], 2);
            }
            if (!empty($nettoBuckets[$key]))
            {
                $sum = $this->base->calculateAmountInSystemCurrency($nettoBuckets[$key]->getResult());
                $entry['netto'] = round($sum, 2);
            }
            if (!empty($customerBuckets[$key]))
            {
                $entry['new'] = (int)$customerBuckets[$key]->getCount();
            }

            if (!empty($payingCustomerBuckets[$key]))
            {
                $entry['paying'] = (int)$payingCustomerBuckets[$key]->getCount();
            }

            $data[] = $entry;
        }

        if ($parameters['format'] === 'csv') {
            return $this->base->exportCSV($data, $parameters['labels']);
        }

        return $data;
    }
}

