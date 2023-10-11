<?php

namespace Sisi\Search\Decorater;

use Elasticsearch\Client;
use Sisi\Search\Service\TextService;
use Sisi\Search\ServicesInterfaces\InterfaceQuerylogService;
use Symfony\Component\Console\Output\OutputInterface;

class QuerylogService implements InterfaceQuerylogService
{

    public function createMapping(): array
    {
        $mapping["properties"]["term"] = [
            "type" => "text",
            "analyzer" => "analyzer_product_name",
            "fields" => [
                "keyword" => [
                    "type" => "keyword",
                    "ignore_above" => 256
                ]
            ]
        ];

        $mapping["properties"]["product_name"] = [
            "type" => "text",
            "analyzer" => "analyzer_product_name",
            "fields" => [
                "keyword" => [
                    "type" => "keyword",
                    "ignore_above" => 256
                ]
            ]
        ];

        $mapping["properties"]["category_name"] = [
            "type" => "text",
            "analyzer" => "analyzer_product_name",
            "fields" => [
                "keyword" => [
                    "type" => "keyword",
                    "ignore_above" => 256
                ]
            ]
        ];

        $mapping["properties"]["product_url"] = [
            "type" => "text",
            "analyzer" => "analyzer_product_url"
        ];
        $mapping["properties"]["product_id"] = [
            "type" => "text",
            "analyzer" => "analyzer_id"
        ];

        $mapping["properties"]["language_id"] = [
            "type" => "text",
            "analyzer" => "analyzer_id"
        ];

        $mapping["properties"]["language_name"] = [
            "type" => "text",
            "analyzer" => "analyzer_id"
        ];

        $mapping["properties"]["time"] = [
            "type" => "integer",
        ];

        $mapping["properties"]["customerId"] = [
            "type" => "text",
            "analyzer" => "analyzer_id"
        ];

        $mapping["properties"]["customerGroupId"] = [
            "type" => "text",
            "analyzer" => "analyzer_id"
        ];

        $mapping["properties"]["type"] = [
            "type" => "text",
            "analyzer" => "analyzer_id"
        ];

        return $mapping;
    }


    public function createSettings(): array
    {
        $settings["analysis"]['filter']['autocomplete'] = [
            "type" => "edge_ngram",
            "min_gram" => "3",
            "max_gram" => "10"
        ];

        $settings["analysis"]['analyzer']['analyzer_term'] = [
            "filter" => ["lowercase"],
            "type" => "custom",
            "tokenizer" => "standard"
        ];

        $settings["analysis"]['analyzer']['analyzer_product_name'] = [
            "filter" => ["lowercase"],
            "type" => "custom",
            "tokenizer" => "standard"
        ];

        $settings["analysis"]['analyzer']['analyzer_product_url'] = [
            "filter" => ["lowercase"],
            "type" => "custom",
            "tokenizer" => "standard"
        ];

        $settings["analysis"]['analyzer']['analyzer_id'] = [
            "filter" => ["lowercase"],
            "type" => "custom",
            "tokenizer" => "standard"
        ];

        return $settings;
    }
}
