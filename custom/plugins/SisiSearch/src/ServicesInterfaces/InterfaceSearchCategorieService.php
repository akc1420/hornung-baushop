<?php

namespace Sisi\Search\ServicesInterfaces;

use Elasticsearch\Client;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

interface InterfaceSearchCategorieService
{
    /**
     * @param array $config
     * @param array $params
     * @param array $configkategorie
     * @param client $client
     * @param string $term
     * @return array|void
     */
    public function searchCategorie($config, $params, $configkategorie, $client, $term);

    /**
     * @param array $config
     * @param array $params
     * @param array $configkategorie
     * @param client $client
     * @param string $term
     * @return array|void
     */
    public function searchCategorieWithOwnIndex($config, $params, $configkategorie, $client, $term);

    /**
     * @param Client $client
     * @param string $indexname
     * @param CategoryEntity $category
     * @param array $fieldConfig
     * @param array $config
     * @param array $parameter
     * @return array
     */
    public function insertValue($client, $indexname, $category, $fieldConfig, $config, $parameter);

    public function createCriteria(): Criteria;

    public function createCategoryMapping(array $fieldConfig): array;

    /**
     * @SuppressWarnings(PHPMD)
     */
    public function createCategorySettings(array $fieldConfigs, array $config);
}
