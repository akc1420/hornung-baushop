<?php

declare(strict_types=1);

namespace Kzph\Simplenote\Migration;

use Shopware\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

class Migration1666859263ModifyTableKzphSimplenote extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return (int) 1666859263;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement("ALTER TABLE `kzph_simplenote` ADD `show_message` INT NOT NULL DEFAULT '0' AFTER `note`;");
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
