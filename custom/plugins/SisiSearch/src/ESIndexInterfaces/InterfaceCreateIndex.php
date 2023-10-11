<?php

namespace Sisi\Search\ESIndexInterfaces;

use Elasticsearch\Client;

interface InterfaceCreateIndex
{
    public function setInsert(Client $client, array $params): array;
}
