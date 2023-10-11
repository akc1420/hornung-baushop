<?php

declare(strict_types=1);

namespace Sisi\Search\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1669388954idforlog extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1669388954;
    }

    public function update(Connection $connection): void
    {
        try {
            // implement update
            $connection->executeStatement(
                "ALTER TABLE `sisi_search_es_log_channel`  ADD `channelid` VARCHAR(55)  DEFAULT NULL COLLATE 'utf8mb4_unicode_ci'"
            );
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
        $connection->createQueryBuilder();
    }
}
