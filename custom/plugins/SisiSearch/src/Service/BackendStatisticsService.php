<?php

namespace Sisi\Search\Service;

use Elasticsearch\Client;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * Class ContextService
 * @package Sisi\Search\Service
 * @SuppressWarnings(PHPMD.StaticAccess)
 */

class BackendStatisticsService
{

    /**
     * @param array $config
     * @param Client $client
     * @param array|bool $parameters
     * @return array
     */
    public function getHistory($config, $client, $parameters)
    {
        if ($parameters === false) {
            return [];
        }
        $size = 5;
        if (array_key_exists('querylogsize', $config)) {
            $size = $config['querylogsize'];
        }
        if (array_key_exists('querylog', $config)) {
            if ($config['querylog'] === '1') {
                $must['bool']['must'][0]['match']['language_id'] = $parameters['languageId'];
                $params = [
                    'index' => $parameters['indexname'],
                    'size' => $size,
                    'from'  => 0,
                    'body' => [
                        "query" => $must,
                        'aggs' => [
                            'historyTerms' => [
                                "terms" => [
                                    "field" => "term.keyword",
                                    "size" => $size
                                ],
                            ],
                            'historyProductname' => [
                                "terms" => [
                                    "field" => "product_name.keyword",
                                    "size" => $size
                                ]
                            ]
                        ],
                    ],

                ];
                return $client->search($params);
            }
        }

        return [];
    }
}
