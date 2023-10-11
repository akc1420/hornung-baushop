<?php

declare(strict_types=1);

namespace Sisi\Search\Migration;

use Doctrine\DBAL\Connection;
use Exception;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1601799488fuzzy extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1601799488;
    }

    public function update(Connection $connection): void
    {
        $result = $this->findLast($connection);
        if ($result != false) {
            $this->setAllQuerys1($connection, $result);
            $this->setAllQuerys2($connection, $result);
        }
    }

    private function setAllQuerys1(Connection $connection, array $result): void
    {
        try {
            if (!array_key_exists("fuzzy", $result)) {
                $connection->executeStatement(
                    "ALTER TABLE `s_plugin_sisi_search_es_fields` ADD `fuzzy` VARCHAR(55)  DEFAULT '' COLLATE 'utf8mb4_unicode_ci' NOT NULL"
                );
            }
            if (!array_key_exists("maxexpansions", $result)) {
                $connection->executeStatement(
                    "ALTER TABLE `s_plugin_sisi_search_es_fields` ADD `maxexpansions` VARCHAR(55)  DEFAULT '' COLLATE 'utf8mb4_unicode_ci' NOT NULL"
                );
            }
            if (!array_key_exists("slop", $result)) {
                $connection->executeStatement(
                    "ALTER TABLE `s_plugin_sisi_search_es_fields` ADD `slop` VARCHAR(55)  DEFAULT '' COLLATE 'utf8mb4_unicode_ci' NOT NULL"
                );
            }
            if (!array_key_exists("operator", $result)) {
                $connection->executeStatement(
                    "ALTER TABLE `s_plugin_sisi_search_es_fields` ADD `operator` VARCHAR(55)  DEFAULT '' COLLATE 'utf8mb4_unicode_ci' NOT NULL"
                );
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    private function setAllQuerys2(Connection $connection, array $result): void
    {
        try {
            if (!array_key_exists("autosynonyms", $result)) {
                $connection->executeStatement(
                    "ALTER TABLE `s_plugin_sisi_search_es_fields` ADD `autosynonyms` VARCHAR(55)  DEFAULT '' COLLATE 'utf8mb4_unicode_ci' NOT NULL"
                );
            }
            if (!array_key_exists("minimumshouldmatch", $result)) {
                $connection->executeStatement(
                    "ALTER TABLE `s_plugin_sisi_search_es_fields` ADD `minimumshouldmatch` VARCHAR(55)  DEFAULT '' COLLATE 'utf8mb4_unicode_ci' NOT NULL"
                );
            }
            if (!array_key_exists("prefixlength", $result)) {
                $connection->executeStatement(
                    "ALTER TABLE `s_plugin_sisi_search_es_fields` ADD `prefixlength` VARCHAR(55)  DEFAULT '' COLLATE 'utf8mb4_unicode_ci' NOT NULL"
                );
            }
            if (!array_key_exists("lenient", $result)) {
                $connection->executeStatement(
                    "ALTER TABLE `s_plugin_sisi_search_es_fields` ADD `lenient` VARCHAR(55)  DEFAULT '' COLLATE 'utf8mb4_unicode_ci' NOT NULL"
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
