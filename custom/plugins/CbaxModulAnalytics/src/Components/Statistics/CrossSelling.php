<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Doctrine\DBAL\Connection;
use Shopware\Core\Content\ProductStream\Service\ProductStreamBuilder;
use Shopware\Core\Content\Product\Aggregate\ProductCrossSelling\ProductCrossSellingEntity;
use Cbax\ModulAnalytics\Bootstrap\Database;
use Cbax\ModulAnalytics\Components\Base;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class CrossSelling
{
    private $base;
    private $productRepository;
    private $crossSellingRepository;
    private $connection;
    private $productStreamBuilder;

    public function __construct(
        Base $base,
        EntityRepositoryInterface $productRepository,
        EntityRepositoryInterface $crossSellingRepository,
        Connection $connection,
        ProductStreamBuilder $productStreamBuilder
    )
    {
        $this->base = $base;
        $this->productRepository = $productRepository;
        $this->crossSellingRepository = $crossSellingRepository;
        $this->connection = $connection;
        $this->productStreamBuilder = $productStreamBuilder;
    }

    public function getCrossSelling($parameters, $context)
    {
        $productId = $parameters['productId'];
        $alsoViewed = [];
        $alsoBought = [];
        if (empty($productId)) return [
            'productName' => '',
            'alsoViewed' => $alsoViewed,
            'alsoBought' => $alsoBought
        ];

        $productNamesArray = [];
        $productName = $this->base->getProductNameFromId($productId, $context);
        $gridLimit = (int)$parameters['config']['gridLimit'] ?? 100;

        if (Database::tableExist('cbax_cross_selling_also_viewed', $this->connection))
        {
            $sql = "SELECT HEX(related_product_id) as productId, viewed
                    FROM cbax_cross_selling_also_viewed
                    WHERE HEX(product_id) = ?
                    ORDER BY viewed DESC LIMIT " . $gridLimit . ";";
            $results = $this->connection->executeQuery($sql, [$productId])->fetchAll();

            if (!empty($results))
            {
                foreach ($results as $result)
                {
                    if (!empty($productNamesArray[$result['productId']]))
                    {
                        $name = $productNamesArray[$result['productId']];

                    } else {

                        $name = $this->base->getProductNameFromId($result['productId'], $context);
                        $productNamesArray[$result['productId']] = $name;
                    }

                    $alsoViewed[] = [
                        'productId' => strtolower($result['productId']),
                        'productName' => $name,
                        'viewed' => (int)$result['viewed'],
                        'crossSellings' => []
                    ];
                }
            }
        }

        if (Database::tableExist('cbax_cross_selling_also_bought', $this->connection))
        {
            $sql = "SELECT HEX(related_product_id) as productId, sales
                    FROM cbax_cross_selling_also_bought
                    WHERE HEX(product_id) = ?
                    ORDER BY sales DESC LIMIT " . $gridLimit . ";";
            $results = $this->connection->executeQuery($sql, [$productId])->fetchAll();

            if (!empty($results))
            {
                foreach ($results as $result)
                {
                    if (!empty($productNamesArray[$result['productId']]))
                    {
                        $name = $productNamesArray[$result['productId']];

                    } else {

                        $name = $this->base->getProductNameFromId($result['productId'], $context);
                    }

                    $alsoBought[] = [
                        'productId' => strtolower($result['productId']),
                        'productName' => $name,
                        'sales' => $result['sales'],
                        'crossSellings' => []
                    ];
                }
            }
        }

        if (count($alsoViewed) == 0 && count($alsoBought) == 0)
        {
            return [
                'productName' => $productName,
                'alsoViewed' => $alsoViewed,
                'alsoBought' => $alsoBought
            ];
        }

        // cross-sellings der Produkte ermitteln
        $criteria = new Criteria();
        $criteria->addAssociation('productStream');
        $criteria->addAssociation('assignedProducts');
        $criteria->addFilter(new EqualsFilter('productId', $productId));

        $crossSellings = $this->crossSellingRepository->search($criteria, $context)->getElements();

        $crossSellingProducts = [];
        foreach ($crossSellings as $crossSelling)
        {
            /* @var ProductCrossSellingEntity $crossSelling */
            if ($crossSelling->getType() == 'productList' &&
                !empty($crossSelling->getAssignedProducts()) &&
                $crossSelling->getAssignedProducts()->count() > 0
            )
            {
                $crossSellingProducts[$crossSelling->getTranslated()['name']] = $crossSelling->getAssignedProducts()->getProductIds();

            } elseif ($crossSelling->getType() == 'productStream' &&
                !empty($crossSelling->getProductStreamId()) &&
                !empty($crossSelling->getProductStream()) &&
                !empty($crossSelling->getProductStream()->getApiFilter())
            )
            {
                $crossSellingProducts[$crossSelling->getTranslated()['name']] = $this->getProductIdsFromStream($crossSelling, $context);
            }
        }

        foreach ($alsoViewed as &$item)
        {
            foreach ($crossSellingProducts as $key => $value)
            {
                if (in_array($item['productId'], $value))
                {
                    $item['crossSellings'][] = $key;
                }
            }
        }

        foreach ($alsoBought as &$item)
        {
            foreach ($crossSellingProducts as $key => $value)
            {
                if (in_array($item['productId'], $value))
                {
                    $item['crossSellings'][] = $key;
                }
            }
        }

        return [
            'productName' => $productName,
            'alsoViewed' => $alsoViewed,
            'alsoBought' => $alsoBought
        ];
    }

    /**
     * @param     ProductCrossSellingEntity    $crossSelling
     * @param     Context    $context
     * @return    array
     */
    private function getProductIdsFromStream($crossSelling, $context)
    {
        $id = $crossSelling->getProductStreamId();
        $limit = $crossSelling->getLimit() ?? 30;
        $productCriteria = new Criteria();
        $productCriteria->setLimit($limit);
        $filters = $this->productStreamBuilder->buildFilters($id, $context);
        $productCriteria->addFilter(...$filters);
        $context->setConsiderInheritance(true);

        return $this->productRepository->searchIds($productCriteria, $context)->getIds();
    }

}
