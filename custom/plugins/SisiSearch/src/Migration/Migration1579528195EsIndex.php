<?php

declare(strict_types=1);

namespace Sisi\Search\Migration;

use Doctrine\DBAL\Connection;
use Exception;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;
use Sisi\Search\Service\SearchService;
use Sisi\Search\Service\ContextService;
use Doctrine\DBAL\Driver\Statement;

class Migration1579528195EsIndex extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1579528195;
    }

    public function update(Connection $connection): void
    {
        // implement update
        $sql = "CREATE TABLE IF NOT EXISTS  `s_plugin_sisi_search_es_index`(
                `id`  BINARY(16)   NOT NULL,
                `index`	varchar(500) NOT NULL,
                `entity`	varchar(500) NOT NULL,
                `time` int ,
                `shop` varchar(500) NOT NULL,
                `token` varchar(255) NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE =utf8mb4_unicode_ci";

        $connection->executeStatement($sql);

        $connection->executeStatement(
            "
            CREATE TABLE IF NOT EXISTS `s_plugin_sisi_search_es_fields` (
              `id` BINARY(16) NOT NULL,
              `name` VARCHAR(255) DEFAULT '',
              `tablename` VARCHAR(255) DEFAULT '',
              `fieldtype` VARCHAR(255) DEFAULT '',
                `edge` int DEFAULT 3,
                `minedge` int DEFAULT 3,
               `tokenizer` VARCHAR(255) DEFAULT '',
               `shop` VARCHAR(255) DEFAULT  '',
               `format` VARCHAR(255)  DEFAULT '',
               `filter1` VARCHAR(500)  DEFAULT  '',
               `filter2` VARCHAR(500)  DEFAULT '',
               `stemming` VARCHAR(500)  DEFAULT '',
               `stop` VARCHAR(500)  DEFAULT '',
               `stemmingstop` VARCHAR(255)  DEFAULT '',
                `booster` VARCHAR(255)  DEFAULT '0',
                `pattern` VARCHAR(255)  DEFAULT '',
              `created_at` DATETIME(3) NOT NULL,
              `updated_at` DATETIME(3) NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        "
        );

        // category_translation
        if ($this->findLast($connection) == null) {
            $values['nameField'] = 'name';
            $values['tablename'] = 'product';
            $values['fieldtype'] = 'text';
            $values['tokenizer'] = 'standard';
            $values['shop'] = '';
            $values['format'] = '';
            $values['filter1'] = 'lowercase';
            $values['filter2'] = 'autocomplete';
            $values['stemming'] = '';
            $values['stop'] = '';
            $values['stemmingstop'] = '';
            $this->insertField($connection, $values);
            $values['nameField'] = 'ean';
            $values['tokenizer'] = 'whitespace';
            $values['filter2'] = '';
            $this->insertField($connection, $values);
            $values['nameField'] = 'productNumber';
            $this->insertField($connection, $values);
            $values['nameField'] = 'description';
            $values['tokenizer'] = 'standard';
            $this->insertField($connection, $values);
            $values['nameField'] = 'metaTitle';
            $this->insertField($connection, $values);
            $values['nameField'] = 'name';
            $values['tablename'] = 'manufacturer';
            $values['fieldtype'] = 'text';
            $this->insertField($connection, $values);
            $values['nameField'] = 'name';
            $values['tablename'] = 'category';
            $this->insertField($connection, $values);
        }
        $this->addNewColumms($connection);
    }

    private function addNewColumms(Connection $connection): void
    {
        try {
            $result = $this->findLast($connection);

            if (!array_key_exists("strip", $result)) {
                $connection->executeStatement(
                    "ALTER TABLE `s_plugin_sisi_search_es_fields`
                                        ADD `strip` VARCHAR(255) DEFAULT '' COLLATE 'utf8mb4_unicode_ci' NULL"
                );
            }

            if (!array_key_exists("strip_str", $result)) {
                $connection->executeStatement(
                    "ALTER TABLE `s_plugin_sisi_search_es_fields`
                                        ADD `strip_str` VARCHAR(40) DEFAULT '' COLLATE 'utf8mb4_unicode_ci' NULL"
                );
            }

            if (!array_key_exists("synonym", $result)) {
                $connection->executeStatement(
                    "ALTER TABLE `s_plugin_sisi_search_es_fields`
                                        ADD `synonym` VARCHAR(6000) DEFAULT '' COLLATE 'utf8mb4_unicode_ci' NULL"
                );
            }

            if (!array_key_exists("filter3", $result)) {
                $connection->executeStatement(
                    "ALTER TABLE `s_plugin_sisi_search_es_fields`
                        ADD `filter3` VARCHAR(500)  DEFAULT '' COLLATE 'utf8mb4_unicode_ci' NOT NULL AFTER `filter2`"
                );
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    private function insertField(Connection $connection, array $values): void
    {
        $contextService = new ContextService();
        // name
        $sql = "
          INSERT INTO `s_plugin_sisi_search_es_fields` (`id`, `name`,  `tablename`,`fieldtype`,
          `tokenizer`,`shop`,`format`,
          `filter1`, `filter2`, `stemming`, `stop`, `stemmingstop`,`created_at`, `updated_at`)
          VALUES
          (:id, :nameField,:tablename,:fieldtype,:tokenizer,
           :shop,:format,:filter1,:filter2,:stemming,:stop, :stemmingstop, now(), now())";

        $connection->executeStatement(
            $sql,
            [
                'id' => $contextService->getRandom(),
                'nameField' => $values['nameField'],
                'tablename' => $values['tablename'],
                'fieldtype' => $values['fieldtype'],
                'tokenizer' => $values['tokenizer'],
                'shop' => $values['shop'],
                'format' => $values['format'],
                'filter1' => $values['filter1'],
                'filter2' => $values['filter2'],
                'stemming' => $values['stemming'],
                'stop' => $values['stop'],
                'stemmingstop' => $values['stemmingstop']
            ]
        );
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
