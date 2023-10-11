<?php

namespace Sisi\Search\Service;

use Doctrine\DBAL\Connection;
use Elasticsearch\Client;
use Shopware\Core\Framework\Uuid\Uuid;
use Sisi\Search\ServicesInterfaces\InterfaceQuerylogSearchService;
use Sisi\Search\ServicesInterfaces\InterfaceQuerylogService;
use Symfony\Component\Console\Output\OutputInterface;

use function pcov\clear;

class QuerylogService
{
    /**
     * @param Client $client
     * @param array $parameters
     * @param OutputInterface|null $output
     * @param InterfaceQuerylogService $querylogSearchService
     * @param Connection $connection
     * @return void
     */
    public function queryLogStart($client, &$parameters, $output, $querylogSearchService, $connection)
    {
        $texthaendler = new TextService();
        $body['index'] = $this->createIndexName($parameters);
        $parameters['indexname'] = $body['index'];
        $body['body']['settings'] = $querylogSearchService->createSettings();
        $body['body']['mappings'] = $querylogSearchService->createMapping();
        if (array_key_exists("delete", $parameters)) {
            try {
                $params = [
                    'index' => $body['index']
                ];
                $result = $client->indices()->delete($params);
                if (array_key_exists("acknowledged", $result)) {
                    if ($result['acknowledged']) {
                        $this->delteQuery($connection, 0, $parameters);
                    }
                }
            } catch (\Exception $e) {
                $outputString = 'Caught exception: ' . $e->getMessage() . "\n";
                if (!strpos($outputString, 'resource_already_exists') === false) {
                    $outputString .= "You have to delete the old index before";
                }
                $texthaendler->write($output, $outputString);
            }
        } else {
            $client->indices()->create($body);
        }
    }
    public function clearAllByChannel(Connection $connection, array $config, string $channelid): void
    {
        $index = "log_sisi_" . $channelid . "*";
        $sql = 'DELETE FROM `sisi_search_es_log_channel` WHERE channelid  = :channelid';
        try {
            $connection->executeStatement($sql, ['channelid' => $channelid]);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        $hostvalue = $config['host'];
        $hostvalues = explode("\n", $hostvalue);
        $command = "curl -XDELETE "  . $hostvalues[0] . "/" . $index;
        shell_exec($command);
    }

    public function clearAll(Connection $connection, array $config): void
    {
        $sql = 'DELETE FROM `sisi_search_es_log_channel`';
        try {
            $connection->executeStatement($sql);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        $hostvalue = $config['host'];
        $hostvalues = explode("\n", $hostvalue);
        $command = "curl -XDELETE "  . $hostvalues[0] . "/log_sisi*";
        shell_exec($command);
    }

    public function createIndexName(array $parameters): string
    {
        $indexname = "log_sisi_" . strtolower($parameters['channelId']);
        return $indexname;
    }

    public function insert(Connection $connection, array $params)
    {
        if (!array_key_exists("delete", $params)) {
            if (!array_key_exists('indexname', $params)) {
                $params['indexname'] = $this->createIndexName($params);
            }
            $isInsert = $this->findLast($connection, $params);
            if (!$isInsert) {
                $this->insertQuery($connection, $params);
            } else {
                $this->delteQuery($connection, 1, $params);
            }
        }
    }

    /**
     * @param Connection $connection
     * @return mixed
     */
    private function findLast(Connection $connection, array $params)
    {
        $handler = $connection->createQueryBuilder()
            ->select(['*'])
            ->from('sisi_search_es_log_channel')
            ->andWhere('indexname = :indexname')
            ->setParameter('indexname', $params['indexname'])
            ->setMaxResults(1);
        return $handler->execute()->fetch();
    }

    public function insertQuery(Connection $connection, array $params)
    {
        if (!array_key_exists("delete", $params)) {
            $sql = "
          INSERT INTO `sisi_search_es_log_channel` (`name`,  `indexname`,`languageId`,`aktive`,`created_at`,`updated_at`,`channelid`)
          VALUES
          (:name,:indexname,:languageId,:aktive, now(), now(),:channelid)";
            $connection->executeStatement(
                $sql,
                [
                    'name' => $params['shop'],
                    'indexname' => $params['indexname'],
                    'channelid' => $params["channelId"],
                    'languageId' => $params['lanuageName'],
                    'aktive' => 1
                ]
            );
        }
    }

    public function delteQuery(Connection $connection, int $aktive, array $params)
    {
        $sql = "UPDATE `sisi_search_es_log_channel`
            SET
              `aktive` = :aktive
            WHERE indexname = :indexname";
        $connection->executeStatement(
            $sql,
            [
                'aktive' => $aktive,
                'indexname' => $params['indexname'],
            ]
        );
    }
    /**
     * @param Connection $connection
     * @return mixed
     */
    public function findAll(Connection $connection, $channelid)
    {
        $handler = $connection->createQueryBuilder()
            ->select(['*'])
            ->from('sisi_search_es_log_channel')
            ->andWhere('aktive=:aktive')
            ->andWhere('channelid=:channelid')
            ->setParameter('aktive', 1)
             ->setParameter('channelid', $channelid);
        return $handler->execute()->fetchAssociative();
    }
}
