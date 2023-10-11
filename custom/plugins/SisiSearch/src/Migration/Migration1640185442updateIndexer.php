<?php

declare(strict_types=1);

namespace Sisi\Search\Migration;

use Doctrine\DBAL\Connection;
use Exception;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1640185442updateIndexer extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1640185442;
    }

    public function update(Connection $connection): void
    {

        try {
            $connection->executeStatement(
                "ALTER TABLE sisi_search_es_scheduledtask ADD days VARCHAR(255) DEFAULT '' COLLATE 'utf8mb4_unicode_ci' NOT NULL AFTER kind"
            );
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        $connection->createQueryBuilder();
    }
}
