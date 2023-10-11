<?php

namespace Sisi\Search\ESIndexInterfaces;

use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Elasticsearch\Client;

interface InterfaceInsertQuery
{
    public function insertValue(
        SalesChannelProductEntity $entitie,
        Client $client,
        string $esIndex,
        array $fields
    ): array;
}
