<?php

declare(strict_types=1);

namespace Sisi\Search\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;
use Sisi\Search\Service\ContextService;

class Migration1603055209properties extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1603055209;
    }

    public function update(Connection $connection): void
    {
        $values['nameField'] = 'name';
        $values['tablename'] = 'properties';
        $values['fieldtype'] = 'text';
        $values['edge'] = '2';
        $values['minedge'] = '2';
        $values['tokenizer'] = 'Edge_n-gram_tokenizer';
        $values['shop'] = '';
        $values['format'] = '';
        $values['filter1'] = 'lowercase';
        $values['filter2'] = '';
        $values['stemming'] = '';
        $values['stop'] = '';
        $values['stemmingstop'] = '';
        $this->insertField($connection, $values);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
        $connection->createQueryBuilder();
    }

    private function insertField(Connection $connection, array $values): void
    {
        $contextService = new ContextService();
        // name
        $sql = "
          INSERT INTO `s_plugin_sisi_search_es_fields` (`id`, `name`,  `tablename`,`fieldtype`,`edge`,`minedge`,
          `tokenizer`,`shop`,`format`,
          `filter1`, `filter2`, `stemming`, `stop`, `stemmingstop`,`created_at`, `updated_at`)
          VALUES
          (:id, :nameField,:tablename,:fieldtype,:edge,:minedge,:tokenizer,
           :shop,:format,:filter1,:filter2,:stemming,:stop, :stemmingstop, now(), now())";

        $connection->executeStatement(
            $sql,
            [
                'id' => $contextService->getRandom(),
                'nameField' => $values['nameField'],
                'tablename' => $values['tablename'],
                'fieldtype' => $values['fieldtype'],
                'tokenizer' => $values['tokenizer'],
                'edge' => $values['edge'],
                'minedge' => $values['minedge'],
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
}
