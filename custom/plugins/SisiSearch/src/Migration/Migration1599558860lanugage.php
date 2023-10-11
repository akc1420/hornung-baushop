<?php

declare(strict_types=1);

namespace Sisi\Search\Migration;

use Doctrine\DBAL\Connection;
use Exception;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1599558860lanugage extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1599558860;
    }

    public function update(Connection $connection): void
    {
        try {
            $result = $this->findLast($connection);
            if ($result == false) {
                $connection->executeStatement(
                    "ALTER TABLE `s_plugin_sisi_search_es_index` ADD `language` VARCHAR(255)  DEFAULT '' COLLATE 'utf8mb4_unicode_ci' NOT NULL"
                );
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * @param Connection $connection
     * @return mixed
     */
    private function findLast(Connection $connection)
    {
        $sql = "SHOW COLUMNS FROM `s_plugin_sisi_search_es_index` LIKE 'language'";
        return $connection->query($sql)->fetchColumn();
    }

    public function updateDestructive(Connection $connection): void
    {
        $connection->createQueryBuilder();
    }
}
