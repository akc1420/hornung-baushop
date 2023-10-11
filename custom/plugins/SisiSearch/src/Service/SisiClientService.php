<?php

namespace Sisi\Search\Service;

use Doctrine\DBAL\Connection;
use Elasticsearch\ClientBuilder;

class SisiClientService
{
    /**
     *
     * @param array{host:string} $config
     * @return \Elasticsearch\Client
     */
    public function getClient(array $config)
    {
        $host = $config['host'];
        return ClientBuilder::create()->setHosts([$host])->build();
    }
}
