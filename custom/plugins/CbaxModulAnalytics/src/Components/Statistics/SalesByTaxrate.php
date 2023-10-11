<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Uuid\Uuid;

use Cbax\ModulAnalytics\Components\Base;

class SalesByTaxrate
{
    private $base;
    private $connection;

    public function __construct(
        Base $base,
        Connection $connection
    )
    {
        $this->base = $base;
        $this->connection = $connection;
    }

    public function getSalesByTaxrate($parameters, Context $context)
    {
        $qb = $this->connection->createQueryBuilder();
        $query = $qb
            ->select([
                'orders.price as price',
                'orders.tax_status as taxStatus'
            ])
            ->from('`order`', 'orders')
            ->andWhere('orders.version_id = :versionId')
            ->andWhere('orders.order_date >= :start')
            ->andWhere('orders.order_date <= :end')
            ->setParameters([
                'versionId' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
                'start' => $parameters['startDate'],
                'end' => $parameters['endDate']
            ]);

        $query = $this->base->setMoreQueryConditions($query, $parameters, $context);

        $data = $query->execute()->fetchAll();

        foreach ($data as &$item) {
            $item['price'] = json_decode($item['price'], true);
        }
        unset($item);

        $taxData = [];
        foreach ($data as $item) {
            foreach ($item['price']['calculatedTaxes'] as $tax) {
                if (empty($taxData[$tax['taxRate']])) {
                    $taxData[$tax['taxRate']] = [
                        'taxRate' => $tax['taxRate'],
                        'tax' => $tax['tax'],
                        'sum' => ($item['taxStatus'] == 'gross') ? $tax['price'] - $tax['tax'] : $tax['price']
                    ];
                } else {
                    $taxData[$tax['taxRate']]['tax'] += $tax['tax'];
                    $taxData[$tax['taxRate']]['sum'] += ($item['taxStatus'] == 'gross') ? $tax['price'] - $tax['tax'] : $tax['price'];
                }
            }
        }

        $taxData = array_values($taxData);

        foreach ($taxData as &$taxItem) {
            $taxItem['tax'] = round($taxItem['tax'], 2);
            $taxItem['sum'] = round($taxItem['sum'], 2);
        }
        unset($taxItem);

        $taxData = $this->base->sortArrayByColumn($taxData, 'taxRate');

        if ($parameters['format'] === 'csv') {
            return $this->base->exportCSV($taxData, $parameters['labels']);
        }

        return ['seriesData' => $taxData];
    }
}

