<?php

namespace Sisi\Search\Service;

use Shopware\Core\Content\Media\Pathname\UrlGeneratorInterface;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Content\Property\PropertyGroupCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\PriceCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Sisi\Search\Service\ContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class VariantenService
{

    private $stock = 0;

    public function setDBQueryWithvariants(array $config, Criteria &$criteria): void
    {
        if ($this->conditionFunction($config)) {
            $criteria->addAssociation('children');
            $criteria->addAssociation('children.properties.translations');
            $criteria->addAssociation('children.properties.group');
            $criteria->addAssociation('children.properties.group.translations');
            $criteria->addAssociation('children.cover');
            $criteria->addAssociation('children.translations');
            if (array_key_exists('cheapestPrice', $config)) {
                if ($config['cheapestPrice'] === 'yes') {
                    $criteria->addAssociation('children.cheapestPrice');
                    $criteria->addAssociation('cheapestPrice');
                }
            }
        }
    }

    public function fixMappingForvariants(array $config, &$mapping, $variantsFields)
    {
        if ($this->conditionFunction($config)) {
            $properties = [];
            foreach ($variantsFields as $key => $pro) {
                $name = $pro->getPrefix() . "product_" . $pro->getName();
                $type = $pro->getFieldtype();
                $properties[$name] = [
                    "type" => "nested",
                ];
                if ($type === 'text') {
                    $properties[$name] = [
                        "type" => $type,
                        "analyzer" => "analyzer_" . $name,
                    ];
                }
            }
            $mapping['properties']['children'] = [
                "type" => "nested",
                'properties' => $properties,
            ];
            $mapping['properties']['children']['properties']['properties'] = [
                "type" => "nested",
            ];
        }
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function fixEsInsertForvariants(
        array &$fields,
        SalesChannelProductEntity &$entitie,
        array $config,
        array $fieldsconfig,
        InsertService $self,
        array $lanuagesArray,
        SalesChannelContext $saleschannelContext,
        ContainerInterface $container,
        array &$merkerIdsFordynamicProducts,
        UrlGeneratorInterface $urlGenerator
    ): void {
        if ($this->conditionFunction($config)) {
            $strLoop = true;
            $count = 0;
            $setpSize = 100;
            $fields['children'] = [];
            if (array_key_exists('childstepsize', $config)) {
                $setpSize = $config['childstepsize'];
            }
            $index = 0;
            $this->stock = 0;
            while ($strLoop) {
                $childrenObject = $this->getChildren(
                    $container,
                    $entitie->getId(),
                    $saleschannelContext,
                    $setpSize,
                    $count
                );
                $total = (int) $childrenObject->getTotal();
                $count = $count + $total;
                $this->mergeCildrenVariants(
                    $fields,
                    $entitie,
                    $config,
                    $fieldsconfig,
                    $self,
                    $lanuagesArray,
                    $merkerIdsFordynamicProducts,
                    $childrenObject,
                    $urlGenerator,
                    $index
                );
                if ($total == 0) {
                    $strLoop = false;
                }
            }
            if (array_key_exists('product_stock', $fields)) {
                $fields['product_stock'] = $this->stock;
            }
        }
    }

    /**
     * @param array $fields
     * @param SalesChannelProductEntity $entitie
     * @param array $config
     * @param array $fieldsconfig
     * @param InsertService $self
     * @param array $lanuagesArray
     * @param array $merkerIdsFordynamicProducts ,
     * @param EntitySearchResult<SalesChannelProductEntity> $childern
     * @param UrlGeneratorInterface $urlGenerator ,
     * @param int $index
     * @return void
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    private function mergeCildrenVariants(
        array &$fields,
        SalesChannelProductEntity &$entitie,
        array $config,
        array $fieldsconfig,
        InsertService $self,
        array $lanuagesArray,
        array &$merkerIdsFordynamicProducts,
        EntitySearchResult $childern,
        UrlGeneratorInterface $urlGenerator,
        int &$index
    ): void {
        if (count($fields) > 0) {
            /** @var array<array<string>> $configuratorSettings */
            $configuratorSettings = $entitie->getConfiguratorGroupConfig();
            $heandlerkeyword = new SearchkeyService();
            $heandlerFixurl = new ChannelDataService();
            $mainId = $entitie->getMainVariantId();
            if ($mainId !== null && !empty($mainId) && $config['addVariants'] === 'individual') {
                $merkerIdsFordynamicProducts[] = $mainId;
            }
            $pricecolltion = new PriceCollection();
            $sorted = new PropertyGroupCollection();
            $optionen = [];
            $mainPropeties = $entitie->getSortedProperties();
            foreach ($mainPropeties->getElements() as $prop) {
                $sorted->add($prop);
                foreach ($prop->getOptions()->getElements() as $key => $op) {
                    $optionen[$key] = $op;
                }
            }
            foreach ($entitie->getPrice()->getElements() as $price) {
                $pricecolltion->add($price);
            }
            $strListing = false;
            if ($configuratorSettings !== null && $config['addVariants'] === 'individual') {
                foreach ($configuratorSettings as $configuratorSettingItem) {
                    if ($configuratorSettingItem["expressionForListings"]) {
                        echo "Item is listed \n";
                        $strListing = true;
                    }
                }
            }
            if ($config['addVariants'] === 'yes') {
                $translations = $entitie->getTranslations();
                foreach ($translations as $translation) {
                    $customFields = $translation->getCustomFields();
                    if ($customFields !== null) {
                        foreach ($customFields as $name => $value) {
                            if ($name === 'sisi_list' && $value > 0) {
                                $strListing = true;
                            }
                        }
                    }
                }
                if (array_key_exists('checkvariantsName', $config)) {
                    if ($config['checkvariantsName'] === 'yes') {
                        $merkerheader = [];
                        $translations = $entitie->getTranslated();
                        $translations[] = $translations['name'];
                        foreach ($childern as $child) {
                            $translations = $child->getTranslated();
                            if (!in_array($translations['name'], $merkerheader)) {
                                $merkerheader[] = $translations['name'];
                            }
                        }
                        if (count($merkerheader) > 1) {
                            $strListing = true;
                        }
                    }
                }
            }
            foreach ($lanuagesArray as $languageId) {
                foreach ($childern as $key => $child) {
                    // check the variants will be as listing
                    if ($strListing) {
                        $merkerIdsFordynamicProducts[] = $child->getId();
                    }
                    $strStock = true;
                    if (array_key_exists('product_stock', $fields)) {
                        $strStock = false;
                        $stock = $child->getAvailableStock();
                        $this->stock = $this->stock + $stock;
                        if ($stock > 0) {
                            $strStock = true;
                        }
                    }
                    if (!$strListing && $strStock) {
                        $fieldsConfigValues = $this->varaintenValue(
                            $fieldsconfig,
                            $config,
                            $child,
                            $languageId,
                            $self
                        );
                        foreach ($fieldsConfigValues as $fieldsItem) {
                            if (array_key_exists('indexName', $fieldsItem)) {
                                $indexName = $fieldsItem['indexName'];
                                $fields['children'][$index][$indexName] = $fieldsItem['value'];
                            }
                        }
                        $fields['children'][$index]['product_id'] = $child->getId();
                        $fields['children'][$index]['price'] = $child->getPrice();
                        $heandlerFixurl->fixMediaUrl($child, $urlGenerator, $config);
                        $cover = $child->getCover();
                        if ($cover === null) {
                            $cover = $entitie->getCover();
                        }
                        $fields['children'][$index]['cover'] = $cover;
                        $sortvalue = $child->getSortedProperties();
                        $heandlerkeyword->insertCustomSearchkey($config, $child, $fields['children'][$index]);
                        if ($sortvalue !== null) {
                            foreach ($sortvalue->getElements() as $prop) {
                                $sorted->add($prop);
                                foreach ($prop->getOptions()->getElements() as $key => $op) {
                                    $optionen[$key] = $op;
                                    $propName = $prop->getName();
                                    $optname = $op->getName();
                                    $fields['children'][$index]['properties'][] = [
                                        'option_id' => $op->getId(),
                                        'option_name' => $optname,
                                        'property_group' => $propName,
                                        'property_id' => $prop->getId(),
                                    ];
                                }
                            }
                        }
                        foreach ($child->getPrice()->getElements() as $price) {
                            $pricecolltion->add($price);
                        }

                        foreach ($sorted->getElements() as &$sortedItem) {
                            $option = $sortedItem->getOptions()->getElements();
                            foreach ($option as $optionitem) {
                                foreach ($optionen as $rember) {
                                    if ($optionitem->getGroupId() === $rember->getGroupId()) {
                                        $sortedItem->getOptions()->add($rember);
                                    }
                                }
                            }
                        }
                        if (array_key_exists('channel', $fields)) {
                            $fields['channel']->setPrice($pricecolltion);
                            $fields['channel']->setSortedProperties($sorted);
                        }
                        $index++;
                    }
                }
            }

            $strresetChildren = true;
            if (array_key_exists('strchildren', $config)) {
                if ($config['strchildren'] === 'yes') {
                    $strresetChildren = false;
                }
            }
            if ($strresetChildren) {
                /* set empty object because too big Data */
                $emptyChildren = new ProductCollection();
                $entitie->setChildren($emptyChildren);
            }
        }
        // set the varinat cover image when the cover image is null from the main produkt
        if ($entitie->getParentId() === null && $entitie->getCover() === null) {
            $children = $entitie->getChildren();
            if ($children !== null) {
                $firstChildren = $children->first();
                if ($firstChildren !== null) {
                    $cover = $firstChildren->getCover();
                    if ($cover !== null) {
                        $entitie->setCover($cover);
                    }
                }
            }
        }
    }

    /**
     * @param ContainerInterface $container
     * @param string $productId
     * @param SalesChannelContext $context
     * @param int $setpSize
     * @param int $offset
     * @return EntitySearchResult<SalesChannelProductEntity>
     **/
    private function getChildren(ContainerInterface $container, string $productId, SalesChannelContext $context, int $setpSize, int $offset)
    {
        $criteria = new Criteria();
        $fieldsService = $container->get('sales_channel.product.repository');
        $criteria->addFilter(new EqualsFilter('parentId', $productId));
        $criteria->addAssociation('properties.translations');
        $criteria->addAssociation('properties.group');
        $criteria->addAssociation('properties.group.translations');
        $criteria->addAssociation('cover');
        $criteria->addAssociation('translations');
        $criteria->setLimit($setpSize);
        $criteria->setOffset($offset);
        return $fieldsService->search($criteria, $context);
    }

    /**
     * @param array $fieldsconfig
     * @param array $config
     * @param SalesChannelProductEntity $child
     * @param InsertService $self
     * @return array
     */
    private function varaintenValue($fieldsconfig, $config, $child, $languageId, $self)
    {
        $heandlerExtendSearch = new ExtSearchService();
        $haendlerTranslation = new TranslationService();
        $heandler = new ExtendInsertService();
        $index = 0;
        $return = [];
        foreach ($fieldsconfig as $mappingValue) {
            $name = 'get' . ucfirst($mappingValue->getName());
            $value = $child->$name();
            $value = $heandler->getValueFromDefaultLanguage($name, $child, $value);
            $translations = $child->getTranslations();
            if (!empty($translations)) {
                $translation = $haendlerTranslation->getTranslationfields($translations, strtolower($languageId), $config);
                if ($translation) {
                    if (method_exists($translation, $name)) {
                        $newvalue = $translation->$name();
                        if (!empty($newvalue)) {
                            $value = $newvalue;
                        }
                    }
                }
            }
            $value = $self->removeSpecialCharacters($value, $mappingValue);
            $value = $heandlerExtendSearch->stripUrl($value, $config);
            $indexName = $mappingValue->getPrefix() . 'product_' . $mappingValue->getName();
            $return[$index] = [
                'value' => $value,
                'indexName' => $indexName,
            ];
            $index++;
        }
        return $return;
    }

    public function getMappingProductsValues(ContainerInterface $container, Criteria $criteriaForFields): array
    {
        $heandlerContext = new ContextService();
        $context = $heandlerContext->getContext();
        $fieldsService = $container->get('s_plugin_sisi_search_es_fields.repository');
        $criteriaForFields->addFilter(new EqualsFilter('tablename', 'product'));
        $criteriaForFields->addFilter(new EqualsFilter('onlymain', 'variante'));
        return $fieldsService->search($criteriaForFields, $context)->getEntities()->getElements();
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     *
     */
    public function changeQueryForvariantssearch(
        array &$params,
        string $term,
        $fieldsconfig,
        array $config,
        bool $kindfilter
    ): void {
        $highlight = [];
        $shouldInner = [];
        $newTerm = $term;
        if ($this->conditionFunction($config)) {
            foreach ($fieldsconfig as $row) {
                if ($row->getOnlymain() === 'variante' && $row->getTablename() === 'product') {
                    $name = 'children.' . $row->getPrefix() . 'product_' . $row->getName();
                    $sonderValues = $row->getPhpfilter();
                    $sonderValuesArray = explode("\n", $sonderValues);
                    if (count($sonderValuesArray) > 0) {
                        foreach ($sonderValuesArray as $sonderValueitem) {
                            $newTerm = str_replace($sonderValueitem, "", $term);
                        }
                    }
                    $query['query'] = $newTerm;
                    $boost = $row->getBooster();
                    if (!empty($boost)) {
                        $query["boost"] = $boost;
                    } else {
                        unset($query["boost"]);
                    }
                    $fuzziness = $row->getFuzzy();
                    if (!empty($fuzziness)) {
                        $query["fuzziness"] = $fuzziness;
                    } else {
                        unset($query["fuzziness"]);
                    }
                    $operator = $row->getOperator();
                    if (!empty($operator)) {
                        $query["operator"] = $operator;
                    } else {
                        unset($query["operator"]);
                    }
                    $shouldInner[] = [
                        'match' => [
                            $name => $query,
                        ],
                    ];
                    $highlight[$name] = new \stdClass();
                }
            }
        }
        if (count($shouldInner) > 0) {
            $should = [
                'nested' => [
                    'path' => 'children',
                    "query" => [
                        'bool' => [
                            'should' => $shouldInner,
                        ],
                    ],
                    'inner_hits' => [
                        'highlight' => [
                            'fields' => $highlight,
                        ],
                    ],
                ],
            ];
            if (array_key_exists('fragmentsize', $config)) {
                $should['nested']['inner_hits']['highlight']['fragment_size'] = $config['fragmentsize'];
            }
            $boolkey = array_key_first($params['body']);
            $bool = ($params['body'][$boolkey]);
            $firstKey = array_key_first($bool);
            $secondKey = array_key_first($bool[$firstKey]);
            if (is_array($bool[$firstKey][$secondKey]) && $kindfilter === false) {
                $params['body']['query'][$firstKey][$secondKey][] = $should;
            }
            if (is_array($bool[$firstKey][$secondKey]) && ($kindfilter)) {
                $first = array_key_first($params['body']['query'][$firstKey][$secondKey]);
                $second = array_key_first($params['body']['query'][$firstKey][$secondKey][$first]);
                $three = array_key_first($params['body']['query'][$firstKey][$secondKey][$first][$second]);
                $params['body']['query']['bool']['must'][$first][$second][$three][] = $should;
            }
        }
    }

    /**
     * @param array $values
     * @return void
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function setCoverAndUrl(array &$values): void
    {
        $productId = null;
        $cover = null;
        $merker = [];
        $price = null;
        foreach ($values['hits']['hits'] as &$value) {
            if (is_array($value)) {
                $productId = null;
                $cover = null;
                if (array_key_exists('inner_hits', $value)) {
                    $hits = $value['inner_hits']['children']['hits']['hits'];
                    $firsthit = array_shift($hits);
                    if ($firsthit !== null) {
                        if (array_key_exists("product_id", $firsthit["_source"])) {
                            $productId = $firsthit["_source"]["product_id"];
                        }
                        if (array_key_exists("productId", $firsthit["_source"])) {
                            $productId = $firsthit["_source"]["productId"];
                        }
                        if (array_key_exists("cover", $firsthit["_source"])) {
                            $cover = $firsthit["_source"]["cover"];
                        }
                        if (array_key_exists("price", $firsthit["_source"])) {
                            $price = $firsthit["_source"]["price"];
                        }
                    }
                    if ($productId !== null && !in_array($productId, $merker)) {
                        $value['_source']['channel']['id'] = $productId;
                        $value['_source']['_id'] = $productId;
                        $value['_source']['id'] = $productId;
                        if ($cover !== null) {
                            $value['_source']['channel']['cover'] = $cover;
                        }
                        if ($price !== null) {
                            $value['_source']['channel']['price'] = $price;
                        }
                        $merker[] = $productId;
                    }
                }
            }
        }
    }
    public function conditionFunction(array $config): bool
    {

        if (array_key_exists('onlymain', $config) && array_key_exists('addVariants', $config)) {
            if (($config['onlymain'] === 'yes' || $config['onlymain'] === 'stock') && ($config['addVariants'] === 'yes' || $config['addVariants'] === 'individual')) {
                return true;
            }
        }
        return false;
    }
}
