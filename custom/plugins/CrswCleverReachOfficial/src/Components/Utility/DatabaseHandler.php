<?php

namespace Crsw\CleverReachOfficial\Components\Utility;

use Crsw\CleverReachOfficial\Migration\Migration1603810036CleverReachEntityTable;
use Crsw\CleverReachOfficial\Migration\Migration1611157971CleverReachAutomationEntityTable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;

/**
 * Class DatabaseHandler
 *
 * @package Crsw\CleverReachOfficial\Components\Utility
 */
class DatabaseHandler
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * DatabaseHandler constructor.
     *
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Removes CleverReach tables.
     *
     * @throws DBALException
     */
    public function removeCleverReachTables(): void
    {
        $tableName = Migration1603810036CleverReachEntityTable::ENTITY_TABLE;
        $sql = 'DROP TABLE IF EXISTS `' . $tableName . '`';
        $this->connection->executeUpdate($sql);

        $tableName = Migration1611157971CleverReachAutomationEntityTable::ENTITY_TABLE;
        $sql = 'DROP TABLE IF EXISTS `' . $tableName . '`';
        $this->connection->executeUpdate($sql);
    }

    /**
     * Removes data from CleverReach table.
     *
     * @throws DBALException
     */
    public function removeData(): void
    {
        $tableName = Migration1603810036CleverReachEntityTable::ENTITY_TABLE;
        $sql = 'TRUNCATE TABLE `' . $tableName . '`';
        $this->connection->executeUpdate($sql);

        $tableName = Migration1611157971CleverReachAutomationEntityTable::ENTITY_TABLE;
        $sql = 'TRUNCATE TABLE `' . $tableName . '`';
        $this->connection->executeUpdate($sql);
    }
}
