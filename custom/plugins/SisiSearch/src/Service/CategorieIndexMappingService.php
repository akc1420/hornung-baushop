<?php

namespace Sisi\Search\Service;

use Elasticsearch\Client;
use Exception;
use Symfony\Bridge\Monolog\Logger;

class CategorieIndexMappingService
{

    public function createCategoryMapping(array $fieldConfig): array
    {
        foreach ($fieldConfig as $backendconfig) {
            $name = $backendconfig->getTablename() . "_" . $backendconfig->getName();
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

    public function delteIndex(Client $client, string $indexname, Logger $logger)
    {
        $params = [
            'index' => $indexname
        ];
        try {
            $client->indices()->delete($params);
        } catch (Exception $e) {
            $logger->log(100, $e->getMessage());
        }
    }

    public function createMappingCategory(Client $client, $params)
    {
        return $client->indices()->create($params);
    }
}
