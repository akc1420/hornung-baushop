<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Shopware\Core\Defaults;
//use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

use Shopware\Core\Content\ProductStream\Service\ProductStreamBuilder;
use Doctrine\DBAL\Connection;

use Cbax\ModulAnalytics\Components\Base;
use Shopware\Core\Framework\Uuid\Uuid;

class SalesByProductsFilter
{
    private $config;
    private $base;
    private $productRepository;
    private $connection;
    private $productStreamRepository;
    private $productStreamBuilder;

    public function __construct(
        $config,
        Base $base,
        EntityRepositoryInterface $productRepository,
        Connection $connection,
        EntityRepositoryInterface $productStreamRepository,
        ProductStreamBuilder $productStreamBuilder
    )
    {
        $this->config = $config;
        $this->base = $base;
        $this->productRepository = $productRepository;
        $this->connection = $connection;
        $this->productStreamRepository = $productStreamRepository;
        $this->productStreamBuilder = $productStreamBuilder;
    }

    public function getSalesByProductsFilter($parameters, $context)
    {
        $languageId = $this->base->getLanguageIdByLocaleCode($parameters['adminLocalLanguage'], $context);
        $modifiedContext = $this->base->getLanguageModifiedContext($context, $languageId);

        $criteriaProductStream = new Criteria();
        $criteriaProductStream->addFilter(new EqualsFilter('id', $parameters['productStreamId']));

        $result = $this->productStreamRepository->search($criteriaProductStream, $modifiedContext)->first();

        if (empty($result)) return ['gridData' => [], 'seriesData' => [], 'overall' => 0];

        $productCriteria = new Criteria();
        $filters = $this->productStreamBuilder->buildFilters($parameters['productStreamId'], $modifiedContext);
        $productCriteria->addFilter(...$filters);
        $productsSearch = $this->productRepository->search($productCriteria, $modifiedContext);
        if ($productsSearch->getTotal() == 0) return ['gridData' => [], 'seriesData' => [], 'overall' => 0];

        $productIds = $productsSearch->getIds();
        $parentsIds = [];
        $variants = [];

        foreach ($productsSearch as $prod)
        {
            if ($prod->getChildCount() > 0)
            {
                $parentsIds[] = $prod->getId();
            }
        }
        if (count($parentsIds) > 0)
        {
            $varCriteria = new Criteria();
            $varCriteria->addFilter(new EqualsAnyFilter('parentId', $parentsIds));
            $varCriteria->addAssociation('options');
            $varCriteria->getAssociation('options')
                ->addSorting(new FieldSorting('groupId'))
                ->addSorting(new FieldSorting('id'));
            $varCriteria->addAssociation('options.translations');
            $variantsSearch = $this->productRepository->search($varCriteria, $modifiedContext);
            $variants = $variantsSearch->getElements();
            $productIds = array_unique(array_merge($productIds, $variantsSearch->getIds()));
        }
        if (empty($productIds)) return ['gridData' => [], 'seriesData' => [], 'overall' => 0];

        $modProductIds = [];
        foreach ($productIds as $productId)
        {
            $modProductIds[] = Uuid::fromHexToBytes($productId);
        }

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
            ->andWhere('lineitems.product_id IN (:modProductIds)')
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
            ->setParameter('modProductIds', $modProductIds, Connection::PARAM_STR_ARRAY)
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

        foreach($data as &$product)
        {
            $product['sum'] = (int)$product['sum'];
            $product['sales'] = round((float)$product['sales'], 2);
            $product['id'] = Uuid::fromBytesToHex($product['id']);
            if (!empty($product['optionIds']))
            {
                if (!empty($variants[$product['id']])) {
                    $variantOptionNames = $this->base->getVariantOptionsNames($variants[$product['id']]);
                    $product['name'] .= ' - ' . $variantOptionNames;
                }
            }
            unset($product['optionIds']);
        }
        unset($product);

        $overall = array_sum(array_column($data, 'sales'));
        $overallCount = array_sum(array_column($data, 'sum'));

        /*
        $sortingField = !empty($parameters['sorting'][0]) ? $parameters['sorting'][0] : 'sales';
        $direction = !empty($parameters['sorting'][1]) ? $parameters['sorting'][1] : 'DESC';
        $data = $this->base->sortArrayByColumn($data, $sortingField, $direction);
        */

        $seriesData = $this->base->limitData($data, $this->config['chartLimit']);
        $gridData   = $this->base->limitData($data, $this->config['gridLimit']);

        if ($parameters['format'] === 'csv') {
            return $this->base->exportCSV($data, $parameters['labels']);
        }

        return ['gridData' => $gridData, 'seriesData' => $seriesData, 'overall' => $overall, 'overallCount' => $overallCount];
    }
}


