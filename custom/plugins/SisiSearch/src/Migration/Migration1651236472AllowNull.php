<?php

declare(strict_types=1);

namespace Sisi\Search\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1651236472AllowNull extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1651236472;
    }

    public function update(Connection $connection): void
    {
        try {
            $connection->executeStatement(
                "ALTER TABLE `s_plugin_sisi_search_es_fields` CHANGE shoplanguage shoplanguage VARCHAR(255)  DEFAULT NULL"
            );

            $connection->executeStatement(
                "ALTER TABLE `s_plugin_sisi_search_es_fields` CHANGE prefix prefix VARCHAR(255)  DEFAULT NULL"
            );
        } catch (\Exception $ex) {
            // Silence is gold
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
        $connection->createQueryBuilder();
    }
}
