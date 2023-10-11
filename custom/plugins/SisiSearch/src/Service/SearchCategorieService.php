<?php

namespace Sisi\Search\Service;

use Elasticsearch\Client;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Sisi\Search\Core\Content\Fields\Bundle\DBFieldsEntity;
use Sisi\Search\ServicesInterfaces\InterfaceSearchCategorieService;

/**
 * @SuppressWarnings("ExcessiveClassComplexity")
 **/
class SearchCategorieService implements InterfaceSearchCategorieService
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

    /**
     * @param array $config
     * @param $params
     * @param $configkategorie
     * @param $client
     * @param $term
     * @return array
     *
     * @SuppressWarnings("unused")
     * @SuppressWarnings("CyclomaticComplexity")
     */
    public function searchCategorieWithOwnIndex($config, $params, $configkategorie, $client, $term)
    {
        if (array_key_exists('categorien', $config)) {
            $index = "categorien_" . $params['index'];
            $index = $this->setPrefix($index, $config);
            if ($config['categorien'] === '6' || $config['categorien'] === '7' || $config['categorien'] === '8') {
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
                    'index' =>  $index,
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
        return [];
    }

    private function setPrefix(string $index, array $config): string
    {
        if (array_key_exists('prefix', $config) && array_key_exists('useprefixforcategorie', $config)) {
            if ($config['useprefixforcategorie'] === '1') {
                 return $config['prefix'] . $index;
            }
        }
        return $index;
    }

    /**
     * @param Client $client
     * @param string $indexname
     * @param CategoryEntity $category
     * @param array $fieldConfig
     * @param array $config
     * @param array $parameter
     * @return array
     */
    public function insertValue($client, $indexname, $category, $fieldConfig, $config, $parameter): array
    {
        $fields = [];
        $henadler = new CategorieInsertService();
        $henadler->mergeFields($fieldConfig, $fields, $config, $category, $parameter);
        $type = $category->getType();
        $originCategoryBreadcrumb = $fields["category_breadcrumb"];
        if (!empty($config["removeBreadcrumb"])) {
            $value = $config["removeBreadcrumb"];
            $chunks = array_filter(explode("\n", $value));
        
            foreach ($chunks as $chunk) {
                if (strpos($originCategoryBreadcrumb, $chunk) !== false) {
                    $originCategoryBreadcrumb = str_replace($chunk, '', $originCategoryBreadcrumb);
                }
            }
        }
        $fields["category_breadcrumb"] = $originCategoryBreadcrumb;
        if ($type === "page") {
            $params = [
                'index' => $indexname,
                'id' => strtolower($category->getId()),
                'body' => $fields
            ];
            return $client->index($params);
        } else {
            return [];
        }
    }

    public function createCriteria(): Criteria
    {
        $criteria = new Criteria();
        $criteria->addAssociation('translations');
        $criteria->addAssociation('children');
        $criteria->addAssociation('products');
        $criteria->addFilter(new EqualsFilter('type', 'page'));

        return  $criteria;
    }

    public function createCategoryMapping(array $fieldConfig): array
    {
        foreach ($fieldConfig as $backendconfig) {
            $name = $backendconfig->getPrefix() . $backendconfig->getTablename() . "_" . $backendconfig->getName();
            $analyzer = "analyzer_" . $name;
            $type = $backendconfig->getFieldtype();
            $mapping['properties'][$name] = [
                "type" => $type,
                "analyzer" => $analyzer
            ];
        }

        $mapping['properties']["category_id"] = [
            "type" => "text"
        ];

        $mapping['properties']["category_breadcrumb"] = [
            "type" => "text"
        ];
        return $mapping;
    }

    /**
     * @SuppressWarnings(PHPMD)
     */
    public function createCategorySettings(array $fieldConfigs, array $config)
    {
        $stopsWords = [];
        $stemmervalues = [];
        $settings = [];

        if (array_key_exists('maxngramdiff', $config)) {
            if (!empty($config['maxngramdiff'])) {
                $settings['index']['max_ngram_diff'] = $config['maxngramdiff'];
            }
        }
        if (array_key_exists('maxshinglediff', $config)) {
            if (!empty($config['maxshinglediff'])) {
                $settings['index']['max_shingle_diff'] = $config['maxshinglediff'];
            }
        }
        if (array_key_exists('totalfields', $config)) {
            $settings['index']['mapping']['total_fields']['limit'] = $config['totalfields'];
        }

        foreach ($fieldConfigs as $key => $backendconfig) {
            $minGram = 3;
            $maxGram = 3;
            $filter = [];
            $tokenizer = "ngram";
            $stemming = "";
            $strstop = "";
            $name = $backendconfig->getPrefix() . $backendconfig->getTablename() . "_" . $backendconfig->getName();

            if (!empty($backendconfig->getStemmingstop())) {
                $valuestring = str_replace("\n", "", $backendconfig->getStop());
                $stopsWords[$name] = explode(",", $valuestring);
            }
            if (!empty($backendconfig->getStop())) {
                $strstop = $backendconfig->getStop();
            }
            if (!empty($backendconfig->getMinedge())) {
                $minGram = (int)$backendconfig->getMinedge();
            }

            if (!empty($backendconfig->getEdge())) {
                $maxGram = (int)$backendconfig->getEdge();
            }

            $filterstring = '';
            $filterstringName = '';

            if (!empty($backendconfig->getFilter1()) && $backendconfig->getFilter1() !== 'noselect') {
                 $filterstring = $backendconfig->getFilter1();
            }

            $filterstringName = $filterstringName = $this->mergeFiltername($filterstring, $name);

            $this->setSynonymFilter($filterstring, $filterstringName, $backendconfig, $settings);

            if ($filterstringName !== '') {
                $filter[] = $filterstringName;
            }

            $filterstring = '';
            $filterstringName = '';

            if (!empty($backendconfig->getFilter2()) && $backendconfig->getFilter2() !== 'noselect') {
                $filterstring = $backendconfig->getFilter2();
            }

            $filterstringName = $filterstringName = $this->mergeFiltername($filterstring, $name);

            $this->setSynonymFilter($filterstring, $filterstringName, $backendconfig, $settings);

            if ($filterstring !== '') {
                $filter[] = $filterstringName;
            }

            $filterstring = '';
            $filterstringName = '';

            $filterstringName = $filterstringName = $this->mergeFiltername($filterstring, $name);

            if (!empty($backendconfig->getFilter3()) && $backendconfig->getFilter3() !== 'noselect') {
                $filterstring = $backendconfig->getFilter3();
            }

            $this->setSynonymFilter($filterstring, $filterstringName, $backendconfig, $settings);

            if ($filterstring !== '') {
                $filter[] = $filterstringName;
            }

            if (!empty($backendconfig->getTokenizer())) {
                $tokenizer = $backendconfig->getTokenizer();
            }

            if (!empty($backendconfig->getStemming())) {
                $stemming = $backendconfig->getStemming();
            }

            if ($strstop === 'yes') {
                $filter[] = "stop_" . $name;
            }
            if ($tokenizer === "Edgengramtokenizer") {
                $tokenizer = "edge_ngram";
            }

            $tokenChars = ["letter", "digit"];

            if ($backendconfig->getPunctuation() == 'yes') {
                $tokenChars[] = "punctuation";
            }

            if ($backendconfig->getWhitespace() == 'yes') {
                $tokenChars[] = "whitespace";
            }

            if ($tokenizer === "edge_ngram" || $tokenizer === "ngram") {
                $settings["analysis"]["tokenizer"][$name . "_" . $tokenizer] = [
                    "token_chars" => $tokenChars,
                    "min_gram" => $minGram,
                    "max_gram" => $maxGram,
                    "type" => $tokenizer
                ];
                $tokenizer = $name . "_" . $tokenizer;
            }

            if (!empty($stemming)) {
                $stemmerName = "stemmer_" . $name;
                $filter[] = $stemmerName;
                $stemmervalues[$stemmerName] = $stemming;
            }

            $analyzer = "analyzer_" . $name;
            $settings["analysis"]["analyzer"][$analyzer]["filter"] = $filter;
            $settings["analysis"]["analyzer"][$analyzer]["tokenizer"] = $tokenizer;
        }

        foreach ($stopsWords as $key => $stopsWordsItem) {
            $settings["analysis"]["filter"]["stop_ " . $key] = [
                "type" => "stop",
                "ignore_case" => true,
                "stopwords" => $stopsWordsItem
            ];
            $filter[] = "stop_ " . $key;
        }

        foreach ($stemmervalues as $key => $stemmeritem) {
            $settings["analysis"]["filter"][$key] = [
                "type" => "stemmer",
                "language" => $stemmeritem
            ];
            $filter[] = $key;
        }

        $this->setAutocompetefilter($settings, $config);

        return $settings;
    }

    private function setSynonymFilter(string $filterString, string $filterName, DBFieldsEntity $backendconfig, array &$settings): void
    {
        if ($filterString === 'synonym') {
            $productExtend = new ProductExtendService();
            $values = $productExtend->getSynonymvalue($backendconfig);
            $settings["analysis"]["filter"][$filterName] = [
                "type" => "synonym",
                "synonyms" => $values
            ];
        }
    }

    private function mergeFiltername(string $filterstring, string $name): string
    {
        if ($filterstring  === 'synonym') {
            return $name . "_" . $filterstring;
        }
        return $filterstring;
    }

    public function setAutocompetefilter(array &$settings, array $config): void
    {
        $min = 3;
        $max = 12;

        if (array_key_exists('minedge', $config)) {
            if (!empty($config['minedge'])) {
                $min = $config['minedge'];
            }
        }
        if (array_key_exists('edge', $config)) {
            if (!empty($config['edge'])) {
                $max = $config['edge'];
            }
        }
        $settings["analysis"]["filter"]['autocomplete'] = [
            "type" => "edge_ngram",
            "min_gram" => $min,
            "max_gram" => $max
        ];
    }
}
