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
use Pickware\DocumentBundle\Installation\DocumentUninstaller as PickwareDocumentUninstaller;
use Pickware\DocumentBundle\Installation\EnsureDocumentTypeInstallationStep;
use Pickware\ShippingBundle\Config\CommonShippingConfig;
use Pickware\ShippingBundle\Config\ConfigService;
use Pickware\ShippingBundle\ParcelHydration\ParcelHydrator;
use Pickware\ShippingBundle\PickwareShippingBundle;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PickwareShippingBundleInstaller
{
    private Connection $db;
    private SystemConfigService $systemConfigService;
    private PickwareDocumentUninstaller $pickwareDocumentUninstaller;

    public function __construct(
        Connection $db,
        SystemConfigService $systemConfigService,
        PickwareDocumentUninstaller $pickwareDocumentUninstaller
    ) {
        $this->db = $db;
        $this->systemConfigService = $systemConfigService;
        $this->pickwareDocumentUninstaller = $pickwareDocumentUninstaller;
    }

    public static function createFromContainer(ContainerInterface $container): self
    {
        return new self(
            $container->get(Connection::class),
            $container->get(SystemConfigService::class),
            PickwareDocumentUninstaller::createForContainer($container),
        );
    }

    public function install(): void
    {
        $this->upsertDocumentTypes();
        $this->upsertExportInformationCustomFields();
        $this->upsertDefaultConfiguration();
    }

    public function uninstall(): void
    {
        $pickwareDocumentTypes = array_keys(PickwareShippingBundle::DOCUMENT_TYPE_TECHNICAL_NAME_DESCRIPTION_MAPPING);
        foreach ($pickwareDocumentTypes as $pickwareDocumentType) {
            $this->pickwareDocumentUninstaller->removeDocumentType($pickwareDocumentType);
        }
    }

    private function upsertDefaultConfiguration(): void
    {
        $shippingConfigService = new ConfigService($this->systemConfigService);
        $currentConfig = $shippingConfigService->getConfigForSalesChannel(
            CommonShippingConfig::CONFIG_DOMAIN,
            null,
        );
        $defaultConfig = CommonShippingConfig::createDefault();
        $defaultConfig->apply(new CommonShippingConfig($currentConfig));
        $shippingConfigService->saveConfigForSalesChannel($defaultConfig->getConfig(), null);
    }

    private function upsertDocumentTypes(): void
    {
        (new EnsureDocumentTypeInstallationStep(
            $this->db,
            PickwareShippingBundle::DOCUMENT_TYPE_TECHNICAL_NAME_DESCRIPTION_MAPPING,
        ))->install();
    }

    private function upsertExportInformationCustomFields(): void
    {
        $technicalName = 'pickware_shipping_customs_information';
        $config = [
            'label' => [
                'de-DE' => 'Zollinformationen',
                'en-GB' => 'Customs information',
            ],
            'translated' => true,
        ];

        $fields = [
            [
                'name' => ParcelHydrator::CUSTOM_FIELD_NAME_CUSTOMS_INFORMATION_DESCRIPTION,
                'type' => 'text',
                'config' => [
                    'type' => 'text',
                    'label' => [
                        'de-DE' => 'Beschreibung',
                        'en-GB' => 'Description',
                    ],
                    'helpText' => [
                        'de-DE' => 'Eine detaillierte Beschreibung des Artikels, z.B. "Herren-Baumwollhemden". ' .
                            'Allgemeine Beschreibungen wie z.B. "Ersatzteile", "Muster" oder "Lebensmittel" sind ' .
                            'nicht erlaubt. Wenn du das Feld freilässt, wird der Produktname verwendet.',
                        'en-GB' => 'A detailed description of the item, e.g. "men\'s cotton shirts". General ' .
                            'descriptions e.g. "spare parts", "samples" or "food products" are not permitted. If ' .
                            'you leave this field blank the product name will be used.',
                    ],
                    'placeholder' => [
                        'de-DE' => null,
                        'en-GB' => null,
                    ],
                    'componentName' => 'sw-field',
                    'customFieldType' => 'text',
                    'customFieldPosition' => 1,
                ],
            ],
            [
                'name' => ParcelHydrator::CUSTOM_FIELD_NAME_CUSTOMS_INFORMATION_TARIFF_NUMBER,
                'type' => 'text',
                'config' => [
                    'type' => 'text',
                    'label' => [
                        'de-DE' => 'Zolltarifnummer (nach HS)',
                        'en-GB' => 'HS customs tariff number',
                    ],
                    'helpText' => [
                        'de-DE' => null,
                        'en-GB' => null,
                    ],
                    'placeholder' => [
                        'de-DE' => null,
                        'en-GB' => null,
                    ],
                    'componentName' => 'sw-field',
                    'customFieldType' => 'text',
                    'customFieldPosition' => 4,
                ],
            ],
            [
                'name' => ParcelHydrator::CUSTOM_FIELD_NAME_CUSTOMS_INFORMATION_COUNTRY_OF_ORIGIN,
                'type' => 'text',
                'config' => [
                    'type' => 'text',
                    'label' => [
                        'de-DE' => 'Herkunftsland (2-stelliger Code, z.B. "DE" für Deutschland)',
                        'en-GB' => 'Country of origin (2-characters Code, e.g. "DE" for Germany)',
                    ],
                    'placeholder' => [
                        'de-DE' => null,
                        'en-GB' => null,
                    ],
                    'componentName' => 'pw-shipping-country-select-by-iso-code',
                    'customFieldType' => 'text',
                    'customFieldPosition' => 5,
                ],
            ],
        ];

        $customFieldSetInstaller = new CustomFieldSetInstaller($this->db);
        $customFieldSetInstaller->installCustomFieldSet([
            'name' => $technicalName,
            'config' => $config,
            'customFields' => $fields,
        ], [ProductDefinition::ENTITY_NAME]);
    }
}
