<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
//use Shopware\Core\Framework\Context;
//use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

use Cbax\ModulAnalytics\Components\Base;
use Shopware\Core\Framework\Uuid\Uuid;

class SalesByPromotion
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

    public function getSalesByPromotion($parameters, $context)
    {
        $languageId = $this->base->getLanguageIdByLocaleCode($parameters['adminLocalLanguage'], $context);

        $qb = $this->connection->createQueryBuilder();
        $query = $qb
            ->select([
                'lineitems.promotion_id as `id`',
                'lineitems.referenced_id as code',
                'IF(trans1.name IS NOT NULL, trans1.name, trans2.name) as name',
                'SUM(lineitems.total_price) as `discount`',
                'SUM(lineitems.quantity) as `count`'
            ])
            ->from('order_line_item', 'lineitems')
            ->innerJoin('lineitems', '`order`', 'orders', 'lineitems.order_id = orders.id')
            ->innerJoin('lineitems', 'promotion', 'promotion', 'lineitems.promotion_id = promotion.id')
            ->leftJoin('promotion', 'promotion_translation', 'trans1',
                'promotion.id = trans1.promotion_id AND trans1.language_id = UNHEX(:language1)')
            ->leftJoin('promotion', 'promotion_translation', 'trans2',
                'promotion.id = trans2.promotion_id AND trans2.language_id = UNHEX(:language2)')
            ->andWhere('lineitems.version_id = :versionId')
            //->andWhere('lineitems.order_version_id = :versionId')
            ->andWhere('orders.version_id = :versionId')
            ->andWhere('DATE(orders.order_date) >= :start')
            ->andWhere('DATE(orders.order_date) <= :end')
            ->andWhere('lineitems.type = :itemtype')
            ->andWhere('lineitems.referenced_id IS NOT NULL')
            ->setParameters([
                'versionId' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
                'start' => $parameters['startDate'],
                'end' => $parameters['endDate'],
                'itemtype' => 'promotion',
                'language1' => $languageId,
                'language2' => $context->getLanguageId()
            ])
            ->groupBy('`id`')
            ->orderBy('`sum`', 'DESC');

        if (!empty($this->config['grossOrNet']) && $this->config['grossOrNet'] == 'gross')
        {
            $query->addSelect([
                "SUM(orders.amount_total/orders.currency_factor) as `sum`"
            ]);
        } else {

            $query->addSelect([
                "SUM(orders.amount_net/orders.currency_factor) as `sum`"
            ]);
        }

        $query = $this->base->setMoreQueryConditions($query, $parameters, $context);

        $data = $query->execute()->fetchAll();

        foreach($data as &$promotion)
        {
            $promotion['count'] = (int)$promotion['count'];
            $promotion['sum'] = round((float)$promotion['sum'], 2);
            $promotion['discount'] = round((float)$promotion['discount'], 2);
            $promotion['id'] = Uuid::fromBytesToHex($promotion['id']);
            $promotion['avg'] = round((float)$promotion['sum']/(float)$promotion['count'], 2);
        }
        unset($promotion);

        $gridData = array_slice($data, 0, $this->config['gridLimit']);
        $seriesData = array_slice($data, 0, $this->config['chartLimit']);

        if ($parameters['format'] === 'csv') {
            return $this->base->exportCSV($data, $parameters['labels']);
        }

        return ['gridData' => $gridData, 'seriesData' => $seriesData];
    }
}



