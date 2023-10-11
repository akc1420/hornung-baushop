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

class Migration1679328661RemoveDraftReturnOrder extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1679328661;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement(
            'DELETE `pickware_erp_return_order`
            FROM `pickware_erp_return_order`
            LEFT JOIN `state_machine_state` ON `state_machine_state`.`id` = `pickware_erp_return_order`.`state_id`
            WHERE `state_machine_state`.`technical_name` = "draft";',
        );
        $connection->executeStatement(
            'DELETE `state_machine_history`
            FROM `state_machine_history`
            LEFT JOIN `state_machine_state`
                ON (`state_machine_state`.`id` = `state_machine_history`.`from_state_id` OR `state_machine_state`.`id` = `state_machine_history`.`to_state_id`)
            WHERE `state_machine_state`.`technical_name` = "draft";',
        );
        $connection->executeStatement(
            'DELETE `state_machine_transition`
            FROM `state_machine_transition`
            LEFT JOIN `state_machine_state`
                ON (`state_machine_state`.`id` = `state_machine_transition`.`from_state_id` OR `state_machine_state`.`id` = `state_machine_transition`.`to_state_id`)
            WHERE `state_machine_state`.`technical_name` = "draft";',
        );
        $connection->executeStatement(
            'DELETE FROM `state_machine_state` WHERE `technical_name` = "draft";',
        );
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
