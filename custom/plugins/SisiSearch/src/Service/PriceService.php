<?php

namespace Sisi\Search\Service;

use Shopware\Core\Content\Product\Aggregate\ProductPrice\ProductPriceCollection;
use Shopware\Core\Content\Product\Aggregate\ProductPrice\ProductPriceEntity;
use Shopware\Core\Content\Product\DataAbstractionLayer\CheapestPrice\CheapestPrice;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Product\SalesChannel\Price\AbstractProductPriceCalculator;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Sisi\Search\Core\Content\Fields\Bundle\DBFieldsEntity;
use Elasticsearch\Client;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\PriceCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\Price;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Sisi\Search\Decorater\SisiProductPriceCalculator;

/**
*
 */
class PriceService
{
    /**
     * @param SalesChannelProductEntity $entitie
     * @param array $fields
     * @return void
     */
    public function insertPrice(SalesChannelProductEntity $entitie, array &$fields)
    {
        $priceEntiy = $entitie->getprice();
        foreach ($priceEntiy->getElements() as $element) {
            $priceNet = $element->getNet();
            if (!empty($priceNet) && $priceNet != null) {
                $fields['product_priceNet'] = $priceNet;
            }
            $priceGross = $element->getGross();
            if (!empty($priceNet) && $priceNet != null) {
                $fields['product_priceGross'] = $priceGross;
            }
        }
        $fields['product_ratingAverage'] = $entitie->getRatingAverage();
    }

