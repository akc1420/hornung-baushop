<?php

declare(strict_types=1);

namespace Sisi\Search\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1653128194setFinish extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1653128194;
    }

    public function update(Connection $connection): void
    {
        $sql = "UPDATE `s_plugin_sisi_search_es_index`
            SET
              `isfinish` = :isfinish";

        $connection->executeStatement(
            $sql,
            [
                'isfinish' => 1,
            ]
        );
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
        $connection->createQueryBuilder();
    }
}
