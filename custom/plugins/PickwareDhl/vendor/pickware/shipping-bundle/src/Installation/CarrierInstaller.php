<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\ShippingBundle\Installation;

use Doctrine\DBAL\Connection;
use Pickware\ShippingBundle\Carrier\Carrier;

class CarrierInstaller
{
    private Connection $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function installCarrier($carrier): void
    {
        if (is_array($carrier)) {
            $carrier = new Carrier(
                $carrier['technicalName'],
                $carrier['name'],
                $carrier['abbreviation'],
                $carrier['configDomain'],
                $carrier['shipmentConfigDescriptionFilePath'] ?? null,
                $carrier['storefrontConfigDescriptionFilePath'] ?? null,
                $carrier['returnShipmentConfigDescriptionFilePath'] ?? null,
                $carrier['defaultParcelPackingConfiguration'] ?? null,
                $carrier['returnLabelMailTemplateTechnicalName'] ?? null,
                $carrier['batchSize'] ?? 1,
            );
        }

        $this->db->executeStatement(
            'INSERT INTO `pickware_shipping_carrier` (
                `technical_name`,
                `name`,
                `abbreviation`,
                `config_domain`,
                `shipment_config_default_values`,
                `shipment_config_options`,
                `storefront_config_default_values`,
                `storefront_config_options`,
                `return_shipment_config_default_values`,
                `return_shipment_config_options`,
                `default_parcel_packing_configuration`,
                `return_label_mail_template_type_technical_name`,
                `batch_size`,
                `created_at`
            ) VALUES (
                :technicalName,
                :name,
                :abbreviation,
                :configDomain,
                :shipmentConfigDefaultValues,
                :shipmentConfigOptions,
                :storefrontConfigDefaultValues,
                :storefrontConfigOptions,
                :returnShipmentConfigDefaultValues,
                :returnShipmentConfigOptions,
                :defaultParcelPackingConfiguration,
                :returnLabelMailTemplateTechnicalName,
                :batchSize,
                NOW(3)
            ) ON DUPLICATE KEY UPDATE
                `name` = VALUES(`name`),
                `abbreviation` = VALUES(`abbreviation`),
                `config_domain` = VALUES(`config_domain`),
                `shipment_config_default_values` = VALUES(`shipment_config_default_values`),
                `shipment_config_options` = VALUES(`shipment_config_options`),
                `storefront_config_default_values` = VALUES(`storefront_config_default_values`),
                `storefront_config_options` = VALUES(`storefront_config_options`),
                `return_shipment_config_default_values` = VALUES(`return_shipment_config_default_values`),
                `return_shipment_config_options` = VALUES(`return_shipment_config_options`),
                `default_parcel_packing_configuration` = VALUES(`default_parcel_packing_configuration`),
                `return_label_mail_template_type_technical_name` = VALUES(`return_label_mail_template_type_technical_name`),
                `batch_size` = VALUES(`batch_size`),
                `updated_at` = NOW(3)',
            [
                'technicalName' => $carrier->getTechnicalName(),
                'name' => $carrier->getName(),
                'abbreviation' => $carrier->getAbbreviation(),
                'configDomain' => $carrier->getConfigDomain(),
                'shipmentConfigDefaultValues' => json_encode($carrier->getShipmentConfigDescription()->getDefaultValues()),
                'shipmentConfigOptions' => json_encode($carrier->getShipmentConfigDescription()->getOptions()),
                'storefrontConfigDefaultValues' => json_encode($carrier->getStorefrontConfigDescription()->getDefaultValues()),
                'storefrontConfigOptions' => json_encode($carrier->getStorefrontConfigDescription()->getOptions()),
                'returnShipmentConfigDefaultValues' => json_encode($carrier->getReturnShipmentConfigDescription()->getDefaultValues()),
                'returnShipmentConfigOptions' => json_encode($carrier->getReturnShipmentConfigDescription()->getOptions()),
                'defaultParcelPackingConfiguration' => json_encode($carrier->getDefaultParcelPackingConfiguration()),
                'returnLabelMailTemplateTechnicalName' => $carrier->getReturnLabelMailTemplateTechnicalName(),
                'batchSize' => $carrier->getBatchSize(),
            ],
        );
    }
}
