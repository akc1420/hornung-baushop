<?php

declare(strict_types=1);

namespace Sisi\Search\Migration;

use Doctrine\DBAL\Connection;
use Exception;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1653126356finishIndex extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1653126356;
    }

    public function update(Connection $connection): void
    {
        try {
            // implement update
            $connection->executeStatement(
                "ALTER TABLE `s_plugin_sisi_search_es_index`
                     ADD `isfinish` int default 0"
            );
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
        $connection->createQueryBuilder();
    }
}
