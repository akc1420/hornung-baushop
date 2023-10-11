<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Uuid\Uuid;
use Doctrine\DBAL\Connection;

use Cbax\ModulAnalytics\Components\Base;

class VariantsCompare
{
    private $config;
    private $base;
    private $productRepository;
    private $connection;
    private $propertyGroupOptionRepository;

    public function __construct(
        $config,
        Base $base,
        EntityRepositoryInterface $propertyGroupOptionRepository,
        EntityRepositoryInterface $productRepository,
        Connection $connection
    )
    {
        $this->config = $config;
        $this->base = $base;
        $this->propertyGroupOptionRepository = $propertyGroupOptionRepository;
        $this->productRepository = $productRepository;
        $this->connection = $connection;
    }

    public function getVariantsCompare($parameters, Context $context)
    {
        $languageId = $this->base->getLanguageIdByLocaleCode($parameters['adminLocalLanguage'], $context);
        $modifiedContext = $this->base->getLanguageModifiedContext($context, $languageId);
        $overall = [];
        $overall['sales'] = 0;
        $overall['sum'] = 0;
        $overall['count'] = 0;

        $optionCriteria = new Criteria();
        $optionCriteria->addFilter(new EqualsFilter('groupId', $parameters['propertyGroupId']));
        $optionsSearch = $this->propertyGroupOptionRepository->search($optionCriteria, $modifiedContext);
        $optionIds = $optionsSearch->getIds();
        $options = $optionsSearch->getElements();

        $productCriteria = new Criteria();
        $productCriteria->addAssociation('options');
        $productCriteria->getAssociation('options')
            ->addSorting(new FieldSorting('groupId'))
            ->addSorting(new FieldSorting('id'));
        $productCriteria->addFilter(new EqualsFilter('options.groupId', $parameters['propertyGroupId']));
        $productsSearch = $this->productRepository->search($productCriteria, $modifiedContext);

        if ($productsSearch->getTotal() == 0) return ['gridData' => [], 'seriesData' => [], 'overall' => $overall];

        $products = $productsSearch->getElements();

        if (!empty($parameters['categoryId']))
        {
            foreach ($products as $product)
            {
                $categoryTree = !empty($product->getCategoryTree()) ? $product->getCategoryTree() : [];
                if (!in_array($parameters['categoryId'], $categoryTree))
                {
                    $productsSearch->remove($product->getId());
                }
            }
            $products = $productsSearch->getElements();
        }

        $productIds = $productsSearch->getIds();

        if (empty($productIds)) return ['gridData' => [], 'seriesData' => [], 'overall' => $overall];

        $modProductIds = [];
        foreach ($productIds as $productId)
        {
            $modProductIds[] = Uuid::fromHexToBytes($productId);
        }

        $qb = $this->connection->createQueryBuilder();
        $query = $qb
            ->select([
                'SUM(lineitems.quantity) as `sum`',
                'products.option_ids as optionIds'
            ])
            ->from('order_line_item', 'lineitems')
            ->innerJoin('lineitems', '`order`', 'orders', 'lineitems.order_id = orders.id')
            ->innerJoin('lineitems', '`product`', 'products', 'lineitems.product_id = products.id')
            ->andWhere('lineitems.product_id IN (:modProductIds)')
            ->andWhere('lineitems.version_id = :versionId')
            //->andWhere('lineitems.order_version_id = :versionId')
            ->andWhere('orders.version_id = :versionId')
            ->andWhere('products.version_id = :versionId')
            ->andWhere('lineitems.order_id IS NOT NULL')
            ->andWhere('orders.order_date >= :start')
            ->andWhere('orders.order_date <= :end')
            //->andWhere('lineitems.type = :itemtype')
            ->setParameters([
                'versionId' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
                'start' => $parameters['startDate'],
                'end' => $parameters['endDate']
                //'itemtype' => 'product'
            ])
            ->setParameter('modProductIds', $modProductIds, Connection::PARAM_STR_ARRAY)
            ->groupBy('optionIds');

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

        $result = $query->execute()->fetchAll();

        $data = [];
        $counter1 = 0;
        foreach($optionIds as $optionId) {
            $counter2 = 0;
            foreach ($products as $product)
            {
                if (in_array($optionId, $product->getOptionIds())) $counter2++;
            }
            if ($counter2 === 0) continue;

            $dataSet = [
                //'optionId' => $optionId,
                'name' => $options[$optionId]->getTranslated()['name'],
                'sum' => 0,
                'sales' => 0,
                'count' => $counter2
            ];
            foreach ($result as $item) {
                if (str_contains($item['optionIds'], $optionId)) {
                    $dataSet['sum'] += (int)$item['sum'];
                    $dataSet['sales'] += round((float)$item['sales'], 2);
                }
            }
            if ($dataSet['sum'] > 0) $counter1++;
            $data[] = $dataSet;
        }

        $overall['sales'] = array_sum(array_column($data, 'sales'));
        $overall['sum'] = array_sum(array_column($data, 'sum'));
        $overall['count'] = array_sum(array_column($data, 'count'));

        $data = $this->base->sortArrayByColumn($data, 'sales');
        $seriesData = $this->base->limitData($data, $this->config['chartLimit']);
        $gridData   = $this->base->limitData($data, $this->config['gridLimit']);

        if ($parameters['format'] === 'csv') {
            return $this->base->exportCSV($data, $parameters['labels']);
        }

        return ['gridData' => $gridData, 'seriesData' => $seriesData, 'overall' => $overall];
    }
}



