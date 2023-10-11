<?php

namespace Swag\Security\Fixes\NEXT21034;

use Doctrine\DBAL\Connection;
use Shopware\Core\System\SalesChannel\Context\CartRestorer;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextRestorer;
use Swag\Security\Components\AbstractSecurityFix;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class SecurityFix extends AbstractSecurityFix
{
    public static function getTicket(): string
    {
        return 'NEXT-21034';
    }

    public static function getMinVersion(): string
    {
        return '6.3.4.0';
    }

    public static function getMaxVersion(): ?string
    {
        return '6.4.10.1';
    }

    public static function buildContainer(ContainerBuilder $container): void
    {
        // New way to restore
        if ($container->hasDefinition(CartRestorer::class)) {
            $decorated = new Definition(FixCartRestorer::class);
            $decorated->setArguments([
                $container->getDefinition(CartRestorer::class)->getArguments(),
                new Reference(FixCartRestorer::class . '.inner'),
                new Reference(Connection::class),
            ]);

            $decorated->setDecoratedService(CartRestorer::class);
            $container->setDefinition(FixCartRestorer::class, $decorated);
        }

        // Old way
        if ($container->hasDefinition(SalesChannelContextRestorer::class) && empty($container->getDefinition(SalesChannelContextRestorer::class)->getTags())) {
            $decorated = new Definition(FixSalesChannelContextRestorer::class);
            $decorated->setArguments([
                $container->getDefinition(SalesChannelContextRestorer::class)->getArguments(),
                new Reference(FixSalesChannelContextRestorer::class . '.inner'),
                new Reference(Connection::class),
            ]);

            $decorated->setDecoratedService(SalesChannelContextRestorer::class);
            $container->setDefinition(FixSalesChannelContextRestorer::class, $decorated);
        }
    }
}
