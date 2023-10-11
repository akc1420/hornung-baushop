<?php

declare(strict_types=1);

namespace Kzph\Simplenote\Migration;

use Shopware\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

class Migration1673945260ModifyTableKzphSimplenote extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return (int) 1673945260;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement("ALTER TABLE `kzph_simplenote` ADD `show_desktop` INT NOT NULL DEFAULT '0' AFTER `note`;");
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
