<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagSocialShopping\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1652180224CreateTablesWithReferralCodes extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1652180224;
    }

    public function update(Connection $connection): void
    {
        $statement = <<<SQL
            CREATE TABLE IF NOT EXISTS `swag_social_shopping_order` (
                `id` BINARY(16) NOT NULL,
                `order_id` BINARY(16) NOT NULL,
                `order_version_id` BINARY(16) NOT NULL,
                `referral_code` BINARY(16) NOT NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                UNIQUE INDEX `swag_social_shopping_order` (`order_id`, `order_version_id`),
                INDEX `swag_social_shopping_order.order_id` (`order_id`),
                INDEX `swag_social_shopping_order.referral_code` (`referral_code`),

                KEY `fk.swag_social_shopping_order.order_id` (`order_id`, `order_version_id`),
                KEY `fk.swag_social_shopping_order.referral_code` (`referral_code`),

                CONSTRAINT `fk.swag_social_shopping_order.order_id` FOREIGN KEY (`order_id`, `order_version_id`) REFERENCES `order` (`id`, `version_id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.swag_social_shopping_order.referral_code` FOREIGN KEY (`referral_code`) REFERENCES `sales_channel` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `swag_social_shopping_customer` (
                `id` BINARY(16) NOT NULL,
                `customer_id` BINARY(16) NOT NULL,
                `referral_code` BINARY(16) NOT NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                INDEX `swag_social_shopping_customer.customer_id` (`customer_id`),
                INDEX `swag_social_shopping_customer.referral_code` (`referral_code`),

                KEY `fk.swag_social_shopping_customer.customer_id` (`customer_id`),
                KEY `fk.swag_social_shopping_customer.referral_code` (`referral_code`),

                CONSTRAINT `fk.swag_social_shopping_customer.customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.swag_social_shopping_customer.referral_code` FOREIGN KEY (`referral_code`) REFERENCES `sales_channel` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        SQL;

        $connection->executeStatement($statement);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
