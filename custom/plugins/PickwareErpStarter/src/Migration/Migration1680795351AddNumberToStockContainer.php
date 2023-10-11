<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareErpStarter\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1680795351AddNumberToStockContainer extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1680795351;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement(
            'ALTER TABLE `pickware_erp_stock_container`
            ADD COLUMN `number` VARCHAR(255) NULL,
            ADD CONSTRAINT `pickware_erp_stock_container.uidx.number`
                UNIQUE (`number`)',
        );
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
