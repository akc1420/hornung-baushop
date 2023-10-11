<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\ShippingBundle;

use Doctrine\DBAL\Connection;
use Pickware\BundleInstaller\BundleInstaller;
use Pickware\DalBundle\DalBundle;
use Pickware\DocumentBundle\DocumentBundle;
use Pickware\InstallationLibrary\DependencyAwareTableDropper;
use Pickware\MoneyBundle\MoneyBundle;
use Pickware\ShippingBundle\Carrier\CarrierAdapterRegistryCompilerPass;
use Pickware\ShippingBundle\Installation\PickwareShippingBundleInstaller;
use Pickware\ShopwareExtensionsBundle\PickwareShopwareExtensionsBundle;
use Pickware\ShopwarePlugins\ShopwareIntegrationTestPlugin\ShopwareIntegrationTestPlugin;
use Pickware\ValidationBundle\PickwareValidationBundle;
use Shopware\Core\Framework\Bundle;
use Shopware\Core\Framework\Migration\MigrationSource;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Struct\Collection;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class PickwareShippingBundle extends Bundle
{
    /**
     * @var class-string<Bundle>[]
     */
    private const ADDITIONAL_BUNDLES = [
        DalBundle::class,
        DocumentBundle::class,
        MoneyBundle::class,
        PickwareShopwareExtensionsBundle::class,
        PickwareValidationBundle::class,
    ];
    public const DOCUMENT_TYPE_TECHNICAL_NAME_DESCRIPTION_MAPPING = [
        PickwareShippingBundle::DOCUMENT_TYPE_TECHNICAL_NAME_SHIPPING_LABEL => 'Versandetikett',
        PickwareShippingBundle::DOCUMENT_TYPE_TECHNICAL_NAME_RETURN_LABEL => 'Retourenetikett',
        PickwareShippingBundle::DOCUMENT_TYPE_TECHNICAL_NAME_CUSTOMS_DECLARATION_CN23 => 'Zollinhaltserklärung CN23',
        PickwareShippingBundle::DOCUMENT_TYPE_TECHNICAL_NAME_STAMP => 'Briefmarke',
        PickwareShippingBundle::DOCUMENT_TYPE_TECHNICAL_NAME_COMMERCIAL_INVOICE => 'Handelsrechnung',
        PickwareShippingBundle::DOCUMENT_TYPE_TECHNICAL_NAME_WAYBILL => 'Frachtbrief',
        PickwareShippingBundle::DOCUMENT_TYPE_TECHNICAL_NAME_OTHER => 'Sonstige',
    ];
    public const DOCUMENT_TYPE_TECHNICAL_NAME_STAMP = 'stamp';
    public const DOCUMENT_TYPE_TECHNICAL_NAME_CUSTOMS_DECLARATION_CN23 = 'customs_declaration_cn23';
    public const DOCUMENT_TYPE_TECHNICAL_NAME_COMMERCIAL_INVOICE = 'commercial_invoice';
    public const DOCUMENT_TYPE_TECHNICAL_NAME_SHIPPING_LABEL = 'shipping_label';
    public const DOCUMENT_TYPE_TECHNICAL_NAME_RETURN_LABEL = 'return_label';
    public const DOCUMENT_TYPE_TECHNICAL_NAME_WAYBILL = 'waybill';
    public const DOCUMENT_TYPE_TECHNICAL_NAME_OTHER = 'other';

    private static ?PickwareShippingBundle $instance = null;
    private static bool $registered = false;
    private static bool $migrationsRegistered = false;

    public static function register(Collection $bundleCollection): void
    {
        if (self::$registered) {
            return;
        }

        $bundleCollection->add(self::getInstance());
        foreach (self::ADDITIONAL_BUNDLES as $bundle) {
            $bundle::register($bundleCollection);
        }

        self::$registered = true;
    }

    public static function registerMigrations(MigrationSource $migrationSource): void
    {
        if (self::$migrationsRegistered) {
            return;
        }
        $migrationsPath = self::getInstance()->getMigrationPath();
        $migrationNamespace = self::getInstance()->getMigrationNamespace();
        $migrationSource->addDirectory($migrationsPath, $migrationNamespace);

        self::$migrationsRegistered = true;

        foreach (self::ADDITIONAL_BUNDLES as $bundle) {
            $bundle::registerMigrations($migrationSource);
        }
    }

    public static function getInstance(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function build(ContainerBuilder $containerBuilder): void
    {
        parent::build($containerBuilder);

        $loader = new XmlFileLoader($containerBuilder, new FileLocator(__DIR__));
        $loader->load('Carrier/DependencyInjection/model.xml');
        $loader->load('Carrier/DependencyInjection/service.xml');
        $loader->load('Carrier/DependencyInjection/subscriber.xml');
        $loader->load('Config/DependencyInjection/command.xml');
        $loader->load('Config/DependencyInjection/model.xml');
        $loader->load('Config/DependencyInjection/service.xml');
        $loader->load('DemodataGeneration/DependencyInjection/command.xml');
        $loader->load('Logging/DependencyInjection/service.xml');
        $loader->load('Mail/DependencyInjection/controller.xml');
        $loader->load('Mail/DependencyInjection/service.xml');
        $loader->load('Notifications/DependencyInjection/service.xml');
        $loader->load('ParcelPacking/DependencyInjection/service.xml');
        $loader->load('ParcelHydration/DependencyInjection/service.xml');
        $loader->load('Shipment/DependencyInjection/controller.xml');
        $loader->load('Shipment/DependencyInjection/model.xml');
        $loader->load('Shipment/DependencyInjection/service.xml');

        $containerBuilder->addCompilerPass(new CarrierAdapterRegistryCompilerPass());

        // Register test services. Should never be loaded in production.
        if (in_array(ShopwareIntegrationTestPlugin::class, $containerBuilder->getParameter('kernel.bundles'), true)) {
            $loader->load('../test/TestEntityCreation/DependencyInjection/service.xml');
        }
    }

    public function shutdown(): void
    {
        parent::shutdown();

        // Shopware may reboot the kernel under certain circumstances (e.g. plugin un-/installation) within a single
        // request. After the kernel was rebooted, our bundles have to be registered again.
        // We reset the registration flag when the kernel is shut down. This will cause the bundles to be registered
        // again in the (re)boot process.
        self::$registered = false;
    }

    public function install(InstallContext $installContext): void
    {
        BundleInstaller::createForContainerAndClass($this->container, self::class)
            ->install(self::ADDITIONAL_BUNDLES, $installContext);
    }

    public function onAfterActivate(InstallContext $installContext): void
    {
        PickwareShippingBundleInstaller::createFromContainer($this->container)->install();

        BundleInstaller::createForContainerAndClass($this->container, self::class)
            ->onAfterActivate(self::ADDITIONAL_BUNDLES, $installContext);
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        if ($uninstallContext->keepUserData()) {
            return;
        }

        DependencyAwareTableDropper::createForContainer($this->container)->dropTables([
            'pickware_shipping_carrier',
            'pickware_shipping_document_shipment_mapping',
            'pickware_shipping_document_tracking_code_mapping',
            'pickware_shipping_shipment',
            'pickware_shipping_shipment_order_mapping',
            'pickware_shipping_shipping_method_config',
            'pickware_shipping_tracking_code',
        ]);

        // We need eight backslashes, as we need to match a single one and double the count for each of the following:
        // 1. The PHP parser
        // 2. The MySQL parser
        // 3. The MySQL pattern matcher (only when using LIKE)
        $this->container->get(Connection::class)
            ->executeStatement("DELETE FROM `migration` WHERE `class` LIKE 'Pickware\\\\\\\\ShippingBundle\\\\\\\\%'");

        PickwareShippingBundleInstaller::createFromContainer($this->container)->uninstall();
        BundleInstaller::createForContainerAndClass($this->container, self::class)->uninstall($uninstallContext);
    }
}
