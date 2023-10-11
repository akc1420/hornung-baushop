<?php

declare(strict_types=1);

namespace Sisi\Search\Migration;

use Doctrine\DBAL\Connection;
use Exception;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1656085043excludefield extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1656085043;
    }

    public function update(Connection $connection): void
    {
        try {
            $result = $this->findLast($connection);
            // implement update
            if (!array_key_exists("excludesearch", $result)) {
                $connection->executeStatement(
                    "ALTER TABLE `s_plugin_sisi_search_es_fields`  ADD `excludesearch` VARCHAR(55)  DEFAULT NULL COLLATE 'utf8mb4_unicode_ci'"
                );
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
        $connection->createQueryBuilder();
    }

    /**
     * @param Connection $connection
     * @return mixed
     */
    private function findLast(Connection $connection)
    {
        $handler = $connection->createQueryBuilder()
            ->select(['*'])
            ->from('s_plugin_sisi_search_es_fields')
            ->setMaxResults(1);
        return $handler->execute()->fetch();
    }
}
