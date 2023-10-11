<?php

namespace Sisi\Search\Service;

use Doctrine\DBAL\Connection;
use Elasticsearch\Client;
use Exception;
use Symfony\Bridge\Monolog\Logger;

class ExInaktiveExService
{
    public function delteInESServer(array $products, Client $client, string $indexName, Logger $logger)
    {
        foreach ($products as $product) {
            $params = [
                'index' => $indexName,
                'id' => strtolower($product['id'])
            ];
            try {
                $client->delete($params);
            } catch (Exception $e) {
                $logger->log(100, $e->getMessage());
            }
        }
    }

    public function getAllInaktiveProducts(connection $connection, array $parameters): array
    {
        $sql = "";

        if ($parameters['type'] === 'inaktive') {
            $sql = "SELECT  HEX(product.id) AS `id`
                FROM product
                     LEFT JOIN product_visibility
                     ON product.id = product_visibility.product_id
                WHERE product.active = 0 and  product_visibility.sales_channel_id = UNHEX(:channelId)";
        }

        if ($parameters['type'] === 'clean') {
            $sql = "SELECT  HEX(product.id) AS `id`
                FROM product
                     LEFT JOIN product_visibility
                     ON product.id = product_visibility.product_id
                WHERE (product.stock < 0  OR product.available =  0 OR is_closeout = 1)
                and  product_visibility.sales_channel_id = UNHEX(:channelId)";
        }

        if (!array_key_exists('limit', $parameters)) {
            $parameters['limit'] = 1000;
        }

        if (array_key_exists('offset', $parameters) && array_key_exists('limit', $parameters)) {
            $sql .= " LIMIT " . $parameters['offset'];
            $sql .= "," . $parameters['limit'];
        }


        return $connection->fetchAllAssociative($sql, ['channelId' => $parameters['channelId']]);
    }
}
