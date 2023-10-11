<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Doctrine\DBAL\Connection;

use Cbax\ModulAnalytics\Components\Base;
use Shopware\Core\Framework\Uuid\Uuid;

class SalesByProducts
{
    private $config;
    private $base;
    private $productRepository;
    private $connection;

    public function __construct(
        $config,
        Base $base,
        EntityRepositoryInterface $productRepository,
        Connection $connection
    )
    {
        $this->config = $config;
        $this->base = $base;
        $this->productRepository = $productRepository;
        $this->connection = $connection;
    }

    public function getSalesByProducts($parameters, Context $context)
    {
        $languageId = $this->base->getLanguageIdByLocaleCode($parameters['adminLocalLanguage'], $context);
        $modifiedContext = $this->base->getLanguageModifiedContext($context, $languageId);

        $qb = $this->connection->createQueryBuilder();
        $query = $qb
            ->select([
                'lineitems.product_id as `id`',
                'products.product_number as number',
                'IFNULL(IFNULL(IFNULL(trans1.name, trans2.name), trans1Parent.name), trans2Parent.name) as name',
                'SUM(lineitems.quantity) as `sum`',
                'products.option_ids as optionIds'
            ])
            ->from('order_line_item', 'lineitems')
            ->innerJoin('lineitems', '`order`', 'orders', 'lineitems.order_id = orders.id')
            ->innerJoin('lineitems', '`product`', 'products', 'lineitems.product_id = products.id')
            ->leftJoin('products', 'product_translation', 'trans1',
                'products.id = trans1.product_id AND trans1.language_id = UNHEX(:language1)')
            ->leftJoin('products', 'product_translation', 'trans2',
                'products.id = trans2.product_id AND trans2.language_id = UNHEX(:language2)')
            ->leftJoin('products', 'product_translation', 'trans1Parent',
                'products.parent_id = trans1Parent.product_id AND trans1Parent.language_id = UNHEX(:language1)')
            ->leftJoin('products', 'product_translation', 'trans2Parent',
                'products.parent_id = trans2Parent.product_id AND trans2Parent.language_id = UNHEX(:language2)')
            ->andWhere('lineitems.version_id = :versionId')
            //->andWhere('lineitems.order_version_id = :versionId')
            ->andWhere('orders.version_id = :versionId')
            ->andWhere('products.version_id = :versionId')
            ->andWhere('lineitems.order_id IS NOT NULL')
            ->andWhere('IF(trans1.product_version_id IS NOT NULL, trans1.product_version_id = :versionId, 1)')
            ->andWhere('IF(trans2.product_version_id IS NOT NULL, trans2.product_version_id = :versionId, 1)')
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
            ->orderBy('sales', 'DESC');

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
                as sales"
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
                as sales"
            ]);
        }

        $query = $this->base->setMoreQueryConditions($query, $parameters, $context);

        $data = $query->execute()->fetchAll();

        $variantIds = [];
        $variantSearch = [];
        foreach ($data as $product)
        {
            if (!empty($product['optionIds']))
            {
                $variantIds[] = Uuid::fromBytesToHex($product['id']);
            }
        }
        if (!empty($variantIds))
        {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsAnyFilter('id', $variantIds));
            $criteria->addAssociation('options');
            $criteria->getAssociation('options')
                ->addSorting(new FieldSorting('groupId'))
                ->addSorting(new FieldSorting('id'));
            $variantSearch = $this->productRepository->search($criteria, $modifiedContext)->getElements();
        }

        foreach($data as &$product)
        {
            $product['sum'] = (int)$product['sum'];
            $product['sales'] = round((float)$product['sales'], 2);
            $product['id'] = Uuid::fromBytesToHex($product['id']);

            if (!empty($product['optionIds']) &&
                !empty($variantSearch[$product['id']]) &&
                !empty($variantSearch[$product['id']]->getOptions()) &&
                !empty($variantSearch[$product['id']]->getOptions()->getElements())
            )
            {
                $variantOptionNames = $this->base->getVariantOptionsNames($variantSearch[$product['id']]);
                $product['name'] .= ' -' . $variantOptionNames;
            }
            unset($product['optionIds']);
        }
        unset($product);

        $seriesData = $this->base->limitData($data, $this->config['chartLimit']);
        $gridData   = $this->base->limitData($data, $this->config['gridLimit']);

        if ($parameters['format'] === 'csv') {
            return $this->base->exportCSV($data, $parameters['labels']);
        }

        return ['gridData' => $gridData, 'seriesData' => $seriesData];
    }
}





