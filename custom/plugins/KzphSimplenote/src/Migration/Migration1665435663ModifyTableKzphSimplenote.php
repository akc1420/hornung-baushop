<?php declare(strict_types=1);

namespace Kzph\Simplenote\Migration;

use Shopware\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

class Migration1665435663ModifyTableKzphSimplenote extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return (int) 1665435663;
    }

    public function update(Connection $connection): void
	{
        $connection->executeStatement("ALTER TABLE `kzph_simplenote` DROP `version_id`, DROP `order_version_id`;");
        $connection->executeStatement("ALTER TABLE `kzph_simplenote` CHANGE `order_id` `entity_id` BINARY(16) NOT NULL;");
        $connection->executeStatement("ALTER TABLE `kzph_simplenote` ADD `entity_type` VARCHAR(100) NOT NULL AFTER `entity_id`;");
        $connection->executeStatement("UPDATE `kzph_simplenote` SET entity_type = 'order';");
	}

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
