<?php

declare(strict_types=1);

namespace Sisi\Search\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1632238456ScheduledTaskHandler extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1632238456;
    }

    public function update(Connection $connection): void
    {
        // implement update
        $sql = "CREATE TABLE IF NOT EXISTS  `sisi_search_es_scheduledtask`(
                `id`  BINARY(16)   NOT NULL,
                `title`	varchar(255) DEFAULT '' NOT NULL,
                `time` int default 0,
                `shop` varchar(255) DEFAULT '' NOT NULL,
                `language` varchar(255) DEFAULT '' NOT NULL,
                `limit`	int default 1000,
                 `all`	int default 1,
                `kind` varchar(255) DEFAULT 'index' NOT NULL,
                `aktive` varchar(55) DEFAULT '' NOT NULL,
                `last_execution_time`  DATETIME(3) NULL,
                `next_execution_time`  DATETIME(3)  NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE =utf8mb4_unicode_ci";

        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        $connection->createQueryBuilder();
    }
}
