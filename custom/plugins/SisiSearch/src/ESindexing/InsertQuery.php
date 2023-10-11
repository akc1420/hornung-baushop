<?php

namespace Sisi\Search\ESindexing;

use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Elasticsearch\Client;
use Sisi\Search\ESIndexInterfaces\InterfaceInsertQuery;

class InsertQuery implements InterfaceInsertQuery
{
    public function insertValue(
        SalesChannelProductEntity $entitie,
        Client $client,
        string $esIndex,
        array $fields
    ): array {
      
        if (count($fields) > 0) {
            $params = [
                'index' => $esIndex,
                'id' => strtolower($entitie->getId()),
                'body' => $fields
            ];
            return $client->index($params);
        }
        return [];
    }
}
