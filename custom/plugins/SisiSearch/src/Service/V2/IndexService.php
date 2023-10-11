<?php

namespace Sisi\Search\Service\V2;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Exception;
use Monolog\Logger;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Sisi\Search\Service\ClientService;
use Throwable;

class IndexService extends \Sisi\Search\Service\IndexService
{
    private ClientService $clientService;
    private Connection $connection;
    private Logger $logger;
    private SystemConfigService $systemConfigService;

    public function __construct(
        ClientService $clientService,
        Connection $connection,
        Logger $logger,
        SystemConfigService $systemConfigService
    ) {
        $this->clientService = $clientService;
        $this->connection = $connection;
        $this->logger = $logger;
        $this->systemConfigService = $systemConfigService;
    }

    /**
     * @param string|null $salesChannelId
     * @return array
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function getIndexes(?string $salesChannelId): array
    {
        $client = $this->getClient($salesChannelId);
        $hostIndices = $client->indices()->get(['index' => "*"]);
        $query = $this->connection->createQueryBuilder()
            ->select('HEX(`id`) AS `id`, `entity`, `index`, `isFinish`, `language`, `shop`, time, `token`')
            ->from(' s_plugin_sisi_search_es_index')
            ->orderBy('time', 'ASC');

        if ($salesChannelId) {
            $query->where('shop = :salesChannelId');
            $query->setParameter('salesChannelId', $salesChannelId);
        }

        $dbIndices = $query->execute()->fetchAllAssociative();
        $indices = [];

        $indices = $this->getHostIndices($hostIndices, $dbIndices, $indices);

        $indices = $this->getDbIndices($dbIndices, $indices);
        $sortedindices = [];
        $others = [];
        foreach ($indices as $index) {
            if (array_key_exists('time', $index)) {
                $sortedindices[$index['time']] = $index;
            } else {
                $others[] = $index;
            }
        }
        $indices = [];
        krsort($sortedindices);
        $sortedindices = array_merge($sortedindices, $others);
        return $sortedindices;
    }

    /**
     * @param string|null $salesChannelId
     * @return \Elasticsearch\Client
     */
    private function getClient(?string $salesChannelId): \Elasticsearch\Client
    {
        $config = $this->systemConfigService->get("SisiSearch.config", $salesChannelId);
        $client = $this->clientService->createClient($config);
        return $client;
    }

    /**
     * @param array $hostIndices
     * @param array $dbIndices
     * @param array $indices
     * @return array
     */
    private function getHostIndices(array $hostIndices, array $dbIndices, array $indices): array
    {
        foreach ($hostIndices as $indexName => $hostIndex) {
            $index = $hostIndex;
            $index["index"] = $indexName;
            foreach ($dbIndices as $dbIndex) {
                if ($dbIndex["index"] === $indexName) {
                    foreach ($dbIndex as $column => $value) {
                        $index[$column] = $value;
                    }
                }
            }
            $indices[] = $index;
        }
        return $indices;
    }

    /**
     * @param array $dbIndices
     * @param $indices
     * @return mixed
     */
    private function getDbIndices(array $dbIndices, $indices)
    {
        foreach ($dbIndices as $dbIndex) {
            $skip = false;
            foreach ($indices as $index) {
                if ($dbIndex["index"] === $index["index"]) {
                    $skip = true;
                }
            }
            if (!$skip) {
                $indices[] = $dbIndex;
            }
        }
        return $indices;
    }

    public function checkChannelId(array $salechannelItem, Logger $loggingService): bool
    {
        if (count($salechannelItem) == 0) {
            $loggingService->log('100', 'Channel not found ');
            echo "Channel not found\n";
            return false;
        }
        return true;
    }

    public function checkshop(string $shop, Logger $loggingService): bool
    {
        if (empty($shop)) {
            $loggingService->log('100', 'Channel not found');
            return false;
        }
        return true;
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function deleteIndex(string $indexName)
    {
        $result = null;
        $index = $this->getIndex($indexName);
        if ($index) {
            $client = $this->getClient($index["shop"]);
        } else {
            $client = $this->getClient(null);
        }

        $params = [
            'index' => $indexName
        ];
        $exists = $client->indices()->exists($params);
        if ($exists) {
            $result = $client->indices()->delete($params);
        }

        $query = $this->connection->createQueryBuilder()
            ->delete('s_plugin_sisi_search_es_index')
            ->where('`index` = :indexName');
        $query->setParameter('indexName', $indexName);
        $query->execute();
        return $result;
    }

    /**
     * @param string $indexName
     * @return array|null
     * @throws Throwable
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     */
    private function getIndex(string $indexName): ?array
    {
        try {
            $query = $this->connection->createQueryBuilder()
                ->select('HEX(`id`) AS `id`, `entity`, `index`, `isFinish`, `language`, `shop`, `time`, `token`')
                ->from('s_plugin_sisi_search_es_index')
                ->orderBy('time', 'ASC');
            $query->where('`index` = :indexName');
            $query->setParameter('indexName', $indexName);
            $result = $query->execute();
            $row = $result->fetchAssociative();
            if ($row) {
                return $row;
            }
            return null;
        } catch (Throwable $throwable) {
            $this->logger->log(100, $throwable->getMessage());
            throw $throwable;
        }
    }

    public function getStatistics(?string $salesChannelId): array
    {
        $client = $this->getClient($salesChannelId);
        $statistics = $client->indices()->stats(['index' => '*']);
        return $statistics;
    }

    public function getCluster($salesChannelId): array
    {
        $client = $this->getClient($salesChannelId);
        $result = [
            'info' => $client->info(),
            'health' => $client->cluster()->health()
        ];
        return $result;
    }
}
