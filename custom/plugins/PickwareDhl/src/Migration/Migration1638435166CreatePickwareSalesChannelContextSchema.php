<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareDhl\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1638435166CreatePickwareSalesChannelContextSchema extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1638435166;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `pickware_sales_channel_api_context` (
                `id` BINARY(16) NOT NULL,
                `sales_channel_context_token` VARCHAR(255) NOT NULL,
                `payload` JSON NOT NULL,
                `updated_at` DATETIME(3) NULL,
                `created_at` DATETIME(3) NULL,
                PRIMARY KEY (`sales_channel_context_token`),
                CONSTRAINT `fk.pickware_sales_channel_api_context.context_token`
                    FOREIGN KEY (sales_channel_context_token) REFERENCES sales_channel_api_context (token)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
