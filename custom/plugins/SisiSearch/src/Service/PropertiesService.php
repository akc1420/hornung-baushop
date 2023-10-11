<?php

namespace Sisi\Search\Service;

use Elasticsearch\Client;
use MyProject\Container;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionCollection;
use Shopware\Core\Content\Property\PropertyGroupEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Sisi\Search\ServicesInterfaces\InterfaceFrontendService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PropertiesService
{
    public function withOwnQuery(
        InterfaceFrontendService $frontendService,
        client $client,
        array $params,
        SalesChannelContext $saleschannelContext,
        ContainerInterface $container,
        array $config
    ) {
        $params['from'] = 0;
        $params['size'] = $config['ownFilterquery'];
        return $frontendService->search($client, $params, $saleschannelContext, $container);
    }

    /**
     * @param array $products
     * @param array $config
     * @param InterfaceFrontendService $frontendService
     * @param Client $client
     * @param array $params
     * @param SalesChannelContext $saleschannelContext
     * @param ContainerInterface $container
     * @return array
     *
     *  @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function sortAjaxPopUpProperties(
        array $products,
        array $config,
        InterfaceFrontendService $frontendService,
        client $client,
        array $params,
        SalesChannelContext $saleschannelContext,
        ContainerInterface $container
    ): array {
        $return = [];
        $optionMerker = [];
        if (array_key_exists('properties', $config)) {
            if ($config['properties'] === '4') {
                if (array_key_exists('ownFilterquery', $config)) {
                    if ($config['ownFilterquery'] > 0) {
                        $products = $this->withOwnQuery(
                            $frontendService,
                            $client,
                            $params,
                            $saleschannelContext,
                            $container,
                            $config
                        );
                    }
                }
                $hits = $products['hits']['hits'];
                foreach ($hits as $hit) {
                    foreach ($hit['_source']['properties'] as $property) {
                        if (!in_array($property["option_id"], $optionMerker)) {
                            $return[$property['property_id']][] = $property;
                            $optionMerker[] = $property["option_id"];
                        }
                    }
                    if (array_key_exists('children', $hit['_source'])) {
                        if (count($hit['_source']['children']) > 0) {
                            foreach ($hit['_source']['children'] as $childProperty) {
                                if (array_key_exists("properties", $childProperty)) {
                                    foreach ($childProperty["properties"] as $childPropertyItem) {
                                        if (!in_array($childPropertyItem["option_id"], $optionMerker)) {
                                            $return[$childPropertyItem['property_id']][] = $childPropertyItem;
                                            $optionMerker[] = $childPropertyItem["option_id"];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $return;
    }

    /**
     * @param array{channel : SalesChannelProductEntity} $fields
     * @param $entitie
     * @param array $parameters
     * @return void
     */
    public function setSortedProperties(array &$fields, &$entitie, array $parameters): void
    {
        if (array_key_exists('propertyGroupSorter', $parameters)) {
            $properties = $entitie->getProperties();
            $sortedProperties = $parameters['propertyGroupSorter']->sort($properties);
            $entitie->setSortedProperties($sortedProperties);
            if ((method_exists($fields['channel'], 'setSortedProperties'))) {
                $fields['channel']->setSortedProperties($sortedProperties);
                $fields['channel']->setProperties($properties);
            }
        }
    }
}
