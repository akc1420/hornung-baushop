<?php

namespace Sisi\Search\Service;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Swag\DynamicAccess\Core\Content\Product\SalesChannel\CategoryRuleFilter;
use Swag\DynamicAccess\Core\Content\Product\SalesChannel\LandingPageRuleFilter;
use Swag\DynamicAccess\Core\Content\Product\SalesChannel\ProductRuleFilter;

class CriteriaService
{
    /**
     * @param Criteria $criteria
     * @param string $shop
     * @param $lanugage
     */
    public function getMergeCriteriaForFields(&$criteria, $shop, $lanugage): void
    {
        $criteria->addFilter(
            new MultiFilter(
                MultiFilter::CONNECTION_OR,
                [
                    new EqualsFilter('shop', trim($shop)),
                    new EqualsFilter('shop', ""),
                    new EqualsFilter('shop', null),
                    new EqualsFilter('shop', "noselect")
                ]
            )
        );
        $criteria->addFilter(
            new MultiFilter(
                MultiFilter::CONNECTION_OR,
                [
                    new EqualsFilter('shoplanguage', trim($lanugage)),
                    new EqualsFilter('shoplanguage', ""),
                    new EqualsFilter('shoplanguage', null),
                    new EqualsFilter('shoplanguage', "noselect")
                ]
            )
        );
    }

    public function getMergeCriteriaForSalesChannel(Criteria &$criteria, string $shop): void
    {
        $pos = strpos($shop, "shopID=");
        if ($pos !== false) {
            $channelId = str_replace("shopID=", "", $shop);
            $criteria->addFilter(new EqualsFilter('id', $channelId));
        } else {
            $criteria->addFilter(new EqualsFilter('name', $shop));
        }
        $criteria->addFilter(new EqualsFilter('active', 1));
        $criteria->addAssociation('landingPages');
    }

    public function fixDynamicAccess(Criteria &$criteria, array $config): void
    {
        if (array_key_exists('usedynamicaccess', $config)) {
            if ($config['usedynamicaccess'] === '1') {
                $criteria->addFilter(new EqualsFilter('strfixDynamic', 1));
            }
        }
        if (array_key_exists('addInaktive', $config)) {
            if ($config['addInaktive'] === 'yes') {
                $criteria->addFilter(new EqualsFilter('strfixDynamic', 2));
            }
        }
    }
    /**
     * @param Criteria $criteria
     * @param array $config
     * @param array $paramteters
     *
     *  @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @return void
     */
    public function getOnlyMainProducts(Criteria &$criteria, array $config, array $paramteters): void
    {
        if (array_key_exists('main', $paramteters)) {
            if (is_numeric($paramteters['main']) && (int)$paramteters['main'] != 0) {
                $criteria->addFilter(
                    new EqualsFilter('parentId', null)
                );
            }
        } elseif (array_key_exists('onlymain', $config)) {
            if ($config['onlymain'] === 'yes' || $config['onlymain'] === 'stock' || $config['onlymain'] === 'nostockandmain') {
                $criteria->addFilter(new EqualsFilter('parentId', null));
            }
            if ($config['onlymain'] === 'nostock' || $config['onlymain'] === 'nostockandmain') {
                $criteria->addFilter(
                    new MultiFilter(
                        MultiFilter::CONNECTION_AND,
                        [
                            new RangeFilter('stock', [RangeFilter::GT => 0]),
                            new EqualsFilter('available', 1),
                            new EqualsFilter('isCloseout', 0)
                        ]
                    )
                );
            }
            if ($config['onlymain'] === 'variants') {
                $criteria->addFilter(
                    new MultiFilter(
                        MultiFilter::CONNECTION_OR,
                        [
                            new  NotFilter(
                                NotFilter::CONNECTION_AND,
                                [
                                    new EqualsFilter('parentId', null)
                                ]
                            ),
                            new EqualsFilter('childCount', 0)
                        ]
                    )
                );
            }
        }
    }
}
