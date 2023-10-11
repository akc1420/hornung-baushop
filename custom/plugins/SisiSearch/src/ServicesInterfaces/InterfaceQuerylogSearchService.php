<?php

namespace Sisi\Search\ServicesInterfaces;

use Elasticsearch\Client;

interface InterfaceQuerylogSearchService
{

    public function searchQuerlog(
        array $config,
        Client $client,
        string $languageId,
        array $terms,
        $saleschannelName
    ): array;

    public function insert(array $fields, string $esIndex, Client $client): array;
}
