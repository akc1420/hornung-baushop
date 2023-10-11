<?php

namespace Sisi\Search\Service;

use _HumbugBox2acd634d137b\Symfony\Component\Console\Output\Output;
use Doctrine\DBAL\Connection;
use Elasticsearch\Client;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Content\Product\Aggregate\ProductSearchKeyword\ProductSearchKeywordEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Console\Output\OutputInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Content\Product\Aggregate\ProductSearchKeyword\ProductSearchKeywordCollection;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepositoryInterface;
use Sisi\Search\Core\Content\Fields\Bundle\DBFieldsEntity;

/**
*  @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class SearchExtraQueriesService
{
    /**
     * @param array $config
     * @param array $params
     * @param array $configkategorie
     * @param client $client
     * @param string $term
     * @return array|void
     */
    public function searchCategorie($config, $params, $configkategorie, $client, $term)
    {
        if (array_key_exists('categorien', $config)) {
            if ($config['categorien'] !== '2') {
                $paramsForPropertie['index'] = $params['index'];
                $paramsForPropertie['body']['query'] = '';
                foreach ($configkategorie as $queries) {
                    if (array_key_exists('match', $queries)) {
                        foreach ($queries['match'] as $key => $item) {
                            $should[]["match"]['categories.' . $key] = $item;
                        }
                    }
                }
                $should[] = [
                    'match' => [
                        'categories.category_id' =>  $term,

                    ]
                ];
                $paramsForPropertie = [
                    'index' => $params['index'],
                    'body' => [
                        'query' => [
                            'nested' => [
                                "path" => "categories",
                                'query' => [
                                    'bool' => [
                                        'should' => $should
                                    ]
                                ],
                                'inner_hits' => [
                                    'highlight' => [
                                        'pre_tags' => ["<b>"], // not required
                                        'post_tags' => ["</b>"], // not required,
                                        'fields' => [
                                            'categories.category_name' => new \stdClass()
                                        ],
                                        'require_field_match' => false
                                    ]
                                ]
                            ]
                        ],

                    ],
                ];
                if (array_key_exists('fragmentsizecategorie', $config)) {
                    $framisize = (int)$config['fragmentsizecategorie'];
                    if (!empty($config['fragmentsizecategorie']) && $framisize > 0) {
                        $paramsForPropertie['body']['query']['nested']['inner_hits']['highlight']['fragment_size'] = $framisize;
                    }
                }

                return $client->search($paramsForPropertie);
            }
        }
    }

    public function searchCategorieWithOwnIndex($config, $params, $configkategorie, $client, $term)
    {
        if (array_key_exists('categorien', $config)) {
            if ($config['categorien'] === '6' || $config['categorien'] === '7' ||  $config['categorien'] === '8') {
                foreach ($configkategorie as $queries) {
                    if (array_key_exists('match', $queries)) {
                        foreach ($queries['match'] as $key => $item) {
                            $should[]["match"][$key] = $item;
                        }
                    }
                }
                $should[] = [
                    'match' => [
                        'category_id' =>  $term,

                    ]
                ];
                $paramsForPropertie = [
                    'index' => "categorien_" . $params['index'],
                    'body' => [
                        'query' => [
                            'bool' => [
                                'should' => $should
                            ],
                        ],
                        'highlight' => [
                            'pre_tags' => ["<b>"], // not required
                            'post_tags' => ["</b>"], // not required,
                            'fields' => [
                                'category_name' => new \stdClass()
                            ],
                            'require_field_match' => false
                        ]
                    ],
                ];
                if (array_key_exists('fragmentsizecategorie', $config)) {
                    $framisize = (int)$config['fragmentsizecategorie'];
                    if (!empty($config['fragmentsizecategorie']) && $framisize > 0) {
                        $paramsForPropertie['body']['highlight']['fragment_size'] = $framisize;
                    }
                }


                return $client->search($paramsForPropertie);
            }
        }
    }

    public function removeCategorienFromTheQuery(array $query, array $config)
    {
        $key1 = array_key_first($query);
        $key2 = array_key_first($query[$key1]);
        $return = [];
        if (array_key_exists('categorien', $config)) {
            if ($config['categorien'] === '4' || $config['categorien'] === '8') {
                return $query;
            }
        }
        foreach ($query[$key1][$key2] as $item) {
            $matchkey = array_key_first($item);
            if (array_key_exists('match', $item)) {
                foreach ($item[$matchkey] as $key => $inneritem) {
                    $pos = strpos($key, 'category');
                    if ($pos === false) {
                        $return[] = $item;
                    }
                }
            } else {
                $return[] = $item;
            }
        }
        $query[$key1][$key2] = $return;
        return $query;
    }

    /**
     * @param array $fields
     * @param array $config
     * @param string $match
     * @return array
     *
     * @SuppressWarnings("unused")
     */
    public function fixQueryforCategorie($fields, $config, $match)
    {
        $return['fields'] = $fields;
        $return['config'] = [];
        $return['categorien'] = [];
        if (array_key_exists('categorien', $config)) {
            if ($config['categorien'] !== '2') {
                $return = [];
                foreach ($fields as $index => $field) {
                    if (array_key_exists($match, $field)) {
                        foreach ($field[$match] as $key => $fieldItem) {
                            if (($config['categorien'] == '4')) {
                                $return['fields'][] = $field;
                                $return['config'] = $fieldItem;
                            } else {
                                $str = false;
                                if ($config['categorien'] === '8' ||  $config['categorien'] === '7' ||  $config['categorien'] === '8') {
                                    $str = true;
                                }
                                $pos = strpos($key, "category_");
                                if ($pos === false) {
                                    $return['fields'][] = $field;
                                    $return['config'] = $fieldItem;
                                } else {
                                    $return['categorien'][] = $field;
                                    if ($str) {
                                        $return['fields'][] = $field;
                                        $return['config'] = $fieldItem;
                                    }
                                }
                            }
                        }
                    }
                    if (array_key_exists('nested', $field)) {
                        $return['fields'][] = $field;
                    }
                }
            }
        }
        $this->addNestedQuery($return, $config);
        return $return;
    }

    /**
     * @param array $return
     * @param array $config
     * @return void
     *
     * @SuppressWarnings("unused")
     */
    public function addNestedQuery(array &$return, array $config): void
    {
        $str = true;
        if (array_key_exists('querykind', $config)) {
            if ($config['querykind'] === 'cross_fields') {
                $str = false;
            }
        }
        if (array_key_exists('fields', $return)) {
            foreach ($return['fields'] as $index => $field) {
                if (array_key_exists('match', $field)) {
                    foreach ($field['match'] as $key => $fieldItem) {
                        if ($key === 'properties_name') {
                            $propertiesArray["path"] = "properties";
                            $propertiesArray["query"]["bool"]['should'][0]['match']['properties.option_id'] = $fieldItem;
                            $propertiesArray["query"]["bool"]['should'][1]['match']['properties.option_name'] = $fieldItem;
                            $propertiesQuery["nested"] = $propertiesArray;
                            if ($str) {
                                $return['fields'][$index] = $propertiesQuery;
                            } else {
                                $return['fields'][] = $propertiesQuery;
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @param array $params
     * @param array $config
     * @param string $term
     * @return void
     */
    public function addSuggest(array &$params, array $config, string $term): void
    {
        if (array_key_exists('suggest', $config)) {
            if ($config['suggest'] === '1') {
                if (!array_key_exists("gramsize", $config)) {
                    $config["gramsize"] = 20;
                }
                if (!array_key_exists("suggestsize", $config)) {
                    $config["suggestsize"] = 1;
                }

                if (!array_key_exists("suggestmode", $config)) {
                    $config["suggestmode"] = "always";
                }

                $params["body"]["suggest"] = [
                    "text" => $term,
                    "simple_phrase_product_name" => [
                        "phrase" => [
                            "field" => "product_name_trigram",
                            "size" => $config["suggestsize"],
                            "gram_size" => $config["gramsize"],
                            "direct_generator" => [
                                [
                                    "field" => "product_name_trigram",
                                    "suggest_mode" => $config["suggestmode"]
                                ]
                            ],
                            "highlight" => [
                                "pre_tag" => "<em>",
                                "post_tag" => "</em>"
                            ]
                        ]
                    ]
                ];
            }
        }
    }
}
