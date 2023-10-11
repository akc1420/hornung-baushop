<?php

declare(strict_types=1);

namespace Sisi\Search\Migration;

use Doctrine\DBAL\Connection;
use Exception;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1617005993exclude extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1617005993;
    }

    public function update(Connection $connection): void
    {
        try {
            $result = $this->findLast($connection);
            if (!array_key_exists("exclude", $result)) {
                $connection->executeStatement(
                    "ALTER TABLE `s_plugin_sisi_search_es_fields` ADD `exclude` VARCHAR(55)
            DEFAULT '' COLLATE 'utf8mb4_unicode_ci' NOT NULL AFTER `filter3`"
                );
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    public function updateDestructive(Connection $connection): void
    {
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
