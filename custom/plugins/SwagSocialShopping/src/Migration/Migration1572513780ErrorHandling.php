<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagSocialShopping\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1572513780ErrorHandling extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1572513780;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement(
            '
            CREATE TABLE IF NOT EXISTS `swag_social_shopping_product_error` (
                `id` BINARY(16) NOT NULL,
                `product_id` BINARY(16) NOT NULL,
                `product_version_id` BINARY(16) NOT NULL,
                `sales_channel_id` BINARY(16) NOT NULL,
                `errors` JSON NOT NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`,`product_version_id`),
                CONSTRAINT `json.swag_social_shopping_product_error.errors` CHECK (JSON_VALID(`errors`)),
                KEY `fk.swag_social_shopping_product_error.sales_channel_id` (`sales_channel_id`),
                KEY `fk.swag_social_shopping_product_error.product_id` (`product_id`,`product_version_id`),
                CONSTRAINT `fk.swag_social_shopping_product_error.sales_channel_id` FOREIGN KEY (`sales_channel_id`) REFERENCES `sales_channel` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.swag_social_shopping_product_error.product_id` FOREIGN KEY (`product_id`,`product_version_id`) REFERENCES `product` (`id`,`version_id`) ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        '
        );

        if (!$this->checkIfColumnExist('swag_social_shopping_sales_channel', 'is_validating', $connection)) {
            $connection->executeStatement(
                'ALTER TABLE `swag_social_shopping_sales_channel` ADD COLUMN `is_validating` bool DEFAULT false'
            );
        }

        if (!$this->checkIfColumnExist('swag_social_shopping_sales_channel', 'last_validation', $connection)) {
            $connection->executeStatement(
                'ALTER TABLE `swag_social_shopping_sales_channel` ADD COLUMN `last_validation` DATETIME(3) NULL'
            );
        }
    }

    public function updateDestructive(Connection $connection): void
    {
    }

    /**
     * Helper function to check if a column exists which is needed during update
     */
    private function checkIfColumnExist(string $tableName, string $columnName, Connection $connection): bool
    {
        $sql = <<<SQL
SELECT column_name
FROM information_schema.columns
WHERE table_name = :tableName
    AND column_name = :columnName
    AND table_schema = DATABASE();
SQL;
        $columnNameInDb = $connection->executeQuery(
            $sql,
            ['tableName' => $tableName, 'columnName' => $columnName]
        )->fetchOne();

        return $columnNameInDb === $columnName;
    }
}
