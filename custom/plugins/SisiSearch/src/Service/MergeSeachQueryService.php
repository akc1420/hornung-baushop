<?php

namespace Sisi\Search\Service;

use Shopware\Core\Checkout\Cart\Price\QuantityPriceCalculator;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Content\Media\Aggregate\MediaThumbnail\MediaThumbnailEntity;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerEntity;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturerTranslation\ProductManufacturerTranslationCollection;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturerTranslation\ProductManufacturerTranslationEntity;
use Shopware\Core\Content\Product\Aggregate\ProductMedia\ProductMediaEntity;
use Shopware\Core\Content\Product\Aggregate\ProductPrice\ProductPriceCollection;
use Shopware\Core\Content\Product\Aggregate\ProductPrice\ProductPriceEntity;
use Shopware\Core\Content\Product\Aggregate\ProductTranslation\ProductTranslationCollection;
use Shopware\Core\Content\Product\Aggregate\ProductTranslation\ProductTranslationEntity;
use Shopware\Core\Content\Product\DataAbstractionLayer\CheapestPrice\CalculatedCheapestPrice;
use Shopware\Core\Content\Product\DataAbstractionLayer\CheapestPrice\CheapestPrice;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Product\SalesChannel\Price\ProductPriceCalculator;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionCollection;
use Shopware\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionEntity;
use Shopware\Core\Content\Property\Aggregate\PropertyGroupOptionTranslation\PropertyGroupOptionTranslationCollection;
use Shopware\Core\Content\Property\Aggregate\PropertyGroupOptionTranslation\PropertyGroupOptionTranslationEntity;
use Shopware\Core\Content\Property\PropertyGroupCollection;
use Shopware\Core\Content\Property\PropertyGroupEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\Price;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\AggregationResultCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepositoryInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\PriceCollection;
use Shopware\Core\Content\Product\AbstractPropertyGroupSorter;
use Shopware\Core\Content\Media\Aggregate\MediaThumbnail\MediaThumbnailCollection;
use Shopware\Core\Checkout\Cart\Price\Struct\PriceCollection as CheckoutPriceCollection;
use Sisi\Search\Decorater\SisiProductPriceCalculator;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MergeSeachQueryService
{
    /**
     * @var SisiProductPriceCalculator
     */
    private $sisiProductPriceCalculator;


    public function __construct(SisiProductPriceCalculator $sisiProductPriceCalculator)
    {
        $this->sisiProductPriceCalculator = $sisiProductPriceCalculator;
    }

    public function selectedKindOfQueryResult(
        ProductService $poductservice,
        SalesChannelRepositoryInterface $productService,
        Criteria $criteria,
        array $newResult,
        SalesChannelContext $context,
        array $config
    ) {
        if (array_key_exists('outputkind', $config)) {
            if ($config['outputkind'] === '1' || $config['outputkind'] === '3') {
                return $this->mergeEntitySearchResult(
                    $newResult,
                    new AggregationResultCollection(),
                    $criteria,
                    $context,
                    $config
                );
            }
        }
        $sortservice = new SortingService();
        $criteria = $poductservice->searchProducte($criteria, $newResult['hits']['hits']);
        $entities = $productService->search($criteria, $context);
        $sortservice->sortDbQueryToES($entities, $newResult['hits']['hits']);
        return $entities;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function mergeEntitySearchResult(
        array $esResult,
        $aggregations,
        $criteria,
        SalesChannelContext $salesChannelContext,
        array $config
    ) {
        $entities = new  ProductCollection();
        $heandlerContext = new ContextService();
        $context = $heandlerContext->getContext();
        $propertyGroupSorter = $config['propertyGroupSorter'];
        $strShopwarePrices = false;
        $units = null;
        if ($config['outputkind'] === '3') {
            $strShopwarePrices = true;
        }
        if ($strShopwarePrices === false) {
            $units = $this->sisiProductPriceCalculator->getUnits($salesChannelContext);
        }
        foreach ($esResult['hits']['hits'] as $key => &$hit) {
            $produktchannelitem = new SalesChannelProductEntity();

            if (array_key_exists('_id', $hit)) {
                $produktchannelitem->setId($hit['_id']);
            }
            if (array_key_exists('id', $hit['_source']['channel'])) {
                if ($hit['_source']['channel']['id'] !== null) {
                    $produktchannelitem->setId($hit['_source']['channel']['id']);
                }
            }
            if ($hit['_source']['channel']['productNumber'] !== null) {
                $produktchannelitem->setProductNumber($hit['_source']['channel']['productNumber']);
            }

            $produktchannelitem->setCalculatedMaxPurchase(0);
            $produktchannelitem->setProductNumber($hit['_source']['channel']['productNumber']);
            $produktchannelitem->setName($hit['_source']['channel']['name']);
            $produktchannelitem->setActive($hit['_source']['channel']['active']);
            $produktchannelitem->setPropertyIds($hit['_source']['channel']['propertyIds']);
            $produktchannelitem->setCategoryTree($hit['_source']['channel']['categoryTree']);
            $produktchannelitem->setStreamIds($hit['_source']['channel']['streamIds']);
            $produktchannelitem->setWeight($hit['_source']['channel']['weight']);

            if (array_key_exists('minPurchase', $hit['_source']['channel'])) {
                $produktchannelitem->setMinPurchase(($hit['_source']['channel']['minPurchase']));
            }
            if ($hit['_source']['channel']['translations'] !== null) {
                $translation = $this->mergeProductTranslation($hit['_source']['channel']['translations']);
                $produktchannelitem->setTranslations($translation);
            }
            if ($hit['_source']['channel']['translated'] !== null) {
                $produktchannelitem->setTranslated($hit['_source']['channel']['translated']);
            }
            $taxid = $hit['_source']['channel']['taxId'];
            $produktchannelitem->setTaxId($taxid);
            if ($hit['_source']['channel']['coverId'] !== null) {
                $produktchannelitem->setCoverId($hit['_source']['channel']['coverId']);
            }
            $produktchannelitem->setRatingAverage($hit['_source']['channel']['ratingAverage']);
            $produktchannelitem->setEan($hit['_source']['channel']['ean']);
            if ($hit['_source']['channel']['sales'] !== null) {
                $produktchannelitem->setSales($hit['_source']['channel']['sales']);
            }
            $produktchannelitem->setActive($hit['_source']['channel']['active']);
            if ($hit['_source']['channel']['stock'] !== null) {
                $produktchannelitem->setStock($hit['_source']['channel']['stock']);
            }
            $produktchannelitem->setDescription($hit['_source']['channel']['description']);
            $produktchannelitem->setMetaDescription($hit['_source']['channel']['metaDescription']);
            $produktchannelitem->setMarkAsTopseller($hit['_source']['channel']['markAsTopseller']);
            if ($hit['_source']['channel']['isNew'] !== null) {
                $produktchannelitem->setIsNew($hit['_source']['channel']['isNew']);
            }
            if ($hit['_source']['channel']['properties'] !== null) {
                $properties = $this->mergeProberties($hit['_source']['channel']['properties']);
                $produktchannelitem->setProperties($properties);
            }
            if ($hit['_source']['channel']['availableStock'] !== null) {
                $produktchannelitem->setAvailableStock($hit['_source']['channel']['availableStock']);
            }
            $produktchannelitem->setMainVariantId($hit['_source']['channel']['mainVariantId']);
            if ($hit['_source']['channel']['variation'] !== null) {
                $produktchannelitem->setVariation($hit['_source']['channel']['variation']);
            }
            if ($hit['_source']['channel']['childCount'] !== null) {
                $produktchannelitem->setChildCount($hit['_source']['channel']['childCount']);
            }
            $produktchannelitem->setDisplayGroup($hit['_source']['channel']['displayGroup']);
            if ($hit['_source']['channel']['sortedProperties'] !== null) {
                $sortedPropeties = $this->extendSortedProperty($hit['_source']['channel']['sortedProperties']);
                $produktchannelitem->setSortedProperties($sortedPropeties);
            }
            if ($hit['_source']['channel']['sortedProperties'] === null) {
                $newPropertie = $hit['_source']['properties'];
                $sortedProperties = $propertyGroupSorter->sort($newPropertie);
                $produktchannelitem->setSortedProperties($sortedProperties);
            }

            $cover = $this->mergeCover($hit['_source']['channel']['cover']);
            $produktchannelitem->setCover($cover);


            if ($hit['_source']['channel']['purchaseUnit'] !== null) {
                $produktchannelitem->setPurchaseUnit($hit['_source']['channel']['purchaseUnit']);
            }

            if ($hit['_source']['channel']['referenceUnit'] !== null) {
                $produktchannelitem->setReferenceUnit((float) $hit['_source']['channel']['referenceUnit']);
            }

            if ($hit['_source']['channel']['unitId'] !== null) {
                $produktchannelitem->setUnitId($hit['_source']['channel']['unitId']);
            }

            if ($hit['_source']['channel']['manufacturer'] !== null) {
                $manufactorer = $this->mergeManufacturerEntity($hit['_source']['channel']['manufacturer']);
                $produktchannelitem->setManufacturer($manufactorer);
            }
            $priceCollection = new PriceCollection();
            if ($hit['_source']['channel']['purchasePrices'] !== null) {
                foreach ($hit['_source']['channel']['purchasePrices'] as $price) {
                    $priceObject = new  Price(
                        $price["currencyId"],
                        $price["net"],
                        $price["gross"],
                        $price["linked"]
                    );
                    $priceCollection->add($priceObject);
                }
            }
            $produktchannelitem->setPurchasePrices($priceCollection);
            $priceCollection = new PriceCollection();
            if ($hit['_source']['channel']['price'] !== null) {
                foreach ($hit['_source']['channel']['price'] as $price) {
                    $priceObject = new  Price(
                        $price["currencyId"],
                        $price["net"],
                        $price["gross"],
                        $price["linked"]
                    );
                    $priceCollection->add($priceObject);
                }
            }
            if ($hit['_source']['channel']['price'] == null) {
                $currencyId = $salesChannelContext->getCurrencyId();
                $priceObject = new Price(
                    $currencyId,
                    0,
                    0,
                    ''
                );
                $priceCollection->add($priceObject);
            }

            $produktchannelitem->setPrice($priceCollection);
            if ($strShopwarePrices === false) {
                $this->sisiProductPriceCalculator->calculatePrice($produktchannelitem, $salesChannelContext, $units);
            }
            $cheapestPrice = new CheapestPrice();
            $cheapestPrice->setPrice(new PriceCollection());
            $cheapespriceitem = $hit['_source']['channel']['cheapestPrice'];
            if ($cheapespriceitem !== null) {
                if (array_key_exists('value', $hit['_source']['channel']['cheapestPrice'])) {
                    $cheapespriceitems = array_shift($hit['_source']['channel']['cheapestPrice']['value']);
                    if ($cheapespriceitems !== null) {
                        foreach ($cheapespriceitems as $cheapespriceitem) {
                            if ($cheapespriceitem !== null) {
                                $this->setPrices($cheapestPrice, $cheapespriceitem);
                            }
                        }
                    }
                } else {
                    $this->setPrices($cheapestPrice, $cheapespriceitem);
                }
                $produktchannelitem->setCheapestPrice($cheapestPrice);
            }
            if ($hit['_source']['channel']['prices'] !== null) {
                $productPrice = new ProductPriceCollection();
                foreach ($hit['_source']['channel']['prices'] as $productprice) {
                    $entity = new ProductPriceEntity();
                    $entity->setUniqueIdentifier($productprice['_uniqueIdentifier']);
                    if ($productprice['productId'] !== null) {
                        $entity->setProductId($productprice['productId']);
                    }
                    $entity->setQuantityStart($productprice['quantityStart']);
                    $entity->setQuantityEnd($productprice['quantityEnd']);
                    $entity->setRuleId($productprice['ruleId']);
                    $priceCollection = new PriceCollection();
                    foreach ($productprice['price'] as $priceitem) {
                        $priceObject = new  Price(
                            $priceitem["currencyId"],
                            $priceitem["net"],
                            $priceitem["gross"],
                            $priceitem["linked"]
                        );
                        $priceCollection->add($priceObject);
                    }
                    $entity->setPrice($priceCollection);
                    $productPrice->add($entity);
                }
                $produktchannelitem->setPrices($productPrice);
            }


            if ($strShopwarePrices === false) {
                if (count($produktchannelitem->getCheapestPrice()->getPrice()->getElements()) > 0) {
                    $this->sisiProductPriceCalculator->calculateAdvancePrices(
                        $produktchannelitem,
                        $salesChannelContext,
                        $units
                    );
                }

                $this->sisiProductPriceCalculator->calculateCheapestPrice(
                    $produktchannelitem,
                    $salesChannelContext,
                    $units
                );
                $priceCollection = new CheckoutPriceCollection();
                foreach ($hit['_source']['channel']['calculatedPrices'] as $price) {
                    $priceObject = new  Price(
                        $price["currencyId"],
                        $price["net"],
                        $price["gross"],
                        $price["linked"]
                    );
                    $priceCollection->add($priceObject);
                }

                $produktchannelitem->setCalculatedPrices($priceCollection);
            }
            $entities->add($produktchannelitem);
        }
        if ($strShopwarePrices) {
            $this->sisiProductPriceCalculator->getOrginalDecorater()->calculate($entities, $salesChannelContext);
        }

        return new EntitySearchResult(
            "product",
            $esResult['hits']['total']['value'],
            $entities,
            $aggregations,
            $criteria,
            $context
        );
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function setPrices(CheapestPrice &$cheapestPrice, array $cheapespriceitem): void
    {
        if (array_key_exists('hasRange', $cheapespriceitem)) {
            $cheapestPrice->setHasRange($cheapespriceitem['hasRange']);
        }

        if (array_key_exists('is_ranged', $cheapespriceitem)) {
            $cheapestPrice->setHasRange($cheapespriceitem['is_ranged']);
        }

        if (array_key_exists('variantId', $cheapespriceitem)) {
            $cheapestPrice->setVariantId($cheapespriceitem['variantId']);
        }
        if (array_key_exists('variant_id', $cheapespriceitem)) {
            $cheapestPrice->setVariantId($cheapespriceitem['variant_id']);
        }
        if (array_key_exists('parentId', $cheapespriceitem)) {
            $cheapestPrice->setParentId($cheapespriceitem['parentId']);
        }
        if (array_key_exists('parent_id', $cheapespriceitem)) {
            $cheapestPrice->setParentId($cheapespriceitem['parent_id']);
        }
        if (array_key_exists('ruleId', $cheapespriceitem)) {
            $cheapestPrice->setRuleId($cheapespriceitem['ruleId']);
        }
        if (array_key_exists('rule_id', $cheapespriceitem)) {
            $cheapestPrice->setRuleId($cheapespriceitem['rule_id']);
        }
        $priceCollection = new PriceCollection();
        if (array_key_exists('price', $cheapespriceitem)) {
            foreach ($cheapespriceitem['price'] as $price) {
                $priceObject = new  Price(
                    $price["currencyId"],
                    $price["net"],
                    $price["gross"],
                    $price["linked"]
                );
                $priceCollection->add($priceObject);
            }
        }
        $cheapestPrice->setPrice($priceCollection);
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function mergeCover(?array $coverArray): ProductMediaEntity
    {
        $cover = new ProductMediaEntity();
        if ($coverArray !== null) {
            if ($coverArray['productId'] !== null) {
                $cover->setProductId($coverArray['productId']);
            }
            $cover->setMediaId($coverArray['mediaId']);
            $cover->setUniqueIdentifier($coverArray['_uniqueIdentifier']);
            $cover->setVersionId($coverArray['versionId']);
            $cover->setId($coverArray['id']);
            $media = new MediaEntity();
            $media->setId($coverArray['media']['id']);
            if ($coverArray['media']['userId'] !== null) {
                $media->setUserId($coverArray['media']['userId']);
            }
            if ($coverArray['media']['mimeType'] !== null) {
                $media->setMimeType($coverArray['media']['mimeType']);
            }
            $media->setUniqueIdentifier($coverArray['media']['_uniqueIdentifier']);
            if ($coverArray['media']['translated'] !== null) {
                $media->setTranslated($coverArray['media']['translated']);
            }
            if ($coverArray['media']['fileExtension'] !== null) {
                $media->setFileExtension($coverArray['media']['fileExtension']);
            }
            if ($coverArray['media']['alt'] !== null) {
                $media->setAlt($coverArray['media']['alt']);
            }
            if ($coverArray['media']['title'] !== null) {
                $media->setTitle($coverArray['media']['title']);
            }

            if ($coverArray['media']['url'] !== null) {
                $media->setUrl($coverArray['media']['url']);
            }

            if ($coverArray['media']['fileName'] !== null) {
                $media->setFileName($coverArray['media']['fileName']);
            }

            if ($coverArray['media']['fileSize'] !== null) {
                $media->setFileSize($coverArray['media']['fileSize']);
            }

            if ($coverArray['media']['mediaFolderId'] !== null) {
                $media->setMediaFolderId($coverArray['media']['mediaFolderId']);
            }
            if ($coverArray['media']['metaData'] !== null) {
                $media->setMetaData($coverArray['media']['metaData']);
            }
            if ($coverArray['media']['thumbnails'] !== null) {
                $thumbCollection = new MediaThumbnailCollection();
                foreach ($coverArray['media']['thumbnails'] as $thumbnail) {
                    $thumbItem = new MediaThumbnailEntity();
                    $thumbItem->setWidth($thumbnail['width']);
                    $thumbItem->setHeight($thumbnail['height']);
                    $thumbItem->setUrl($thumbnail['url']);
                    $thumbItem->setMediaId($thumbnail['mediaId']);
                    $thumbItem->setId($thumbnail['id']);
                    $thumbItem->setTranslated($thumbnail['translated']);
                    $thumbCollection->add($thumbItem);
                }
                $media->setThumbnails($thumbCollection);
            }
            $cover->setMedia($media);
        }
        return $cover;
    }

    public function mergeProberties(array $proberties): PropertyGroupOptionCollection
    {
        $probertieColletion = new PropertyGroupOptionCollection();
        foreach ($proberties as $proberty) {
            $entity = new PropertyGroupOptionEntity();
            $entity->setName($proberty['name']);
            $entity->setGroupId($proberty['groupId']);
            $entity->setPosition($proberty['position']);
            $entity->setUniqueIdentifier($proberty['_uniqueIdentifier']);
            $group = $this->mergePropertyGroupEntity($proberty['group']);
            $entity->setGroup($group);
            $translation = $this->mergePropertyGroupOptionTranslationCollection($proberty['translations']);
            $entity->setTranslations($translation);
            $entity->setTranslated($proberty['translated']);
            $probertieColletion->add($entity);
        }
        return $probertieColletion;
    }

    private function mergePropertyGroupEntity(array $groupvalue)
    {
        $group = new PropertyGroupEntity();
        $group->setName($groupvalue['position']);
        $group->setUniqueIdentifier($groupvalue['_uniqueIdentifier']);
        $group->setPosition($groupvalue['position']);
        $group->setDescription($groupvalue['description']);
        $group->setDisplayType($groupvalue['displayType']);
        $group->setSortingType($groupvalue['sortingType']);
        return $group;
    }

    private function mergePropertyGroupOptionTranslationCollection(?array $translations): ?PropertyGroupOptionTranslationCollection
    {
        $translation = new PropertyGroupOptionTranslationCollection();
        if ($translations !== null) {
            foreach ($translations as $transitem) {
                $item = new PropertyGroupOptionTranslationEntity();
                $item->setName($transitem['name']);
                $item->setPosition($transitem['position']);
                $item->setUniqueIdentifier($transitem['_uniqueIdentifier']);
                $item->setLanguageId($transitem['languageId']);
                $translation->add($item);
            }
        }
        return $translation;
    }

    private function extendSortedProperty(array $proberties): PropertyGroupCollection
    {
        $probertieColletion = new PropertyGroupCollection();
        foreach ($proberties as $proberty) {
            $entity = new PropertyGroupEntity();
            $entity->setName($proberty['name']);
            $entity->setId($proberty['id']);
            $entity->setPosition($proberty['position']);
            $entity->setSortingType('sortingType');
            $entity->setDescription('description');
            $entity->setDisplayType($proberty['displayType']);
            $entity->setFilterable($proberty['filterable']);
            if ($proberty['options'] !== null) {
                $options = new PropertyGroupOptionCollection();
                foreach ($proberty['options'] as $option) {
                    $group = new PropertyGroupOptionEntity();
                    $group->setName($option['name']);
                    $group->setId($option['id']);
                    $group->setUniqueIdentifier($option['_uniqueIdentifier']);
                    $group->setPosition($option['position']);
                    $group->setTranslated($option['translated']);
                    $groupItem = $this->mergePropertyGroupEntity($option['group']);
                    $group->setGroup($groupItem);
                    $translation = $this->mergePropertyGroupOptionTranslationCollection($option['translations']);
                    $group->setTranslations($translation);
                    $options->add($group);
                }
                $entity->setOptions($options);
            }
            $probertieColletion->add($entity);
        }
        return $probertieColletion;
    }

    public function mergeProductTranslation(array $tranlations): ProductTranslationCollection
    {
        $return = new ProductTranslationCollection();
        foreach ($tranlations as $tranlation) {
            $translationItem = new ProductTranslationEntity();
            $translationItem->setUniqueIdentifier($tranlation['_uniqueIdentifier']);
            $translationItem->setProductId($tranlation['productId']);
            $translationItem->setMetaDescription($tranlation['metaDescription']);
            $translationItem->setName($tranlation['name']);
            $translationItem->setDescription($tranlation['description']);
            $translationItem->setKeywords($tranlation['keywords']);
            $translationItem->setMetaTitle($tranlation['metaTitle']);
            $return->add($translationItem);
        }
        return $return;
    }

    public function calculatePricesforAjaxPopup(
        array &$products,
        SalesChannelContext $salesChannelContext,
        array $config
    ): void {
        if (array_key_exists('calculatedprices', $config)) {
            if ($config['calculatedprices'] === '1') {
                $elasticsearchResults = $this->mergeEntitySearchResult(
                    $products,
                    new AggregationResultCollection(),
                    new Criteria(),
                    $salesChannelContext,
                    $config['propertyGroupSorter']
                );
                $values = $elasticsearchResults->getEntities()->first();
                foreach ($products['hits']['hits'] as &$product) {
                    $product['_source']['channel'] = $values;
                }
            }
        }
    }

    public function mergeManufacturerEntity(array $manufacturer): ProductManufacturerEntity
    {
        $entity = new ProductManufacturerEntity();
        $entity->setMediaId($manufacturer['mediaId']);
        $entity->setName($manufacturer['name']);
        $entity->setLink($manufacturer['link']);
        $entity->setDescription($manufacturer['description']);
        $entity->setId($manufacturer['id']);
        $entity->setTranslated($manufacturer['translated']);
        $entity->setVersionId($manufacturer['versionId']);
        $translations = new ProductManufacturerTranslationCollection();
        if ($translations !== null) {
            foreach ($manufacturer['translations'] as $translationsvalue) {
                $translation = new  ProductManufacturerTranslationEntity();
                $translation->setProductManufacturerId($translationsvalue['productManufacturerId']);
                $translation->setName($translationsvalue['name']);
                $translation->setDescription($translationsvalue['description']);
                $translation->setLanguageId($translationsvalue['languageId']);
                $translation->setUniqueIdentifier($translationsvalue['_uniqueIdentifier']);
                $translations->add($translation);
            }
            $entity->setTranslations($translations);
        }
        return $entity;
    }
}
