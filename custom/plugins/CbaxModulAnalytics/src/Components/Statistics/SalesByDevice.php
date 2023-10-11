<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Defaults;

use Cbax\ModulAnalytics\Components\Base;

class SalesByDevice
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

    public function getSalesByDevice($parameters, $context)
    {
        $qb = $this->connection->createQueryBuilder();
        $query = $qb
            ->select([
                'JSON_UNQUOTE(JSON_EXTRACT(JSON_EXTRACT(orders.custom_fields, "$.cbaxStatistics"),"$.device")) as name',
                'COUNT(orders.id) as count'
            ])
            ->from('`order`', 'orders')
            ->andWhere('orders.version_id = :versionId')
            ->andWhere('orders.order_date >= :start')
            ->andWhere('orders.order_date <= :end')
            ->andWhere('orders.custom_fields IS NOT NULL')
            ->andWhere('JSON_EXTRACT(JSON_EXTRACT(orders.custom_fields, "$.cbaxStatistics"),"$.device") IS NOT NULL')
            ->setParameters([
                'start' => $parameters['startDate'],
                'end' => $parameters['endDate'],
                'versionId' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION)
            ])
            ->groupBy('name')
            ->orderBy('sum', 'DESC');

        if (!empty($this->config['grossOrNet']) && $this->config['grossOrNet'] == 'gross')
        {
            $query->addSelect([
                'SUM(orders.amount_total) as sum'
            ]);
        } else {
            $query->addSelect([
                'SUM(orders.amount_net) as sum'
            ]);
        }

        $query = $this->base->setMoreQueryConditions($query, $parameters, $context);

        $data = $query->execute()->fetchAll();

        foreach ($data as &$set)
        {
            $set['sum'] = round((float)$set['sum'], 2);
            $set['count'] = (int)$set['count'];
            $set['name'] = ucfirst($set['name']);
        }
        unset($set);

        if ($parameters['format'] === 'csv') {
            return $this->base->exportCSV($data, $parameters['labels']);
        }

        return ['gridData' => $data, 'seriesData' => $data];
    }
}



