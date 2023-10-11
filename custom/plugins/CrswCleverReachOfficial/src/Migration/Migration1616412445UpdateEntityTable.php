<?php declare(strict_types=1);

namespace Crsw\CleverReachOfficial\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * Class Migration1616412445UpdateEntityTable
 *
 * @package Crsw\CleverReachOfficial\Migration
 */
class Migration1616412445UpdateEntityTable extends MigrationStep
{
    public const ENTITY_TABLE = 'cleverreach_entity';

    /**
     * @inheritDoc
     *
     * @return int
     */
    public function getCreationTimestamp(): int
    {
        return 1616412445;
    }

    /**
     * @inheritDoc
     *
     * @param Connection $connection
     *
     * @throws DBALException
     */
    public function update(Connection $connection): void
    {
        $sql = 'ALTER TABLE `' . self::ENTITY_TABLE . '` 
            CHANGE COLUMN `data` `data` LONGBLOB;';

        $connection->executeUpdate($sql);
    }

    /**
     * @inheritDoc
     *
     * @param Connection $connection
     *
     * @throws DBALException
     */
    public function updateDestructive(Connection $connection): void
    {
        // No need for update destructive
    }
}
