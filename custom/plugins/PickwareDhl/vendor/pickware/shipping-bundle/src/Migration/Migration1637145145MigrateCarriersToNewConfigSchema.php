<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\ShippingBundle\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1637145145MigrateCarriersToNewConfigSchema extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1637145145;
    }

    public function update(Connection $db): void
    {
        $carriers = $db->fetchAllAssociative(
            'SELECT
                `technical_name`,
                `config_default_values`,
                `config_options`
            FROM `pickware_shipping_carrier`',
        );

        $migratedCarriers = [];
        foreach ($carriers as $carrier) {
            $migratedCarriers[] = [
                'technicalName' => $carrier['technical_name'],
                'configDefaultValues' => ['shipmentConfig' => json_decode($carrier['config_default_values'], true)],
                'configOptions' => ['shipmentConfig' => json_decode($carrier['config_options'], true)],
            ];
        }

        foreach ($migratedCarriers as $migratedCarrier) {
            $db->executeStatement(
                'UPDATE `pickware_shipping_carrier`
                SET `config_default_values` = :configDefaultValues, `config_options` = :configOptions
                WHERE `technical_name` = :technicalName',
                [
                    'configDefaultValues' => json_encode($migratedCarrier['configDefaultValues']),
                    'configOptions' => json_encode($migratedCarrier['configOptions']),
                    'technicalName' => $migratedCarrier['technicalName'],
                ],
            );
        }
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
