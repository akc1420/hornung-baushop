<?php declare(strict_types=1);

namespace Swag\Security\Fixes\NEXT14744;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class CompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (
            !$container->hasDefinition('Shopware\Core\Framework\Adapter\Command\S3FilesystemVisibilityCommand') &&
            $container->hasDefinition('shopware.filesystem.private') &&
            $container->hasDefinition('shopware.filesystem.public') &&
            $container->hasDefinition('shopware.filesystem.theme') &&
            $container->hasDefinition('shopware.filesystem.sitemap') &&
            $container->hasDefinition('shopware.filesystem.asset')
        ) {
            $definition = new Definition(S3FilesystemVisibilityCommand::class);
            $definition->setArguments([
                new Reference('shopware.filesystem.private'),
                new Reference('shopware.filesystem.public'),
                new Reference('shopware.filesystem.theme'),
                new Reference('shopware.filesystem.sitemap'),
                new Reference('shopware.filesystem.asset')
            ]);
            $definition->addTag('console.command');

            $container->setDefinition(S3FilesystemVisibilityCommand::class, $definition);
        }
    }
}
