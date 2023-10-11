<?php

namespace Sisi\Search\ESindexing;

use Elasticsearch\Client;
use Sisi\Search\ESIndexInterfaces\InterfaceCreateIndex;

class CreateIndex implements InterfaceCreateIndex
{
    public function setInsert(Client $client, array $params): array
    {
        return $client->indices()->create($params);
    }
}
