<?php declare(strict_types=1);

namespace Kzph\Simplenote\Migration;

use Shopware\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

class Migration1661505236CreateTableKzphSimplenote extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return (int) 1661505236;
    }

    public function update(Connection $connection): void
	{
        $connection->executeStatement("
			CREATE TABLE IF NOT EXISTS `kzph_simplenote` (
			    `id` BINARY(16) NOT NULL,
				`version_id` BINARY(16) NOT NULL,
			    `order_id` BINARY(16) NOT NULL,
				`order_version_id` BINARY(16) NOT NULL,
				`username` VARCHAR(100) NULL,
				`note` TEXT NULL,
			    `created_at` DATETIME(3) NOT NULL,
			    `updated_at` DATETIME(3) NULL,
			    PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
	}

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
