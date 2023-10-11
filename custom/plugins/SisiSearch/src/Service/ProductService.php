<?php

namespace Sisi\Search\Service;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Sisi\Search\Core\Content\Fields\Bundle\DBFieldsEntity;

/**
 * Class ProductService
 * @package Sisi\Search\Service
 * @SuppressWarnings(PHPMD)
 */
class ProductService
{
    public function setOffsetandLimit(Criteria &$criteria, int $hit, int $pageID): void
    {
        $offset = $pageID * $hit;
        $limit = $offset + $hit;

        if ($limit == 0) {
            $limit = 10;
        }
        $criteria->setOffset($pageID);
        $criteria->setLimit($limit);
    }

    public function searchProdukteManufactory(Criteria $criteria, string $manufacturer): Criteria
    {
        $criteria->addFilter(
            new EqualsFilter('manufacturer.name', $manufacturer)
        );
        $criteria->addSorting(new FieldSorting('product.name'));
        return $criteria;
    }

    public function searchProducte(Criteria $criteria, array $hits): Criteria
    {
        $filter = [];
        foreach ($hits as $hit) {
            $filter[] = new EqualsFilter('id', $hit['_source']['id']);
        }
        $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_OR, $filter));
        $criteria->addAssociation('manufacturer');
        $criteria->addAssociation('manufacturer.translations');
        return $criteria;
    }

    public function searchFilter(Criteria $criteria, array $categoriesIds): Criteria
    {
        $filter = [];
        foreach ($categoriesIds as $id) {
            $filter[] = new EqualsFilter('categories.id', $id);
        }
        $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_OR, $filter));
        return $criteria;
    }

    public function getProducte(Connection $connection): array
    {
        $query = $connection->createQueryBuilder()
            ->select(['*'])
            ->from('product')
            ->innerJoin('product', ' product_translation', 'translation', 'translation.product_id =product.id');
        return $query->execute()->fetchAllAssociative();
    }

    /**
     * @param EntitySearchResult<DBFieldsEntity> $requiredField
     * @param array $config
     * @return array
     */
    public function getCheckRequiredFieldInConfig(EntitySearchResult $requiredField, array $config): array
    {
        $return = [];
        $index = 0;
        foreach ($requiredField as $entity) {
            $onlymain = $entity->getOnlymain();
            $type = $entity->getFieldtype();
            $name = $this->getName($entity);
            $return[$name] = [
                "type" => $type,
            ];

            if ($type == "text") {
                $this->setTextanalyzer($entity, $config, $name, $return);
            }
            $format = $entity->getFormat();

            if ($type === "date") {
                $return[$name] = [
                    "type" => "date"
                ];
            }
            if ($type == "keyword") {
                $normalizerKey = 'normalizer_' . $name;
                $return[$name]["normalizer"] = $normalizerKey;
            }
            if ($type === 'float') {
                $return[$name] = [
                    "type" => "float"
                ];
            }
            if ($type === 'long') {
                $return[$name] = [
                    "type" => "long"
                ];
            }
            if ($onlymain == 'yes') {
                $return[$name . "nest"] = [
                    "type" => 'nested',
                    "properties" => [
                        $name => ["type" => $type],
                        "onlymain" => [
                            "type" => "text",
                            "analyzer" =>  "analyzer_default"
                        ],
                    ]
                ];
                $this->setTextanalyzer($entity, $config, $name, $return[$name . "nest"]['properties']);
            }

            $index++;
        }
        return $return;
    }

    private function getName(DBFieldsEntity $entity): string
    {
        return trim($entity->getPrefix()) . trim($entity->getTablename()) . '_' . trim($entity->getName());
    }

    private function setTextanalyzer(DBFieldsEntity $entity, array $config, string $name, array &$return): void
    {
        $anlyzerKey = 'analyzer_product_name';
        if ($config['querykind'] !== 'cross_fields') {
            $anlyzerKey = 'analyzer_' . $name;
        }
        $return[$name]["analyzer"] = $anlyzerKey;
        if (
            $entity->getFilter1() === 'autocomplete' || $entity->getFilter2() === 'autocomplete'
            || $entity->getFilter3() === 'autocomplete'
        ) {
            $return[$name]["search_analyzer"] = "standard";
        }
    }

    /**
     * @param EntitySearchResult<DBFieldsEntity> $fieldsEntity
     * @param array $config
     * @param array $synoms
     * @return array
     */
    public function mergeSettings(EntitySearchResult $fieldsEntity, array $config, array $synoms): array
    {
        /** @var EntityCollection<DBFieldsEntity> $entities */
        $entities = $fieldsEntity->getEntities();
        $return = [];
        $index = 0;
        $indexAnalysis = 0;
        $indexNormalizer = 0;
        $productExtend = new ProductExtendService();
        $heandlersearchkeyword = new SearchkeyService();
        $filterGobal = $productExtend->getGlobalsSynom($config, $return);
        $return['index']['max_ngram_diff'] = 10;
        $return['index']['max_shingle_diff'] = 10;
        if (array_key_exists('maxngramdiff', $config)) {
            if (!empty($config['maxngramdiff'])) {
                $return['index']['max_ngram_diff'] = $config['maxngramdiff'];
            }
        }
        if (array_key_exists('maxshinglediff', $config)) {
            if (!empty($config['maxshinglediff'])) {
                $return['index']['max_shingle_diff'] = $config['maxshinglediff'];
            }
        }
        if (array_key_exists('totalfields', $config)) {
             $return['index']['mapping']['total_fields']['limit'] = $config['totalfields'];
        }
        if (array_key_exists('totalnested', $config)) {
            $return['index']['mapping']['nested_objects']['limit'] = $config['totalnested'];
        }
        foreach ($entities as $entity) {
            $filtervalues = [];
            if (array_key_exists("synom", $config)) {
                $filtervalues[] = $filterGobal;
            }
            $tokenizer = $this->getTokenizer($entity, $return);
            $filter1 = $entity->getFilter1();
            $filter2 = $entity->getFilter2();
            $filter3 = $entity->getFilter3();
            $stemming = $entity->getStemming();
            $prefix = $entity->getPrefix();
            $name = $entity->getTablename() . '_' . $entity->getName();
            if (!empty($prefix)) {
                $name = trim($prefix) . trim($name);
            }
            $strStemming = $productExtend->mergeStemmigFilter($stemming, $return);
            $strstop = $productExtend->mergeStopWords($entity->getStemmingstop(), $stemming, $name, $return);
            $strOwnStop = $productExtend->mergeOwnStopWords($entity->getStop(), $name, $return);
            $values = $productExtend->getSynonymvalue($entity);
            $filter1 = $productExtend->mergeSynonymFilter($filter1, $values, $name, $return);
            $filter2 = $productExtend->mergeSynonymFilter($filter2, $values, $name, $return);
            $filter3 = $productExtend->mergeSynonymFilter($filter3, $values, $name, $return);
            $filtervalues = $productExtend->removeEmptyElemnetsFromArray($filtervalues);
            $productExtend->mergeStemmigFilterValues($strStemming, $stemming, $filtervalues);
            $productExtend->mergeStopWordsValues($strstop, $name, $filtervalues);
            $productExtend->mergeOwnStopWordsValues($strOwnStop, $name, $filtervalues);
            $productExtend->mergeFiltervalues($filter1, $filter2, $filter3, $filtervalues);
            if ($entity->getFieldtype() === "text") {
                $this->getText($entity, $tokenizer, $filtervalues, $index, $indexAnalysis, $return, $config);
            }
            if ($entity->getFieldtype() === "keyword") {
                $this->getKeywords($entity, $filtervalues, $indexNormalizer, $return);
            }
        }
        $this->addDefaultAnalyzer($return);
        $heandlersearchkeyword->addFilter($return, $config, $synoms);
        return $return;
    }

    protected function addDefaultAnalyzer(array &$return): void
    {
        $return['analysis']['analyzer']['analyzer_default'] = [
            "tokenizer" => "standard",
            "filter" => [0 => 'lowercase']
        ];
    }

    protected function getTokenizer(DBFieldsEntity $entity, array &$return): string
    {
        $tokenizer = $entity->getTokenizer();
        $gram = $entity->getEdge();
        $mingram = $entity->getMinedge();
        if (!(is_numeric($gram))) {
            $gram = 3;
        }
        if (!(is_numeric($mingram))) {
            $mingram = 3;
        }
        if (empty($tokenizer)) {
            $tokenizer = "standard";
        }
        if ($tokenizer === "Edgengramtokenizer") {
            $this->getdEdgengramtokenizer($entity, $tokenizer, $mingram, $gram, $return);
        }
        if ($tokenizer === "ngram" || $tokenizer === 'Edge_n-gram_tokenizer') {
            $this->getNgramtokenizer($entity, $tokenizer, $mingram, $gram, $return);
        }
        if ($tokenizer === "simple_pattern") {
            $name = $entity->getTablename() . '_' . $entity->getName();
            $tokenizer = $name . '_' . $tokenizer;
            $return['analysis']['tokenizer'][$tokenizer] = [
                "type" => "simple_pattern",
                "pattern" => trim($entity->getPattern()),
            ];
        }

        return $tokenizer;
    }

    protected function getdEdgengramtokenizer(
        DBFieldsEntity $entity,
        string &$tokenizer,
        int $mingram,
        int $gram,
        array &$return
    ): void {
        $name = $this->getName($entity);
        $tokenizer = $name . '_' . $tokenizer;
        $return['analysis']['tokenizer'][$tokenizer] = [
            "type" => "edge_ngram",
            "min_gram" => $mingram,
            "max_gram" => $gram,
            "token_chars" => [
                "letter",
                "digit"
            ],
        ];
        $heandlerExtProduktService = new ProductExtendService();
        $heandlerExtProduktService->setEdgeSeparatorConfig($entity, $tokenizer, $return);
    }

    protected function getNgramtokenizer(
        DBFieldsEntity $entity,
        string &$tokenizer,
        int $mingram,
        int $gram,
        array &$return
    ): void {
        $name = $this->getName($entity);
        $tokenizer = $name . '_' . $tokenizer;
        $return['analysis']['tokenizer'][$tokenizer] = [
            "type" => "ngram",
            "min_gram" => $mingram,
            "max_gram" => $gram,
            "token_chars" => [
                "letter",
                "digit"
            ],
        ];
        $heandlerExtProduktService = new ProductExtendService();
        $heandlerExtProduktService->setEdgeSeparatorConfig($entity, $tokenizer, $return);
    }

    protected function getText(
        DBFieldsEntity $entity,
        string $tokenizer,
        array &$filtervalues,
        int &$index,
        int &$indexAnalysis,
        array &$return,
        array $config
    ): void {
        $stopsStrings = $entity->getStop();
        $stemming = $entity->getStemming();
        $strStemmingStop = $entity->getStemmingstop();
        if ($strStemmingStop == null) {
            $strStemmingStop = '';
        }
        if ($stopsStrings == null) {
            $stopsStrings = '';
        }
        $this->setAutoComplete($indexAnalysis, $return, $config);
        $anlyzerKey = 'analyzer_' . $this->getName($entity);
        $return['analysis']['analyzer'][$anlyzerKey]["type"] = "custom";
        $return['analysis']['analyzer'][$anlyzerKey]["tokenizer"] = $tokenizer;
        if (count($filtervalues) > 0) {
            $return['analysis']['analyzer'][$anlyzerKey]["filter"] = $filtervalues;
        }
        if (array_key_exists("suggest", $config)) {
            if ($config["suggest"] === "1") {
                $return['analysis']['analyzer']['trigram'] = [
                    "type" => "custom",
                    "tokenizer" => "standard",
                    "filter" => ["lowercase", "shingle"]
                ];
            }
        }
        $index++;
        $this->setStemming($strStemmingStop, $stemming, $entity, $tokenizer, $filtervalues, $index, $return);
    }

    protected function setAutoComplete(int &$indexAnalysis, array &$return, array $config): void
    {
        $autocompleteKey = 'autocomplete';
        $return['analysis']["filter"][$autocompleteKey] = [
            "type" => "edge_ngram",
            "min_gram" => $config['minedge'],
            "max_gram" => $config['edge'],
        ];
        if (!array_key_exists('minshinglesize', $config)) {
            $config['minshinglesize'] = 2;
        }
        if (!array_key_exists('maxshinglesize', $config)) {
            $config['maxshinglesize'] = 3;
        }
        if (array_key_exists("suggest", $config)) {
            if ($config["suggest"] === "1") {
                $return['analysis']["filter"]["shingle"] = [
                    "type" => "shingle",
                    "min_shingle_size" => $config['minshinglesize'],
                    "max_shingle_size" => $config['maxshinglesize'],
                ];
            }
        }
        $indexAnalysis++;
    }

    protected function setStemming(
        string $strStemmingStop,
        string $stemming,
        DBFieldsEntity $entity,
        string $tokenizer,
        array $filtervalues,
        int &$index,
        array &$return
    ): void {
        if ($strStemmingStop === 'yes') {
            if ($stemming === 'de') {
                $filtervalues[] = "german_stop";
            }
            if ($stemming === 'en') {
                $filtervalues[] = "english_stop";
            }

            $key = "stops_filter_" . $this->getName($entity);
            if (array_key_exists($key, $return['analysis']['filter'])) {
                $filtervalues[] = $key;
            }
            $anlyzerKey = 'analyzer_' . $this->getName($entity);
            $return['analysis']['analyzer'][$anlyzerKey]["type"] = "custom";
            $return['analysis']['analyzer'][$anlyzerKey]["tokenizer"] = $tokenizer;
            if (sizeof($filtervalues) > 0) {
                $return['analysis']['analyzer'][$anlyzerKey]["filter"] = $filtervalues;
            }
            $index++;
        }
    }

    protected function getKeywords(
        DBFieldsEntity $entity,
        array &$filtervalues,
        int &$indexNormalizer,
        array &$return
    ): void {
        $normalizerKey = 'normalizer_' . $this->getName($entity);
        $return["analysis"]["normalizer"][$normalizerKey] = [
            "type" => "custom",
        ];
        if (sizeof($filtervalues) > 0) {
            $return["analysis"]["normalizer"][$normalizerKey]["filter"] = $filtervalues;
        }
        $indexNormalizer++;
    }

    public function mergeRequiredField(array &$params, array $properties, array $config): void
    {
        $categoriesPro = [];

        $params['body']['mappings']['properties']['id'] = [
            'type' => 'object',
            'enabled' => false,
        ];

        $params['body']['mappings']['properties']['product_priceNet'] = [
            'type' => 'float',
        ];

        $params['body']['mappings']['properties']['product_ratingAverage'] = [
            'type' => 'float',
        ];

        $params['body']['mappings']['properties']['product_priceGross'] = [
            'type' => 'float',
        ];

        $params['body']['mappings']['properties']['manufacturer_id'] = [
            'type' => 'text',
            "analyzer" => "analyzer_default"
        ];

        $params['body']['mappings']['properties']['channel'] = [
            'type' => 'object',
            'enabled' => false,
        ];

        $params['body']['mappings']['properties']['properties_group'] = [
            'type' => 'object',
            'enabled' => false,
        ];

        $params['body']['mappings']['properties']['properties'] = [
            'type' => 'nested',
            'enabled' => true,
        ];

        $params['body']['mappings']['properties']['category_breadcrumb'] = [
            'type' => 'object',
            'enabled' => false,
        ];


        if (array_key_exists('analyzer_properties_name', $properties)) {
            $params['body']['mappings']['properties']['properties'] = [
                'properties' => [
                    'option_name' => [
                        "type" => "text",
                        "analyzer" => "analyzer_properties_name"
                    ],
                    'option_id' => [
                        "type" => "text",
                        "analyzer" => "analyzer_properties_name"
                    ],
                ]
            ];
        }

        if (array_key_exists('category_name', $properties)) {
            $categoriesPro['category_name'] = [
                "type" => "text",
                "analyzer" => "analyzer_category_name"
            ];
            $categoriesPro['category_id'] = [
                "type" => "text",
                "analyzer" => "analyzer_category_name"
            ];
        }

        foreach ($properties as $key => $propertie) {
            $pos = strpos($key, "category_");
            if (!($pos === false) && ($key !== 'category_name')) {
                $categoriesPro[$key] = [
                    "type" => "text",
                    "analyzer" => "analyzer_" . $key
                ];
            }
        }
        if (!empty($categoriesPro)) {
            $params['body']['mappings']['properties']['categories'] = [
                'type' => 'nested',
                'properties' => $categoriesPro
            ];
        }

        if (array_key_exists('suggest', $config)) {
            if ($config['suggest'] === '1') {
                $params['body']['mappings']['properties']["product_name_trigram"] = [
                    "type" => "text",
                    "fields" => [
                        "trigram" => [
                            "type" => "text",
                            "analyzer" => "trigram"
                        ]
                    ],
                ];
            }
        }
    }

    public function getRequiredField(): array
    {
        return array(0 => 'name', 1 => 'text');
    }
}
