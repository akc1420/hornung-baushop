<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

//use Shopware\Core\Framework\Context;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Uuid\Uuid;
use Cbax\ModulAnalytics\Components\Base;

class UnfinishedOrdersByCart
{
    private $config;
    private $base;
    private $connection;

    public function __construct(
        $config,
        Base $base,
        Connection $connection
    )
    {
        $this->config = $config;
        $this->base = $base;
        $this->connection = $connection;
    }

    public function getUnfinishedOrdersByCart($parameters, $context)
    {
        $qb = $this->connection->createQueryBuilder();
        $query = $qb
            ->select([
                'cart.created_at as date',
                'COUNT(cart.token) as count',
                'SUM(cart.price/currency.factor) as price'
            ])
            ->from('`cart`', 'cart')
            ->InnerJoin('cart', 'currency', 'currency', 'cart.currency_id = currency.id')
            ->andWhere('cart.created_at >= :start')
            ->andWhere('cart.created_at <= :end')
            ->andWhere('cart.name != "recalculation"')
            ->andWhere('cart.customer_id IS NOT NULL')
            ->setParameters([
                'start' => $parameters['startDate'],
                'end' => $parameters['endDate']
            ])
            ->orderBy('count', 'DESC')
            ->groupBy('date');

        if (!empty($parameters['salesChannelIds']))
        {
            array_walk($parameters['salesChannelIds'],
                function (&$value) { $value = Uuid::fromHexToBytes($value); }
            );

            $query->andWhere('cart.sales_channel_id IN (:salesChannels)')
                ->setParameter('salesChannels', $parameters['salesChannelIds'], Connection::PARAM_STR_ARRAY);
        }

        if (!empty($parameters['customerGroupIds']))
        {
            array_walk($parameters['customerGroupIds'],
                function (&$value) { $value = Uuid::fromHexToBytes($value); }
            );

            $query->InnerJoin('cart', 'customer', 'customer', 'cart.customer_id = customer.id')
                ->andWhere('customer.customer_group_id IN (:customerGroupIds)')
                ->setParameter('customerGroupIds', $parameters['customerGroupIds'], Connection::PARAM_STR_ARRAY);
        }

        $data = $query->execute()->fetchAll();

        $gridData = [];
        $seriesData =[];

        foreach($data as $day)
        {
            $gridData[] = [
                'date' => $day['date'],
                'formatedDate' => $this->base->getFormatedDate($day['date'], $parameters['adminLocalLanguage']),
                'count' => (int)$day['count'],
                'sales' => round((float)$day['price'], 2),
                'avg' => round( (float)$day['price'] / (float)$day['count'], 2 )
            ];
        }

        if ($parameters['format'] === 'csv') {
            return $this->base->exportCSV($gridData, $parameters['labels']);
        }

        return $gridData;
    }
}
