<?php

namespace Sisi\Search\Service;

use Elasticsearch\ClientBuilder;
use Elasticsearch\Client;

class ClientService
{
    public function createClient(array $config): Client
    {
        $hosts[] = 'http//:localhost:9200';
        if (
            array_key_exists('elasticloud', $config) && array_key_exists('user', $config)
            && array_key_exists('password', $config) && array_key_exists('cloudid', $config)
        ) {
            if ($config['elasticloud'] ===  '1') {
                return ClientBuilder::create()
                    ->setElasticCloudId($config['cloudid'])
                    ->setBasicAuthentication($config['user'], $config['password'])
                    ->build();
            }
        }
        if (array_key_exists('host', $config)) {
            if (!empty($config['host'])) {
                $hosts = explode("\n", $config['host']);
                foreach ($hosts as &$host) {
                    $host = trim($host);
                }
            }
        }
        $hosts = array_filter($hosts);
        return ClientBuilder::create()->setHosts($hosts)->build();
    }
}
