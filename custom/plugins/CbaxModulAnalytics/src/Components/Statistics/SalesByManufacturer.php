<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
//use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

use Cbax\ModulAnalytics\Components\Base;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\Uuid\Uuid;

class SalesByManufacturer
{
    private $config;
    private $base;
    private $productManufacturerRepository;
    private $connection;

    public function __construct(
        $config,
        Base $base,
        EntityRepositoryInterface $productManufacturerRepository,
        Connection $connection
    )
    {
        $this->config = $config;
        $this->base = $base;
        $this->productManufacturerRepository = $productManufacturerRepository;
        $this->connection = $connection;
    }

    public function getSalesByManufacturer($parameters, $context)
    {
        $languageId = $this->base->getLanguageIdByLocaleCode($parameters['adminLocalLanguage'], $context);
        $modifiedContext = $this->base->getLanguageModifiedContext($context, $languageId);

        $qb = $this->connection->createQueryBuilder();
        $query = $qb
            ->select([
                'IF(products.product_manufacturer_id IS NOT NULL, products.product_manufacturer_id, parents.product_manufacturer_id)  as `id`',
                'SUM(lineitems.quantity) as `count`'
            ])
            ->from('order_line_item', 'lineitems')
            ->innerJoin('lineitems', '`order`', 'orders', 'lineitems.order_id = orders.id')
            ->innerJoin('lineitems', '`product`', 'products', 'lineitems.product_id = products.id')
            ->leftJoin('products', '`product`', 'parents', 'products.parent_id = parents.id')
            ->andWhere('lineitems.version_id = :versionId')
            //->andWhere('lineitems.order_version_id = :versionId')
            ->andWhere('orders.version_id = :versionId')
            ->andWhere('products.version_id = :versionId')
            ->andWhere('products.parent_version_id = :versionId')
            ->andWhere('IF(parents.version_id IS NOT NULL, parents.version_id = :versionId, 1)')
            ->andWhere('products.product_manufacturer_version_id = :versionId')
            ->andWhere('IF(products.product_manufacturer_id IS NOT NULL, products.product_manufacturer_id, parents.product_manufacturer_id) IS NOT NULL')
            ->andWhere('lineitems.order_id IS NOT NULL')
            ->andWhere('orders.order_date >= :start')
            ->andWhere('orders.order_date <= :end')
            //->andWhere('lineitems.type = :itemtype')
            ->setParameters([
                'versionId' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
                'start' => $parameters['startDate'],
                'end' => $parameters['endDate'],
                //'itemtype' => 'product',
                'language1' => $languageId,
                'language2' => $context->getLanguageId()
            ])
            ->groupBy('`id`')
            ->orderBy('sum', 'DESC');

        if (!empty($this->config['grossOrNet']) && $this->config['grossOrNet'] == 'gross')
        {
            $query->addSelect([
                "SUM(
                    IF(
                        orders.tax_status = 'gross' OR orders.tax_status = 'tax-free',
                        lineitems.total_price/orders.currency_factor,
                        (((JSON_EXTRACT(lineitems.price,'$.taxRules[0].taxRate')/100)+1)*lineitems.total_price)/orders.currency_factor
                    )
                )
                as `sum`"
            ]);
        } else {

            $query->addSelect([
                "SUM(
                    IF(
                        orders.tax_status = 'net' OR orders.tax_status = 'tax-free',
                        lineitems.total_price/orders.currency_factor,
                        (lineitems.total_price/((JSON_EXTRACT(lineitems.price,'$.taxRules[0].taxRate')/100)+1))/orders.currency_factor
                    )
                )
                as `sum`"
            ]);
        }

        $query = $this->base->setMoreQueryConditions($query, $parameters, $context);

        $manufacturers = $query->execute()->fetchAll();
        $ids = [];

        foreach($manufacturers as &$manufacturer)
        {
            $manufacturer['count'] = (int)$manufacturer['count'];
            $manufacturer['sum'] = round((float)$manufacturer['sum'], 2);
            $manufacturer['id'] = Uuid::fromBytesToHex($manufacturer['id']);
            $ids[] = $manufacturer['id'];
        }
        unset($manufacturer);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('id', $ids));
        $result = $this->productManufacturerRepository->search($criteria, $modifiedContext)->getElements();

        $data = [];
        foreach($manufacturers as $manufacturer)
        {
            $data[] = [
                'id' => $manufacturer['id'],
                'name' => !empty($result[$manufacturer['id']]) ? $result[$manufacturer['id']]->getTranslated()['name'] : '',
                'count' => $manufacturer['count'],
                'sum' => $manufacturer['sum']
            ];
        }

        $seriesData = $this->base->limitData($data, $this->config['chartLimit']);
        $gridData   = $this->base->limitData($data, $this->config['gridLimit']);

        if ($parameters['format'] === 'csv') {
            return $this->base->exportCSV($data, $parameters['labels']);
        }

        return ['gridData' => $gridData, 'seriesData' => $seriesData];
    }
}




