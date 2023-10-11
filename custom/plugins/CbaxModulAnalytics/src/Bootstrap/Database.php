<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Bootstrap;

class Database
{
    public function removeDatabaseTables($services)
    {
        $connection = $services['connectionService'];

        $connection->executeUpdate('DROP TABLE IF EXISTS `cbax_analytics_config`');
        $connection->executeUpdate('DROP TABLE IF EXISTS `cbax_analytics_groups_config`');
        $connection->executeUpdate('DROP TABLE IF EXISTS `cbax_analytics_search`');
        $connection->executeUpdate('DROP TABLE IF EXISTS `cbax_analytics_product_impressions`');
        $connection->executeUpdate('DROP TABLE IF EXISTS `cbax_analytics_category_impressions`');
        $connection->executeUpdate('DROP TABLE IF EXISTS `cbax_analytics_visitors`');
        $connection->executeUpdate('DROP TABLE IF EXISTS `cbax_analytics_pool`');
        $connection->executeUpdate('DROP TABLE IF EXISTS `cbax_analytics_referer`');
        $connection->executeUpdate('DROP TABLE IF EXISTS `cbax_analytics_manufacturer_impressions`');
    }

    /**
     * Internal helper function to check if a database table column exist.
     *
     * @param string $tableName
     * @param string $columnName
     * @param object $connection
     *
     * @return bool
     */
    public function columnExist($tableName, $columnName, $connection)
    {
        $sql = "SHOW COLUMNS FROM " . $connection->quoteIdentifier($tableName) . " LIKE ?";

        return count($connection->executeQuery($sql, array($columnName))->fetchAll()) > 0;
    }

    /**
     * Überprüfung ob Tabelle existiert
     *
     * @return bool
     */
    public static function tableExist($tableName, $connection)
    {
        $sql = "SHOW TABLES LIKE ?";
        $result = $connection->executeQuery($sql, array($tableName))->fetch();
        return !empty($result);
    }


}
