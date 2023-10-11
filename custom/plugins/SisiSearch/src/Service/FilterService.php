<?php

namespace Sisi\Search\Service;

use Doctrine\DBAL\Connection;
use Elasticsearch\Client;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Sisi\Search\ESIndexInterfaces\InterSearchAjaxService;
use Sisi\Search\ServicesInterfaces\InterfaceFrontendService;
use Sisi\Search\ServicesInterfaces\InterfaceSearchCategorieService;
use Sisi\Search\ServicesInterfaces\InterfaceQuerylogSearchService;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SearchService
 * @package Sisi\Search\Service
 *
 */
class FilterService
{
    /**
     * @param string $term
     * @param array|null $properties
     * @param array|string|null $manufactoryIds
     * @param array $config
     * @param SalesChannelContext $saleschannelContext
     * @param Connection $connection
     * @param array $getParams
     * @param ContainerInterface $container
     * @param InterSearchAjaxService $searchajax
     *
     * @return array
     * @SuppressWarnings(PHPMD)
     *
     *
     */
    public function getthequeryResultfortheFilter(
        $term,
        $properties,
        $manufactoryIds,
        $config,
        $saleschannelContext,
        $connection,
        $getParams,
        $container,
        $searchajax
    ): array {
        $getParams['size'] = 20;
        $strloop = true;
        $mergeresult = [];
        $from = 0;
        $index = 0;
        $config['sisiProductPriceCalculator'] = null;
        if (array_key_exists('extraquerxmenory', $config)) {
            if ($config['extraquerxmenory'] === 'yes') {
                if (array_key_exists('fragmentsizeRecursive', $config)) {
                    if ($config['fragmentsizeRecursive'] > 0) {
                        $getParams['size'] = $config['fragmentsizeRecursive'];
                    }
                }
                while ($strloop) {
                    $newResult = $searchajax->searchProducts(
                        $term,
                        $properties,
                        $manufactoryIds,
                        $config,
                        $saleschannelContext,
                        $connection,
                        $getParams,
                        $container
                    );
                    if ($index === 0) {
                        $mergeresult = $newResult;
                        unset($mergeresult['hits']['hits']);
                        $mergeresult['hits']['hits'] = [];
                    }
                    $hits = [];
                    foreach ($newResult['hits']['hits'] as $hit) {
                        $hits["_source"]["product_name"] = $hit["_source"]["product_name"];
                        $hits["_source"]["channel"]['_id'] = $hit["_source"]["channel"]["id"];
                        $hits["_source"]["channel"]["sortedProperties"] = $hit["_source"]["channel"]["sortedProperties"];
                        if (array_key_exists("manufacturer_name", $hit["_source"])) {
                            $hits["_source"]["manufacturer_name"] = $hit["_source"]["manufacturer_name"];
                        }
                        if (array_key_exists("manufacturer_id", $hit["_source"])) {
                            $hits["_source"]["manufacturer_id"] = $hit["_source"]["manufacturer_id"];
                        }
                        $mergeresult['hits']['hits'][] = $hits;
                    }
                    $toalvalue = count($newResult['hits']['hits']);
                    if (array_key_exists('extraquerySizeforfilter', $config)) {
                        if (($config['extraquerySizeforfilter'] <= $from)) {
                            $strloop = false;
                        }
                    }
                    if ($toalvalue <= 0) {
                        $strloop = false;
                    }
                    $from = $from + $getParams['size'];
                    $getParams['from'] = $from;
                    $index++;
                }

                return $mergeresult;
            }
        }
        $mergeresult = $searchajax->searchProducts(
            $term,
            $properties,
            $manufactoryIds,
            $config,
            $saleschannelContext,
            $connection,
            $getParams,
            $container
        );

        return $mergeresult;
    }
}
