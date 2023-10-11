<?php

namespace Sisi\Search\Service;

use _HumbugBox01d8f9a04075\React\Socket\Connection;
use Elasticsearch\Client;
use Exception;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Sisi\Search\Service\ContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Sisi\Search\Service\SearchHelpService;
use Doctrine\DBAL\Connection as DBconnection;

class InsertTimestampService
{
    /**
     * @param array $parameters
     * @param Client $client
     * @param SalesChannelProductEntity $entitie
     * @return void
     *
     * @SuppressWarnings(PHPMD)
     */
    public function deleteEntry(array $parameters, Client $client, SalesChannelProductEntity $entitie)
    {
        if (array_key_exists('update', $parameters) && array_key_exists('time', $parameters)) {
            try {
                $params = [
                    'index' => $parameters['esIndex'],
                    'id' => $entitie->getid()
                ];
                $result = $client->delete($params);
            } catch (Exception $e) {
            }
        }
    }

    /**
     * @param int $time
     * @param array $parameters
     * @param DBconnection $connection
     * @param string $shopId
     * @param array $config
     * @return string|bool
     */
    public function getTheESIndex(int &$time, array $parameters, DBconnection $connection, string $shopId, array $config)
    {
        if (array_key_exists('update', $parameters)) {
            $heandler = new SearchHelpService();
            $config['strindexfinish'] = 'no';
            if (array_key_exists('language', $parameters)) {
                $lastindexMerker = $heandler->findLast($connection, $shopId, $parameters['language_id'], $config);
            } else {
                $lastindexMerker = $heandler->findLast($connection, $shopId, null, $config);
            }
            if ($lastindexMerker == false) {
                echo "No Index found\n";
                return false;
            }
            $time = $lastindexMerker['time'];
        }
        $esIndex = 'sisisearch';
        if (array_key_exists('language', $parameters)) {
            $esIndex .= '_' . strtolower($parameters['language']);
        }
        if (array_key_exists('prefix', $config)) {
            if (!empty($config['prefix'])) {
                $esIndex = $config['prefix'] . $esIndex;
            }
        }
        return $esIndex . '_' . $time;
    }
}
