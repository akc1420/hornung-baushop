<?php

namespace Swag\Security\Fixes\NEXT26140;

use Swag\Security\Components\AbstractSecurityFix;
use Swag\Security\Fixes\NEXT24667\SecurityExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SecurityFix extends AbstractSecurityFix
{
    public static function getTicket(): string
    {
        return 'NEXT-26140';
    }

    public static function getMinVersion(): string
    {
        return '6.4.18.0';
    }

    public static function getMaxVersion(): ?string
    {
        return '6.4.20.0';
    }

    public static function buildContainer(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new ReplaceTwigExtensionCompilerPass());
    }

    public static function boot(ContainerInterface $container): void
    {
        if (!class_exists('Shopware\Core\Framework\Adapter\Twig\SecurityExtension', false)) {
            class_alias(SecurityExtension::class, 'Shopware\Core\Framework\Adapter\Twig\SecurityExtension');
        }
    }
}
