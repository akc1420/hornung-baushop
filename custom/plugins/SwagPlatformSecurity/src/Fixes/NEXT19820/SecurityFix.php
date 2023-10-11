<?php

namespace Swag\Security\Fixes\NEXT19820;

use Doctrine\DBAL\Connection;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextPersister;
use Swag\Security\Components\AbstractSecurityFix;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class SecurityFix extends AbstractSecurityFix
{
    public static function getTicket(): string
    {
        return 'NEXT-19820';
    }

    public static function getMinVersion(): string
    {
        return '6.1.0';
    }

    public static function getMaxVersion(): ?string
    {
        return '6.4.9.0';
    }

    public static function buildContainer(ContainerBuilder $container): void
    {
        $definition = new Definition(CustomerTokenSubscriber::class);
        $definition->setPublic(true);
        $definition->addTag('kernel.event_subscriber');
        $definition->addArgument(new Reference(Connection::class));
        $definition->addArgument(new Reference('request_stack'));
        $definition->addArgument(new Reference(SalesChannelContextPersister::class));

        // Overwrite the core one if exists. Can be missing on 6.2.0
        $container->setDefinition('Shopware\Core\Checkout\Customer\Subscriber\CustomerTokenSubscriber', $definition);
    }
}