<?php

namespace Sisi\Search\ESindexing;

use Doctrine\DBAL\Connection;
use Elasticsearch\Client;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Sisi\Search\ESIndexInterfaces\InterSearchAjaxService;
use Sisi\Search\Service\ClientService;
use Sisi\Search\Service\ContextService;
use Sisi\Search\Service\ExtSearchService;
use Sisi\Search\Service\PriceService;
use Sisi\Search\Service\QueryService;
use Sisi\Search\Service\SearchExtraQueriesService;
use Sisi\Search\Service\SearchHelpService;
use Sisi\Search\Service\VariantenService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class SearchAjaxService implements InterSearchAjaxService
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
     *
     * @return array
     * @SuppressWarnings(PHPMD)
     *
     *
     */
    public function searchProducts(
        $term,
        $properties,
        $manufactoryIds,
        $config,
        $saleschannelContext,
        $connection,
        $getParams,
        $container
    ): array {
        $client = null;
        $price = $getParams['price'];
        $rating = $getParams['rating'];
        $helpService = new SearchHelpService();
        $queryService = new QueryService();
        $hanlerExSearchService = new ExtSearchService();
        $heandlervariants = new VariantenService();
        $heandlerprice = new PriceService();
        $saleschannel = $saleschannelContext->getSalesChannel();
        $languageId = $saleschannel->getLanguageId();
        $salechannelID = $saleschannel->getId();
        $index = $helpService->findLast($connection, $salechannelID, $languageId, $config);
        $from = $getParams['from'];
        $size = $getParams['size'];
        $params = [
            'index' => $index['index'],
            'from' => $from,
            'size' => $size
        ];
        if (array_key_exists('host', $config)) {
            if ($config['elasticsearchAktive'] == '1') {
                $heandlerClient = new ClientService();
                $client = $heandlerClient->createClient($config);
            }
        }
        if (array_key_exists('filterToSearch', $config)) {
            if ($config['filterToSearch'] === 'yes') {
                $fieldsService = $container->get('s_plugin_sisi_search_es_fields.repository');
                $contextService = new ContextService();
                $context = $contextService->getContext();
                $criteria = new Criteria();
                $criteria->addFilter(new EqualsFilter('fieldtype', 'text'));
                $fieldsconfig = $fieldsService->search($criteria, $context);
                $match = $queryService->getTheKindOfMatch($config);
                $fields = [];
                if (sizeOf($fieldsconfig) > 0) {
                    $indexProducts = 0;
                    foreach ($fieldsconfig as $row) {
                        $tablename = $row->getTablename();
                        $str = $hanlerExSearchService->strQueryFields($tablename, $config);
                        $exclude = $row->getExcludesearch();
                        if ($exclude === 'yes') {
                            $str = false;
                        }
                        if ($str) {
                            $name = $helpService->setField($row);
                            $queryService->mergeFields($indexProducts, $fields, $match, $term, $row, $name, $getParams);
                        }
                    }
                }
                $params = $queryService->getQuery($index, $fields, $config, $from, $size);
                $heandlerExtra = new SearchExtraQueriesService();
                $heandlerExtra->addSuggest($params, $config, $term);
                $newParam = $this->mergeQueryInRelationToAllfields(
                    $manufactoryIds,
                    $properties,
                    $rating,
                    $price,
                    $params,
                    $config,
                );
                $heandlervariants->changeQueryForvariantssearch($newParam, $term, $fieldsconfig, $config, true);
                $return = $getParams['frontendService']->search($client, $newParam, $saleschannelContext, $container);
                return $return;
            }
        }

        $this->mergeQueryInrealtionToOneField($config, $term, $properties, $rating, $price, $params);
        $this->mergeManufactory($manufactoryIds, $params);
        $return = $getParams['frontendService']->search($client, $params, $saleschannelContext, $container);
        return $return;
    }

    /**
     * @param array $manufactoryIds
     * @param array|null $properties
     * @param string|null $rating
     * @param array|null $price
     * @param array $params
     * @param array $systemConfig
     *
     * @return array
     */
    private function mergeQueryInRelationToAllfields($manufactoryIds, $properties, $rating, $price, &$params, $systemConfig)
    {
        $heandler = new SearchExtraQueriesService();
        $queryfields = $heandler->removeCategorienFromTheQuery($params['body']['query'], $systemConfig);
        $newParam['body']['query']['bool']['must'][0] = $queryfields;
        $heandler = new ExtSearchService();
        $fields = $params['body']['query'];
        $fields = array_shift($fields);
        $fields = array_shift($fields);
        $newParam['body']['highlight'] = [
            'pre_tags' => ["<em>"],
            // not required
            'post_tags' => ["</em>"],
            // not required
            'fields' => $heandler->getHighlightFields($fields),
            'require_field_match' => false
        ];
        $this->mergeNestedProperties($properties, $systemConfig, $newParam);
        $manufactory = [];
        if ($manufactoryIds !== null) {
            $manufactoryIds = $this->mergeManufactoryIds($manufactoryIds);
            if (!empty($manufactoryIds)) {
                $manufactory['bool']['must'][] = [
                    'match' => [
                        "manufacturer_id" => [
                            'query' => trim($manufactoryIds)
                        ]
                    ]
                ];
            }
        }
        if (count($manufactory) > 0) {
            $newParam['body']['query']['bool']['must'][] = $manufactory;
        }

        if ($rating != null && is_numeric($rating)) {
            $newParam['body']['query']['bool']['must'][]['range']["product_ratingAverage"] = [
                "gte" => $rating
            ];
        }
        $this->mergePriceQuery($price, $newParam);
        $newParam['index'] = $params['index'];
        $newParam['from'] = $params['from'];
        $newParam['size'] = $params['size'];
        if (array_key_exists("min_score", $params['body'])) {
            $newParam['body']["min_score"] = $params['body']["min_score"];
        }
        if (array_key_exists("minScoreFilter", $systemConfig)) {
            if ($systemConfig['minScoreFilter'] > 0) {
                $newParam['body']["min_score"] = $systemConfig['minScoreFilter'];
            }
        }
        return $newParam;
    }

    /**
     * @param array|null $price
     * @param array $newParam
     * @return void
     *
     * @SuppressWarnings(PHPMD)
     */
    private function mergePriceQuery($price, &$newParam)
    {
        if ($price != null) {
            $priceQuery = [];
            if (array_key_exists(0, $price)) {
                if ($price[0] != null && is_numeric($price[0]) && $price[0] > 0) {
                    $priceQuery["gte"] = $price[0];
                }
            }
            if (array_key_exists(1, $price)) {
                if ($price[1] != null && is_numeric($price[1]) && $price[1] > 0) {
                    $priceQuery["lte"] = $price[1];
                }
            }
            if (count($priceQuery) > 0) {
                $newParam['body']['query']['bool']['must'][]['range']["product_priceNet"] = $priceQuery;
            }
        }
    }

    /**
     * @param array $config
     * @param string $term
     * @param array|null $properties
     * @param string|null $properties
     * @param string|null $rating
     * @param array|null $price
     * @param array $params
     *
     * @return void
     */
    private function mergeQueryInrealtionToOneField($config, $term, $properties, $rating, $price, &$params)
    {
        $relationFieled = 'product_name';

        if (array_key_exists('resultpage', $config)) {
            if (!empty($config['resultpage'])) {
                $relationFieled = trim($config['resultpage']);
            }
        }
        if (array_key_exists('filterFuzzy', $config)) {
            if ($config['filterFuzzy'] == 'yes') {
                $params['body']['query']["bool"]["must"][] = [
                    'match' => [
                        $relationFieled => [
                            'query' => trim($term),
                            'fuzziness' => 2
                        ]
                    ]
                ];
            } else {
                $params['body']['query']["bool"]["must"][] = ['match' => [$relationFieled => ['query' => trim($term)]]];
            }
        } else {
            $params['body']['query']["bool"]["must"][] = ['match' => [$relationFieled => ['query' => trim($term)]]];
        }
        if ($rating != null && is_numeric($rating)) {
            $params['body']['query']['bool']['must'][]['range']["product_ratingAverage"] = [
                "gte" => $rating
            ];
        }

        if (array_key_exists("minScoreFilter", $config)) {
            if ($config['minScoreFilter'] > 0) {
                $params['body']["min_score"] = $config['minScoreFilter'];
            }
        }
        $this->mergePriceQuery($price, $params);
        $this->mergeNestedProperties($properties, $config, $params);
    }

    /**
     * @param array|null $properties
     * @param array $systemConfig
     * @param array $newParam
     * @return void
     */
    private function mergeNestedProperties($properties, $systemConfig, &$newParam): void
    {
        $propertiesQuery = [];
        $propertiesnested = [];
        if (is_array($properties)) {
            foreach ($properties as $pro) {
                if (array_key_exists('properties', $systemConfig)) {
                    $propertiesArray["path"] = "properties";
                    $propertiesArray["query"]["bool"]['should'][0]['match']['properties.option_id'] = trim($pro);
                    $propertiesArray["query"]["bool"]['should'][1]['match']['properties.option_name'] = trim($pro);
                    $propertiesQuery["nested"] = $propertiesArray;
                }
                $str = true;
                $heandlervarianten = new VariantenService();
                if ($heandlervarianten->conditionFunction($systemConfig)) {
                    $propertiesnested["nested"] = $this->mergeFilterQueryChildren($pro);
                    $str = false;
                    $newParam['body']['query']['bool']['must'][]["bool"]['should'] = [
                        0 => $propertiesQuery,
                        1 => $propertiesnested
                    ];
                }
                if ($str) {
                    $newParam['body']['query']['bool']['must'][] = $propertiesQuery;
                }
            }
        }
    }

    private function mergeFilterQueryChildren(string $pro)
    {
        $properties["path"] = "children.properties";
        $properties["query"]["bool"]['should'][0]['match']['children.properties.option_id'] = trim($pro);
        $properties["query"]["bool"]['should'][1]['match']['children.properties.option_name'] = trim($pro);
        $propertiesArray["path"] = "children";
        $propertiesArray["query"]['nested'] = $properties;
        return $propertiesArray;
    }

    /**
     * @param array|string|null $manufactoryIds
     * @param array $params
     * @return void
     */
    private function mergeManufactory($manufactoryIds, &$params)
    {
        $manufactoryIds = $this->mergeManufactoryIds($manufactoryIds);
        if (!empty($manufactoryIds)) {
            $params['body']['query']["bool"]["must"][] = [
                'match' => [
                    "manufacturer_id" => [
                        'query' => trim($manufactoryIds)
                    ]
                ]
            ];
        }
    }

    /**
     * @param array|string|null $manufactoryIds
     * @return string
     */
    private function mergeManufactoryIds($manufactoryIds)
    {
        if (is_array($manufactoryIds)) {
            $manu = '';
            $index = 0;
            foreach ($manufactoryIds as $id) {
                if ($index == 0) {
                    $manu .= $id;
                } else {
                    $manu .= " " . $id;
                }
                $index++;
            }
            return $manu;
        }
        return '';
    }
}
