<?php

namespace Sisi\Search\Decorater;

use Elasticsearch\Client;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Sisi\Search\Service\ContextService;
use Sisi\Search\Service\QuerylogService;
use Symfony\Component\Console\Output\OutputInterface;
use Sisi\Search\ServicesInterfaces\InterfaceQuerylogSearchService;

class QuerylogSearchService implements InterfaceQuerylogSearchService
{

    public function searchQuerlog(
        array $config,
        Client $client,
        string $languageId,
        array $terms,
        $saleschannelId
    ): array {
        $heandlerQuerylog = new QuerylogService();
        $parameters['channelId'] = $saleschannelId;
        $parameters['lanuageName'] = $languageId;
        $indexname = $heandlerQuerylog->createIndexName($parameters);
        $size = 5;
        if (array_key_exists('querylogsize', $config)) {
            $size = $config['querylogsize'];
        }
        if (array_key_exists('querylog', $config)) {
            if ($config['querylog'] === '1') {
                $must['bool']['must'][0]['match']['term'] = $terms['product'];
                $must['bool']['must'][1]['match']['language_id'] = $languageId;
                $params = [
                    'index' => $indexname,
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

    public function insert(array $fields, string $esIndex, Client $client): array
    {
        $contexthaendler = new ContextService();
        $params = [
            'index' => $esIndex,
            'id' => $contexthaendler->getRandomHex(),
            'body' => $fields
        ];
        return $client->index($params);
    }
}
