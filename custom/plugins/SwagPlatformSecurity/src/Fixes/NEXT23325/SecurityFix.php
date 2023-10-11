<?php declare(strict_types=1);

namespace Swag\Security\Fixes\NEXT23325;

use Swag\Security\Components\AbstractSecurityFix;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class SecurityFix extends AbstractSecurityFix
{
    public static function getTicket(): string
    {
        return 'NEXT-23325';
    }

    public static function getMinVersion(): string
    {
        return '6.1.0';
    }

    public static function getMaxVersion(): ?string
    {
        return '6.4.18.0';
    }

    public static function buildContainer(ContainerBuilder $container): void
    {
        $definition = new Definition(ProductLineItemValidator::class);
        $definition->addTag('shopware.cart.validator');

        $container->addDefinitions([$definition]);
    }
}
