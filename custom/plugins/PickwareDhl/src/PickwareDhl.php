<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareDhl;

use Doctrine\DBAL\Connection;
use Pickware\ApiErrorHandlingBundle\PickwareApiErrorHandlingBundle;
use Pickware\BundleInstaller\BundleInstaller;
use Pickware\DalBundle\DalBundle;
use Pickware\DebugBundle\ShopwarePluginsDebugBundle;
use Pickware\DocumentBundle\DocumentBundle;
use Pickware\InstallationLibrary\DependencyAwareTableDropper;
use Pickware\MoneyBundle\MoneyBundle;
use Pickware\PickwareDhl\Config\DhlConfig;
use Pickware\PickwareDhl\Installation\PickwareDhlInstaller;
use Pickware\ShippingBundle\Carrier\CarrierAdapterRegistryCompilerPass;
use Pickware\ShippingBundle\PickwareShippingBundle;
use Shopware\Core\Framework\Bundle;
use Shopware\Core\Framework\Migration\MigrationCollectionLoader;
use Shopware\Core\Framework\Migration\MigrationRuntime;
use Shopware\Core\Framework\Migration\MigrationSource;
use Shopware\Core\Framework\Parameter\AdditionalBundleParameters;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\Framework\Struct\Collection;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

if (file_exists(__DIR__ . '/../vendor/pickware/dependency-loader/src/DependencyLoader.php')) {
    require_once __DIR__ . '/../vendor/pickware/dependency-loader/src/DependencyLoader.php';
}

class PickwareDhl extends Plugin
{
    /**
     * @var class-string<Bundle>[]
     */
    private const ADDITIONAL_BUNDLES = [
        DalBundle::class,
        DocumentBundle::class,
        MoneyBundle::class,
        PickwareApiErrorHandlingBundle::class,
        PickwareShippingBundle::class,
        ShopwarePluginsDebugBundle::class,
    ];
    public const CARRIER_TECHNICAL_NAME_DHL = 'dhl';

    public function getAdditionalBundles(AdditionalBundleParameters $parameters): array
    {
        if (isset($GLOBALS['PICKWARE_DEPENDENCY_LOADER'])) {
            $kernelParameters = $parameters->getKernelParameters();
            // Ensure the bundle classes can be loaded via auto-loading.
            $GLOBALS['PICKWARE_DEPENDENCY_LOADER']->ensureLatestDependenciesOfPluginsLoaded(
                $kernelParameters['kernel.plugin_infos'],
                $kernelParameters['kernel.project_dir'],
            );
        }

        // For some reason Collection is abstract
        // phpcs:ignore Squiz.WhiteSpace.ScopeClosingBrace.ContentBefore -- PHP CS does not understand the PHP 7 syntax
        $bundleCollection = new class() extends Collection {};
        foreach (self::ADDITIONAL_BUNDLES as $bundle) {
            $bundle::register($bundleCollection);
        }

        return $bundleCollection->getElements();
    }

    public static function getDistPackages(): array
    {
        return include __DIR__ . '/../Packages.php';
    }

    public function build(ContainerBuilder $containerBuilder): void
    {
        parent::build($containerBuilder);

        $loader = new XmlFileLoader($containerBuilder, new FileLocator(__DIR__));
        $loader->load('Adapter/DependencyInjection/service.xml');
        $loader->load('ApiClient/DependencyInjection/service.xml');
        $loader->load('Config/DependencyInjection/service.xml');
        $loader->load('DhlBcpConfigScraper/DependencyInjection/command.xml');
        $loader->load('PreferredDelivery/DependencyInjection/controller.xml');
        $loader->load('PreferredDelivery/DependencyInjection/service.xml');
        $loader->load('SalesChannelContext/DependencyInjection/model.xml');
        $loader->load('SalesChannelContext/DependencyInjection/service.xml');
        $loader->load('LocationFinder/DependencyInjection/controller.xml');
        $loader->load('LocationFinder/DependencyInjection/service.xml');
        $loader->load('Installation/DependencyInjection/service.xml');
        $loader->load('ReturnLabel/DependencyInjection/service.xml');

        $containerBuilder->addCompilerPass(new CarrierAdapterRegistryCompilerPass());
    }

    public function install(InstallContext $installContext): void
    {
        $this->loadDependenciesForSetup();

        $this->executeMigrationsOfBundles();

        BundleInstaller::createForContainerAndClass($this->container, self::class)
            ->install(self::ADDITIONAL_BUNDLES, $installContext);
    }

    public function update(UpdateContext $updateContext): void
    {
        $this->loadDependenciesForSetup();

        $this->executeMigrationsOfBundles();

        BundleInstaller::createForContainerAndClass($this->container, self::class)
            ->install(self::ADDITIONAL_BUNDLES, $updateContext);
    }

