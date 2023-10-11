<?php

namespace Sisi\Search\Service;

use MyProject\Container;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerEntity;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionCollection;
use Shopware\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionEntity;
use Shopware\Core\Content\Property\Aggregate\PropertyGroupOptionTranslation\PropertyGroupOptionTranslationCollection;
use Shopware\Core\Content\Property\Aggregate\PropertyGroupOptionTranslation\PropertyGroupOptionTranslationEntity;
use Shopware\Core\Content\Property\Aggregate\PropertyGroupTranslation\PropertyGroupTranslationCollection;
use Shopware\Core\Content\Property\Aggregate\PropertyGroupTranslation\PropertyGroupTranslationEntity;
use Shopware\Core\Content\Property\PropertyGroupEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DBFieldsEntity
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SortingService
{
    public function sortDbQueryToES(EntitySearchResult &$result, array $hits): void
    {
        $entities = $result->getEntities();
        $sort = [];
        foreach ($entities as $key => $entity) {
            foreach ($hits as $index => $hit) {
                $productId = $hit["_id"];
                if (array_key_exists("id", $hit["_source"])) {
                    $productId = $hit["_source"]["id"];
                }
                if ($key === $productId) {
                    $sort[$index] = $key;
                }
            }
        }
        ksort($sort);
        $result->sortByIdArray($sort);
    }

    public function getKindofProperties(array &$config)
    {
        if (array_key_exists('extraqueryforfilters', $config)) {
            if ($config['extraqueryforfilters'] === 'yes') {
                $config['producthitsSearch'] = 500;
                if (array_key_exists('extraquerySizeforfilter', $config)) {
                    if ($config['extraquerySizeforfilter'] > 0) {
                        $config['producthitsSearch'] = $config['extraquerySizeforfilter'];
                    }
                }
                return true;
            }
        }
        return false;
    }
    /**
     *  @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function getProperties(
        EntitySearchResult $result,
        ContainerInterface $container,
        array $config,
        array $newResult,
        string $languageId,
        bool $kindproperties
    ) {

        if ($kindproperties) {
            return [
                'properties' => $this->getFiltersWithOwnQuery($newResult, $languageId, $config),
                'manufactories' => $this->getManufactoryByOwnQuery($newResult, $container, $config)
            ];
        }
        return ['properties' => $this->getProptertiesfilters($result, $container, $config),
               'manufactories' => $this->getManufactory($result, $container, $config)];
    }

    /**
     * @param array $newResult
     * @param string $languageId
     * @return array
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function getFiltersWithOwnQuery(array $newResult, string $languageId, array $config): array
    {
        $collection = [];
        $filtergrpups = [];
        $str = true;
        if (array_key_exists('filterfilter', $config)) {
            if (!empty($config['filterfilter'])) {
                $filtergrpups = explode("\n", $config['filterfilter']);
                $str = false;
            }
        }
        foreach ($newResult['hits']['hits'] as $hit) {
            $sortedProperties = $hit["_source"]['channel']['sortedProperties'];
            if (is_array($sortedProperties)) {
                foreach ($sortedProperties as $property) {
                    if ($str || in_array($property['name'], $filtergrpups)) {
                        $index = $property['name'];
                        $collection[$index][$property['name']] = new PropertyGroupEntity();
                        $collection[$index][$property['name']]->setName($property['name']);
                        $collection[$index][$property['name']]->setDisplayType($property['displayType']);
                        $collection[$index][$property['name']]->setSortingType($property['sortingType']);
                        $collection[$index][$property['name']]->setFilterable($property['filterable']);
                        $collection[$index][$property['name']]->setPosition($property['position']);
                        $collection[$index][$property['name']]->setVisibleOnProductDetailPage(
                            $property['visibleOnProductDetailPage']
                        );
                        $collection[$index][$property['name']]->setTranslated($property['translated']);
                        $collection[$index][$property['name']]->setUniqueIdentifier($property['id']);
                        $collection[$index][$property['name']]->setId($property['id']);
                        $options = new PropertyGroupOptionCollection();
                        $transCollection = new PropertyGroupTranslationCollection();
                        $translationEntity = new PropertyGroupTranslationEntity();
                        if ($property['translations'] !== null) {
                            foreach ($property['translations'] as $tranitem) {
                                $translationEntity->setLanguageId($languageId);
                                $translationEntity->setUniqueIdentifier($tranitem['position']);
                                $translationEntity->setName($tranitem['name']);
                                $transCollection->add($translationEntity);
                            }
                            $collection[$index][$property['name']]->setTranslations($transCollection);
                        }

                        $collection[$index][$property['name']]->setOptions($options);
                    }
                }
            }
        }
        $merk = [];
        foreach ($newResult['hits']['hits'] as $hit) {
            $sortedProperties = $hit["_source"]['channel']['sortedProperties'];
            if (is_array($sortedProperties)) {
                foreach ($sortedProperties as $property) {
                    if ($str || in_array($property['name'], $filtergrpups)) {
                        $optionsValues = $property['options'];
                        foreach ($optionsValues as $optionsValue) {
                            if (!in_array($optionsValue['name'], $merk)) {
                                $option = new PropertyGroupOptionEntity();
                                $transaltion = new PropertyGroupOptionTranslationCollection();
                                $group = new PropertyGroupEntity();
                                $group->setName($optionsValue['group']['name']);
                                $group->setDisplayType($optionsValue['group']['displayType']);
                                $group->setPosition($optionsValue['group']['position']);
                                $group->setSortingType($optionsValue['group']['sortingType']);
                                $group->setDescription($optionsValue['group']['description']);
                                $group->setId($optionsValue['group']['id']);
                                $group->setTranslated($optionsValue['group']['translated']);
                                $option->setId($optionsValue["id"]);
                                $option->setGroupId($optionsValue["groupId"]);
                                $option->setName($optionsValue['name']);
                                $option->setTranslated([$transaltion]);
                                $option->setMedia($optionsValue['media']);
                                $option->setMediaId($optionsValue['mediaId']);
                                $option->setPosition($optionsValue['position']);
                                $option->setGroup($group);
                                $option->setTranslated($optionsValue['translated']);
                                $transaltionsValues = $optionsValue['translations'];
                                if ($transaltionsValues !== null) {
                                    foreach ($transaltionsValues as $value) {
                                        $propertie = new PropertyGroupOptionTranslationEntity();
                                        $propertie->setName($value['name']);
                                        $propertie->setPropertyGroupOptionId($value['propertyGroupOptionId']);
                                        $propertie->setPosition($value['position']);
                                        $propertie->setLanguageId($languageId);
                                        $propertie->setUniqueIdentifier($value['_uniqueIdentifier']);
                                        $transaltion->add($propertie);
                                    }
                                }
                                $index = $property['name'];
                                $option->setTranslations($transaltion);
                                $collection[$index][$property['name']]->getOptions()->add($option);
                                $merk[] = $optionsValue['name'];
                            }
                        }
                    }
                }
            }
        }
        return $collection;
    }
    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity )
     */
    public function getProptertiesfilters(
        EntitySearchResult $result,
        ContainerInterface $container,
        array $config
    ): array {
        if (array_key_exists('filteronpageresult', $config)) {
            if ($config['filteronpageresult'] == 'yes') {
                return [];
            }
        }
        if (array_key_exists('filtertype', $config)) {
            if ($config['filtertype'] == 'yes') {
                return $this->getAllFilters($container);
            }
        }

        return $this->getRealtionsFilters($result, $config);
    }

    private function getAllFilters(ContainerInterface $container): array
    {
        $repository = $container->get('property_group.repository');
        $contextheandler = new ContextService();
        $criteria = new Criteria();
        $criteria->addAssociation('options');
        $context = $contextheandler->getContext();
        $entitiesPro = $repository->search($criteria, $context);
        $prupertiesGoup = $entitiesPro->getEntities();
        $collectionPro = [];
        foreach ($prupertiesGoup as $property) {
            if (!array_key_exists($property->getName(), $collectionPro)) {
                $collectionPro[$property->getName()][] = $property;
            }
        }
        return $collectionPro;
    }

    /**
     * @param EntitySearchResult<SalesChannelProductEntity> $result
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function getRealtionsFilters(EntitySearchResult $result, array $config): array
    {
        /** @var EntityCollection<SalesChannelProductEntity> $entities */
        $entities = $result->getEntities();
        $properties = [];
        $collection = [];
        foreach ($entities as $entity) {
            if ($entity->getSortedProperties() !== null) {
                foreach ($entity->getSortedProperties() as $index => $property) {
                    if (!array_key_exists($property->getName(), $collection)) {
                        $collection[$property->getName()] = new PropertyGroupOptionCollection();
                    }
                }
            }
        }
        foreach ($entities as $entity) {
            if ($entity->getSortedProperties() !== null) {
                foreach ($entity->getSortedProperties() as $index => $property) {
                    $options = $property->getOptions();
                    if ($options !== null) {
                        foreach ($options as $option) {
                            $collection[$property->getName()]->add($option);
                        }
                    }
                }
            }
        }
        $this->mergeProperties($entities, $properties, $collection);
        $properties = $this->filter($config, $properties);
        return $properties;
    }

    private function filter(array $config, array $properties): array
    {
        $countFilter = 0;
        $filter = [];
        $return = [];
        if (array_key_exists('filterfilter', $config)) {
            if (!empty($config['filterfilter'])) {
                $filter = explode("\n", $config['filterfilter']);
                $countFilter = count($filter);
            }
        }
        if ($countFilter > 0) {
            foreach ($properties as $key => $property) {
                if (in_array($key, $filter)) {
                    $return[] = $property;
                }
            }
            return $return;
        }
        return $properties;
    }

    /**
     * @param EntityCollection<SalesChannelProductEntity> $entities
     * @param array $properties
     * @param array $collection
     * @return void
     */
    private function mergeProperties(EntityCollection $entities, array &$properties, array &$collection): void
    {
        $merker = [];
        foreach ($entities as $entity) {
            if ($entity->getSortedProperties() !== null) {
                foreach ($entity->getSortedProperties() as $index => $property) {
                    $id = $property->getId();
                    if (!in_array($id, $merker)) {
                        $property->setOptions($collection[$property->getName()]);
                        $properties[$property->getName()][$index] = $property;
                        $merker[] = $id;
                    }
                }
            }
        }
    }

    public function getManufactoryByOwnQuery(array $newResult, ContainerInterface $container, array $config): array
    {
        if (array_key_exists('filteronpageresult', $config)) {
            if ($config['filteronpageresult'] == 'yes') {
                return [];
            }
        }
        if (array_key_exists('filtertype', $config)) {
            if ($config['filtertype'] == 'yes') {
                return $this->getAllManufactory($container);
            }
        }
        $manufatory = [];
        foreach ($newResult['hits']['hits'] as $item) {
            if (array_key_exists('manufacturer_id', $item["_source"]) && array_key_exists('manufacturer_name', $item["_source"])) {
                $manfatoryEntity = new ProductManufacturerEntity();
                $manfatoryEntity->setId($item["_source"]['manufacturer_id']);
                $translated = [
                    'name' => $item["_source"]["manufacturer_name"]
                ];
                $manfatoryEntity->setTranslated($translated);
                $manufatory[$item["_source"]['manufacturer_id']] = $manfatoryEntity;
            }
        }
        return $manufatory;
    }

    public function getManufactory(EntitySearchResult $result, ContainerInterface $container, array $config): array
    {
        if (array_key_exists('filteronpageresult', $config)) {
            if ($config['filteronpageresult'] == 'yes') {
                return [];
            }
        }
        if (array_key_exists('filtertype', $config)) {
            if ($config['filtertype'] == 'yes') {
                return $this->getAllManufactory($container);
            }
        }
        return $this->getRelationManufactory($result);
    }

    /**
     * @param EntitySearchResult<SalesChannelProductEntity> $result
     * @return array
     */
    private function getRelationManufactory(EntitySearchResult $result): array
    {
        /** @var EntityCollection<SalesChannelProductEntity> $entities */
        $entities = $result->getEntities();
        $manufatory = [];
        foreach ($entities as $entity) {
            $manufacturer = $entity->getManufacturer();
            if ($manufacturer != null) {
                $manufatory[$manufacturer->getId()] = $entity->getManufacturer();
            }
        }
        return $manufatory;
    }

    private function getAllManufactory(ContainerInterface $container): array
    {
        $repository = $container->get('product_manufacturer.repository');
        $contextheandler = new ContextService();
        $context = $contextheandler->getContext();
        $criteria = new Criteria();
        $entitiesManu = $repository->search($criteria, $context);
        $manufatory = $entitiesManu->getEntities()->getElements();
        return $manufatory;
    }
}
