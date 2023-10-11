<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

//use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\DateHistogramAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\FilterAggregation;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Defaults;

use Cbax\ModulAnalytics\Components\Base;

class OrdersCountAll
{
    private $config;
    private $base;
    private $orderRepository;
    private $connection;

    public function __construct(
        $config,
        Base $base,
        EntityRepositoryInterface $orderRepository,
        Connection $connection
    )
    {
        $this->config = $config;
        $this->base = $base;
        $this->orderRepository = $orderRepository;
        $this->connection = $connection;
    }

    public function getOrdersCountAll($parameters, $context)
    {
        $criteria = $this->base->getBaseCriteria('orderDate', $parameters);
        $filters = $this->base->getTransactionsFilters($parameters);

        $criteria->addAggregation(
            new FilterAggregation(
                'filter-order_count_day',
                new DateHistogramAggregation(
                    'order_count_day',
                    'orderDate',
                    DateHistogramAggregation::PER_DAY
                ),
                $filters
            )
        );

        $result = $this->orderRepository->search($criteria, $context);
        $aggregation = $result->getAggregations()->get('order_count_day');

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
            ->andWhere('orderCustomer.order_version_id = :versionId')
            ->andWhere('NOT ((SELECT count(ocs.id) FROM order_customer as ocs 
                                    INNER JOIN `order` as o 
                                    ON ocs.order_id = o.id AND o.version_id = :versionId
                                    WHERE orderCustomer.customer_id = ocs.customer_id AND
                                    o.order_date_time < orders.order_date_time AND
                                    ocs.version_id = :versionId AND
                                    ocs.order_version_id = :versionId AND
                                    ocs.order_id != orders.id) > 0)')
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

        $data = [];
        foreach ($aggregation->getBuckets() as $bucket) {
            $rawDate = explode(' ', $bucket->getKey())[0];
            $firstTimeCount = !empty($firstTimeOrders[$rawDate]) ? (int)$firstTimeOrders[$rawDate] : 0;
            $allCount = (int)$bucket->getCount();
            $firstTimeCount = min($firstTimeCount, $allCount);
            $data[] = [
                'date' => $rawDate,
                'formatedDate' => $this->base->getFormatedDate($bucket->getKey(), $parameters['adminLocalLanguage']),
                'firstTimeCount' => $firstTimeCount,
                'returningCount' => $allCount - $firstTimeCount,
                'count' => $allCount
            ];
        }

        if ($parameters['format'] === 'csv') {
            return $this->base->exportCSV($data, $parameters['labels']);
        }

        return $data;
    }
}
