<?php

namespace Swag\Security\Fixes\NEXT23562;

use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Twig\Loader\FilesystemLoader;

class TwigLoaderConfigCompilerPassPatch implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // Add Resources dir
        $fileSystemLoader = $container->findDefinition('twig.loader.native_filesystem');

        $bundlesMetadata = $container->getParameter('kernel.bundles_metadata');

        if (!\is_array($bundlesMetadata)) {
            throw new \RuntimeException('Container parameter "kernel.bundles_metadata" needs to be an array');
        }

        foreach ($bundlesMetadata as $name => $bundle) {
            $resDir = $bundle['path'] . '/Resources';
            if (!file_exists($resDir)) {
                continue;
            }

            $fileSystemLoader->addMethodCall('addPath', [$resDir, $name]);

            if (file_exists($resDir . '/app/storefront/dist')) {
                $fileSystemLoader->addMethodCall('addPath', [$resDir . '/app/storefront/dist', $name]);
            }

        }

        // App templates are only loaded in dev env from files
        // on prod they are loaded from DB as the app files might not exist locally
        if ($container->getParameter('kernel.environment') === 'dev') {
            $this->addAppTemplatePaths($container, $fileSystemLoader);
        }
    }

    private function addAppTemplatePaths(ContainerBuilder $container, Definition $fileSystemLoader): void
    {
        $connection = $container->get(Connection::class);

        try {
            $apps = $connection->fetchAll('SELECT `name`, `path` FROM `app` WHERE `active` = 1');
        } catch (\Doctrine\DBAL\DBALException $e) {
            // If DB is not yet set up correctly we don't need to add app paths
            return;
        }

        $projectDir = $container->getParameter('kernel.project_dir');
        if (!\is_string($projectDir)) {
            throw new \RuntimeException('Container parameter "kernel.project_dir" needs to be a string');
        }

        foreach ($apps as $app) {
            $resourcesDirectory = sprintf('%s/%s/Resources', $projectDir, $app['path']);

            if (!file_exists($resourcesDirectory)) {
                continue;
            }

            if (file_exists($resourcesDirectory)) {
                $fileSystemLoader->addMethodCall('addPath', [$resourcesDirectory, $app['name']]);
            }

        }
    }
}
