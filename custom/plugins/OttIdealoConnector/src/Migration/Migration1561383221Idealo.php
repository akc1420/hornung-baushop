<?php declare(strict_types=1);

namespace Ott\IdealoConnector\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1561383221Idealo extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1552484872;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<'SQL'
            CREATE TABLE IF NOT EXISTS `idealo_order` (
                `id` BINARY(16) NOT NULL,
                `order_id` BINARY(16) NOT NULL,
                `idealo_transaction_id` VARCHAR(255) NOT NULL,
                `created_at` datetime(3) NULL,
                `updated_at` datetime(3) NULL,
                PRIMARY KEY (`id`),
                KEY `IDX_6BA052416796D554` (`order_id`),
                CONSTRAINT `FK_5BA052416796D554` FOREIGN KEY (`order_id`) REFERENCES `order` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            )
                ENGINE = InnoDB
                DEFAULT CHARSET = utf8mb4
                COLLATE = utf8mb4_unicode_ci;
            SQL;
        $connection->query($sql);

        $sql = <<<'SQL'
            CREATE TABLE IF NOT EXISTS `idealo_order_line_item_status` (
                `id` BINARY(16) NOT NULL,
                `idealo_order_id` BINARY(16) NOT NULL,
                `line_item_id` BINARY(16) NOT NULL,
                `status` VARCHAR(255) NOT NULL,
                `created_at` datetime(3) NULL,
                `updated_at` datetime(3) NULL,
                PRIMARY KEY (`id`),
                KEY `IDX_7BA052416796D554` (`idealo_order_id`),
                KEY `IDX_8BA052416796D554` (`line_item_id`),
                CONSTRAINT `FK_9BA052416796D554` FOREIGN KEY (`idealo_order_id`) REFERENCES `idealo_order` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `FK_1BA052416796D554` FOREIGN KEY (`line_item_id`) REFERENCES `order_line_item` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            )
                ENGINE = InnoDB
                DEFAULT CHARSET = utf8mb4
                COLLATE = utf8mb4_unicode_ci;
            SQL;
        $connection->query($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