    /**
     * @param array $values
     * @param array $config
     * @param SalesChannelContext $saleschannelContext
     * @return void
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function caluteteAllPrices(array &$values, array $config, SalesChannelContext $saleschannelContext)
    {
        $sisiProductPriceCalculator = null;
        if (array_key_exists('calculatedprices', $config)) {
            if (array_key_exists('sisiProductPriceCalculator', $config)) {
                $sisiProductPriceCalculator = $config['sisiProductPriceCalculator'];
            }
            if ($config['calculatedprices'] === '1') {
                $sisiProductPriceCalculator = null;
                if ($sisiProductPriceCalculator !== null) {
                    $units = $sisiProductPriceCalculator->getUnits($saleschannelContext);
                    $index = 0;
                    foreach ($values['hits']['hits'] as &$value) {
                        $produktchannelitem = new SalesChannelProductEntity();
                        $priceitem = $value['_source']['channel']['price'];
                        $priceCollection = new PriceCollection();
                        foreach ($priceitem as $price) {
                            $priceObject = new  Price(
                                $price["currencyId"],
                                $price["net"],
                                $price["gross"],
                                $price["linked"]
                            );
                            $priceCollection->add($priceObject);
                        }
                        $produktchannelitem->setPrice($priceCollection);
                        $produktchannelitem->setUnitId($value['_source']['channel']['unitId']);
                        $taxid = $value['_source']['channel']['taxId'];
                        $produktchannelitem->setTaxId($taxid);
                        $prices = $value['_source']['channel']['prices'];
                        $productpriceCollection = new ProductPriceCollection();
                        foreach ($prices as $priceitem) {
                            $entity = new ProductPriceEntity();
                            $entity->setProductId($priceitem['productId']);
                            $entity->setQuantityStart($priceitem['quantityStart']);
                            $entity->setQuantityEnd($priceitem['quantityEnd']);
                            $entity->setId($priceitem['id']);
                            $entity->setRuleId($priceitem['ruleId']);
                            $priceCollection = new PriceCollection();
                            foreach ($priceitem["price"] as $price) {
                                $priceObject = new  Price(
                                    $price["currencyId"],
                                    $price["net"],
                                    $price["gross"],
                                    $price["linked"]
                                );
                                $priceCollection->add($priceObject);
                            }
                            $entity->setPrice($priceCollection);
                            $productpriceCollection->add($entity);
                        }
                        $produktchannelitem->setPrices($productpriceCollection);
                        $cheapestPrice = $sisiProductPriceCalculator->findtheMinValue(
                            $value['_source']['channel']['cheapestPrice'],
                            $saleschannelContext
                        );
                        if (count($cheapestPrice) > 0) {
                            $cheapestPriceObcect = new CheapestPrice();
                            $cheapestPriceObcect->setVariantId($cheapestPrice["variant_id"]);
                            $cheapestPriceObcect->setParentId($cheapestPrice["parent_id"]);
                            $cheapestPriceObcect->setUnitId($cheapestPrice["unit_id"]);
                            $cheapestPriceObcect->setHasRange($cheapestPrice["is_ranged"]);
                            $priceCollection = new PriceCollection();
                            foreach ($cheapestPrice["price"] as $price) {
                                $priceObject = new  Price(
                                    $price["currencyId"],
                                    $price["net"],
                                    $price["gross"],
                                    $price["linked"]
                                );
                                $priceCollection->add($priceObject);
                            }
                            $cheapestPriceObcect->setPrice($priceCollection);
                            $produktchannelitem->setCheapestPrice($cheapestPriceObcect);
                            $sisiProductPriceCalculator->calculateCheapestPrice(
                                $produktchannelitem,
                                $saleschannelContext,
                                $units
                            );
                            $value['_source']['channel']['cheapestPrice'] = $cheapestPrice;
                            $value['_source']['channel']['calculatedCheapestPrice'] = $produktchannelitem->getCalculatedCheapestPrice();
                        }
                        $sisiProductPriceCalculator->calculatePrice($produktchannelitem, $saleschannelContext, $units);
                        $sisiProductPriceCalculator->calculateAdvancePrices(
                            $produktchannelitem,
                            $saleschannelContext,
                            $units
                        );
                        $value['_source']['channel']['calculatedPrice'] = $produktchannelitem->getCalculatedPrice();
                        $value['_source']['channel']['calculatedPrices'] = $produktchannelitem->getCalculatedPrices();
                        $index++;
                    }
                }
            }
            if ($sisiProductPriceCalculator !== null) {
                $this->calculteOrginalprices($values, $config, $saleschannelContext, $sisiProductPriceCalculator);
            }
        }
    }

    public function calculteOrginalprices(array &$results, array $config, SalesChannelContext $saleschannelContext, SisiProductPriceCalculator $sisiProductPriceCalculator): void
    {
        if ($config['calculatedprices'] === '3') {
            $productCollection = new ProductCollection();
            foreach ($results['hits']['hits'] as &$value) {
                $priceitem = $value['_source']['channel']['price'];
                $priceCollection = new \Shopware\Core\Framework\DataAbstractionLayer\Pricing\PriceCollection();
                $produktchannelitem = new SalesChannelProductEntity();
                foreach ($priceitem as $price) {
                    $priceObject = new  Price(
                        $price["currencyId"],
                        $price["net"],
                        $price["gross"],
                        $price["linked"]
                    );
                    $priceCollection->add($priceObject);
                }
                $produktchannelitem->setPrice($priceCollection);
                $produktchannelitem->setUnitId($value['_source']['channel']['unitId']);
                $produktchannelitem->setId($value['_source']['channel']['id']);
                $produktchannelitem->setProductNumber($value['_source']['channel']['productNumber']);
                $taxid = $value['_source']['channel']['taxId'];
                $produktchannelitem->setTaxId($taxid);
                $prices = $value['_source']['channel']['prices'];
                $productpriceCollection = new ProductPriceCollection();
                foreach ($prices as $priceitem) {
                    $entity = new ProductPriceEntity();
                    $entity->setProductId($priceitem['productId']);
                    $entity->setQuantityStart($priceitem['quantityStart']);
                    $entity->setQuantityEnd($priceitem['quantityEnd']);
                    $entity->setId($priceitem['id']);
                    $entity->setRuleId($priceitem['ruleId']);
                    $priceCollection = new PriceCollection();
                    foreach ($priceitem["price"] as $price) {
                        $priceObject = new  Price(
                            $price["currencyId"],
                            $price["net"],
                            $price["gross"],
                            $price["linked"]
                        );
                        $priceCollection->add($priceObject);
                    }
                    $entity->setPrice($priceCollection);
                    $productpriceCollection->add($entity);
                }
                $produktchannelitem->setPrices($productpriceCollection);
                $productCollection->add($produktchannelitem);
            }
            $sisiProductPriceCalculator->getOrginalDecorater()->calculate($productCollection, $saleschannelContext);
            foreach ($results['hits']['hits'] as &$value) {
                $item = $productCollection->filterByProperty('id', $value['_id'])->getElements();
                $item = array_shift($item);
                if ($item !== null) {
                    $value['_source']['channel']['calculatedPrices'] = $item->getCalculatedPrices();
                    $value['_source']['channel']['calculatedPrice'] = $item->getCalculatedPrice();
                    $value['_source']['channel']['calculatedCheapestPrice'] = $item->getCalculatedCheapestPrice();
                }
            }
        }
    }
}
