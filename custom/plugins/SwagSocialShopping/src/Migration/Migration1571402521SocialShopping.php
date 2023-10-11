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

class Migration1571402521SocialShopping extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1571402521;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `swag_social_shopping_sales_channel` (
                `id` BINARY(16) NOT NULL,
                `sales_channel_id` BINARY(16) NOT NULL,
                `product_stream_id` BINARY(16) NULL,
                `currency_id` BINARY(16) NULL,
                `sales_channel_domain_id` BINARY(16) NOT NULL,
                `network` VARCHAR(255) NOT NULL,
                `configuration` JSON NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                CONSTRAINT `json.swag_social_shopping_sales_channel.configuration` CHECK (JSON_VALID(`configuration`)),
                KEY `fk.swag_social_shopping_sales_channel.sales_channel_id` (`sales_channel_id`),
                KEY `fk.swag_social_shopping_sales_channel.product_stream_id` (`product_stream_id`),
                KEY `fk.swag_social_shopping_sales_channel.currency_id` (`currency_id`),
                KEY `fk.swag_social_shopping_sales_channel.sales_channel_domain_id` (`sales_channel_domain_id`),
                CONSTRAINT `fk.swag_social_shopping_sales_channel.sales_channel_id` FOREIGN KEY (`sales_channel_id`) REFERENCES `sales_channel` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.swag_social_shopping_sales_channel.product_stream_id` FOREIGN KEY (`product_stream_id`) REFERENCES `product_stream` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
                CONSTRAINT `fk.swag_social_shopping_sales_channel.currency_id` FOREIGN KEY (`currency_id`) REFERENCES `currency` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
                CONSTRAINT `fk.swag_social_shopping_sales_channel.sales_channel_domain_id` FOREIGN KEY (`sales_channel_domain_id`) REFERENCES `sales_channel_domain` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
