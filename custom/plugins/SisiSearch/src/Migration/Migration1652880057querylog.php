<?php

declare(strict_types=1);

namespace Sisi\Search\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1652880057querylog extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1652880057;
    }

    public function update(Connection $connection): void
    {
        // implement update
        $sql = "CREATE TABLE IF NOT EXISTS `sisi_search_es_log_channel` (
              `name` VARCHAR(255) DEFAULT '',
              `indexname` VARCHAR(255) DEFAULT '',
              `languageId` VARCHAR(255) DEFAULT '',
              `aktive` TINYINT DEFAULT 0,
              `created_at` DATETIME(3) NOT NULL,
              `updated_at` DATETIME(3) NULL,
              PRIMARY KEY (`indexname`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";

        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        $connection->createQueryBuilder();
    }
}
