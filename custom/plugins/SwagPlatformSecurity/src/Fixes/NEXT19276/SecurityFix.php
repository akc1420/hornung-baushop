<?php

namespace Swag\Security\Fixes\NEXT19276;

use Shopware\Storefront\Controller\CartLineItemController as CoreCartLineItemController;
use Swag\Security\Components\AbstractSecurityFix;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class SecurityFix extends AbstractSecurityFix
{
    public static function getTicket(): string
    {
        return 'NEXT-19276';
    }

    public static function getMinVersion(): string
    {
        return '6.2.3';
    }

    public static function getMaxVersion(): ?string
    {
        return '6.4.8.0';
    }

    public static function buildContainer(ContainerBuilder $container): void
    {
        $definition = new Definition(CartLineItemController::class);
        $definition->setPublic(true);
        $definition->setDecoratedService(CoreCartLineItemController::class);
        $definition->addArgument(new Reference('session'));
        $definition->addArgument(new Reference('request_stack'));
        $definition->addArgument(new Reference(CartLineItemController::class . '.inner'));
        $definition->addMethodCall('setContainer', [new Reference('service_container')]);
        $definition->addTag('swag.security.fix', ['ticket' => \Swag\Security\Fixes\NEXT19276\SecurityFix::class]);

        $container->setDefinition(CartLineItemController::class, $definition);
    }
}
