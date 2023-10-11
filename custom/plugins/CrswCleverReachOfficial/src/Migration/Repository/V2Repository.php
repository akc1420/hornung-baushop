<?php

namespace Crsw\CleverReachOfficial\Migration\Repository;

use Doctrine\DBAL\Connection;

/**
 * Class V2Repository
 *
 * @package Crsw\CleverReachOfficial\Migration\Repository
 */
class V2Repository
{
    /**
     * @var Connection
     */
    private static $connection;

    /**
     * Checks if cleverreach_configs table exists.
     *
     * @return bool
     */
    public static function configTableExists(): bool
    {
        $schemaManager = self::$connection->getSchemaManager();

        return  $schemaManager ? $schemaManager->tablesExist('cleverreach_configs') : false;
    }

    /**
     * Get user's API credentials.
     *
     * @return array
     */
    public static function getAPICredentials(): array
    {
        $query = "SELECT cleverreach_configs.value 
FROM cleverreach_configs
WHERE cleverreach_configs.key IN 
('CLEVERREACH_ACCESS_TOKEN', 'CLEVERREACH_REFRESH_TOKEN', 'CLEVERREACH_ACCESS_TOKEN_EXPIRATION_TIME')
GROUP BY cleverreach_configs.key";

        return self::$connection->fetchAll($query);
    }

    /**
     * Gets user info.
     *
     * @return string
     */
    public static function getUserInfo(): string
    {
        $query = "SELECT cleverreach_configs.value
        FROM cleverreach_configs
        WHERE cleverreach_configs.key = 'CLEVERREACH_USER_INFO'";

        return self::$connection->fetchAll($query)[0]['value'];
    }

    /**
     * Get group id.
     *
     * @return array
     */
    public static function getGroupID(): array
    {
        $query = "SELECT value
        FROM cleverreach_configs
        WHERE cleverreach_configs.key = 'CLEVERREACH_INTEGRATION_ID'";

        return self::$connection->fetchAll($query)[0];
    }

    /**
     * Get webhooks data.
     *
     * @return array
     */
    public static function getWebHooksData(): array
    {
        $query = "SELECT cleverreach_configs.key, cleverreach_configs.value
        FROM cleverreach_configs
        WHERE cleverreach_configs.key IN 
        ('CLEVERREACH_EVENT_CALL_TOKEN', 'CLEVERREACH_EVENT_VERIFICATION_TOKEN', 'CLEVERREACH_FORM_EVENT_CALL_TOKEN')";

        return self::$connection->fetchAll($query);
    }

    /**
     * Get dynamic content data.
     *
     * @return array
     */
    public static function getDynamicContentData(): array
    {
        $query = "SELECT cleverreach_configs.key, cleverreach_configs.value
        FROM cleverreach_configs
        WHERE cleverreach_configs.key IN 
        ('CLEVERREACH_PRODUCT_SEARCH_CONTENT_ID', 'CLEVERREACH_PRODUCT_SEARCH_PASSWORD')
        GROUP BY cleverreach_configs.key";

        return self::$connection->fetchAll($query);
    }

    public static function setConnection(Connection $connection): void
    {
        self::$connection = $connection;
    }
}