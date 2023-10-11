<?php

declare(strict_types=1);

namespace Sisi\Search\Migration;

use Doctrine\DBAL\Connection;
use Exception;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1612341396whitespace extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1612341396;
    }

    public function update(Connection $connection): void
    {
        try {
            $result = $this->findLast($connection);
            // implement update
            if (!array_key_exists("whitespace", $result)) {
                $connection->executeStatement(
                    "ALTER TABLE `s_plugin_sisi_search_es_fields` ADD `whitespace` VARCHAR(55)
                                            DEFAULT '' COLLATE 'utf8mb4_unicode_ci' NOT NULL AFTER `filter3`"
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
        $handler = $connection->createQueryBuilder()
            ->select(['*'])
            ->from('s_plugin_sisi_search_es_fields')
            ->setMaxResults(1);
        return $handler->execute()->fetch();
    }


    public function updateDestructive(Connection $connection): void
    {
        $connection->createQueryBuilder();
    }
}
