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

/**
 * DHL has updated their incoterms used for european and international shipments, which makes the old distinction
 * between frankaturs (european shipments) and incoterms (international shipments) obsolete. This migration updates the
 * default shipment configs of all DHL related shipping methods by renaming the respective config fields
 *   - "frankatur" -> "incotermEurope"
 *   - "incoterm" -> "incotermInternational"
 * and ensuring that the stored default values are still valid incoterms.
 */
class Migration1632302999UpdateIncotermRelatedConfigElementsInShippingMethodConfigs extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1632302999;
    }

    public function update(Connection $connection): void
    {
        $shippingMethodConfigs = $connection->fetchAllAssociative(
            'SELECT
                LOWER(HEX(id)) AS id,
                shipment_config AS config
            FROM `pickware_shipping_shipping_method_config`
            WHERE carrier_technical_name = :carrierTechnicalName',
            ['carrierTechnicalName' => 'dhl'],
        );

        foreach ($shippingMethodConfigs as $shippingMethodConfig) {
            $config = json_decode($shippingMethodConfig['config'], true);
            $this->migrateIncotermRelatedConfigElement(
                $config,
                'frankatur',
                'incotermEurope',
                [
                    'DDP',
                    'DXV',
                    'DAP',
                    'DDX',
                    'CPT',
                ],
            );
            $this->migrateIncotermRelatedConfigElement(
                $config,
                'incoterm',
                'incotermInternational',
                [
                    'DDP',
                    'DXV',
                    'DAP',
                    'DDX',
                ],
            );

            $connection->executeStatement(
                'UPDATE `pickware_shipping_shipping_method_config`
                SET shipment_config = :config
                WHERE id = :shippingMethodConfigId',
                [
                    'config' => json_encode($config),
                    'shippingMethodConfigId' => hex2bin($shippingMethodConfig['id']),
                ],
            );
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }

    /**
     * Migrates a given incoterm related config element (actually 'incoterm' or 'frankatur') by renaming the config
     * element to its new name and ensuring that its value is still valid. If the value is now longer valid it is
     * cleared by setting it to null.
     *
     * @retrun void
     */
    private function migrateIncotermRelatedConfigElement(
        array &$config,
        string $elementKey,
        string $newElementKey,
        array $validElementValues
    ): void {
        if (array_key_exists($elementKey, $config)) {
            $value = $config[$elementKey];
            if ($value !== null && !in_array($value, $validElementValues)) {
                $value = null;
            }
            $config[$newElementKey] = $value;
            unset($config[$elementKey]);
        }
    }
}
