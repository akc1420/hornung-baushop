<?php

namespace Swag\Security\Fixes\NEXT20348;

use Shopware\Storefront\Framework\Cache\CacheStore;
use Swag\Security\Components\AbstractSecurityFix;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class SecurityFix extends AbstractSecurityFix
{
    public static function getTicket(): string
    {
        return 'NEXT-20348';
    }

    public static function getMinVersion(): string
    {
        return '6.4.8.0';
    }

    public static function getMaxVersion(): string
    {
        return '6.4.8.2';
    }

    public static function buildContainer(ContainerBuilder $container): void
    {
        $definition = new Definition(FixCacheStore::class);
        $definition->addArgument(
            new Reference(FixCacheStore::class . '.inner')
        );
        $definition->setDecoratedService(CacheStore::class);

        $container->setDefinition(
            FixCacheStore::class,
            $definition
        );
    }
}