    private function executeMigrationsOfBundles(): void
    {
        // All the services required for migration execution are private in the DI-Container. As a workaround the
        // services are instantiated explicitly here.
        $db = $this->container->get(Connection::class);
        // See vendor/symfony/monolog-bundle/Resources/config/monolog.xml on how the logger is defined.
        $logger = new Logger('app');
        $logger->useMicrosecondTimestamps($this->container->getParameter('monolog.use_microseconds'));
        $migrationCollectionLoader = new MigrationCollectionLoader($db, new MigrationRuntime($db, $logger));
        $migrationSource = new MigrationSource('PickwareDhl');

        foreach (self::ADDITIONAL_BUNDLES as $bundle) {
            $bundle::registerMigrations($migrationSource);
        }
        $migrationCollectionLoader->addSource($migrationSource);

        foreach ($migrationCollectionLoader->collectAll() as $migrationCollection) {
            $migrationCollection->sync();
            $migrationCollection->migrateInPlace();
        }
    }

    public function postInstall(InstallContext $installContext): void
    {
        $installer = PickwareDhlInstaller::initFromContainer($this->container);
        $installer->postInstall($installContext->getContext());
    }

    public function postUpdate(UpdateContext $updateContext): void
    {
        $installer = PickwareDhlInstaller::initFromContainer($this->container);
        $installer->postUpdate($updateContext->getContext());

        if ($updateContext->getPlugin()->isActive()) {
            $this->container
                ->get('pickware_dhl.bundle_supporting_asset_service')
                ->copyAssetsFromBundle('PickwareShippingBundle');
            $this->migrateDocumentsOfPluginFileSystemToDocumentBundleFileSystem();

            BundleInstaller::createForContainerAndClass($this->container, self::class)
                ->onAfterActivate(self::ADDITIONAL_BUNDLES, $updateContext);
        }
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        if ($uninstallContext->keepUserData()) {
            return;
        }

        $this->loadDependenciesForSetup();

        DependencyAwareTableDropper::createForContainer($this->container)->dropTables([
            'pickware_dhl_sales_channel_api_context',
            // These are actually only tables from old plugin versions. We still remove them here just in case.
            'pickware_dhl_carrier',
            'pickware_dhl_document',
            'pickware_dhl_document_page_format',
            'pickware_dhl_document_shipment_mapping',
            'pickware_dhl_document_tracking_code_mapping',
            'pickware_dhl_document_type',
            'pickware_dhl_shipment',
            'pickware_dhl_shipment_order_delivery_mapping',
            'pickware_dhl_shipment_order_mapping',
            'pickware_dhl_shipping_method_config',
            'pickware_dhl_tracking_code',
        ]);

        $this->container->get(Connection::class)->executeStatement(
            'DELETE FROM system_config
            WHERE configuration_key LIKE :domain',
            ['domain' => DhlConfig::CONFIG_DOMAIN . '.%'],
        );

        PickwareDhlInstaller::initFromContainer($this->container)->uninstall($uninstallContext);
        BundleInstaller::createForContainerAndClass($this->container, self::class)->uninstall($uninstallContext);
    }

    public function activate(ActivateContext $activateContext): void
    {
        $this->container->get('pickware_dhl.bundle_supporting_asset_service')->copyAssetsFromBundle('PickwareShippingBundle');
        $this->migrateDocumentsOfPluginFileSystemToDocumentBundleFileSystem();

        BundleInstaller::createForContainerAndClass($this->container, self::class)
            ->onAfterActivate(self::ADDITIONAL_BUNDLES, $activateContext);
    }

    private function migrateDocumentsOfPluginFileSystemToDocumentBundleFileSystem(): void
    {
        $this->container->get(
            'pickware_dhl.plugin_filesystem_to_document_bundle_filesystem_migrator',
        )->moveDirectory('documents');
    }

    /**
     * Run the dependency loader for a setup step like install/update/uninstall
     *
     * When executing one of these steps but no Pickware plugin is activated, the dependency loader did never run until
     * the call of the corresponding method. You can trigger it with a call of this method.
     */
    private function loadDependenciesForSetup(): void
    {
        if (isset($GLOBALS['PICKWARE_DEPENDENCY_LOADER'])) {
            $plugins = $this->container->get('kernel')->getPluginLoader()->getPluginInfos();
            $projectDir = $this->container->getParameter('kernel.project_dir');
            $GLOBALS['PICKWARE_DEPENDENCY_LOADER']->ensureLatestDependenciesOfPluginsLoaded($plugins, $projectDir);
        }
    }
}
