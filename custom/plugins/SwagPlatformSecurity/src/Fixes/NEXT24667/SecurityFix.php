<?php

namespace Swag\Security\Fixes\NEXT24667;

use Shopware\Core\Content\Seo\SeoUrlGenerator;
use Shopware\Core\Framework\Rule\ScriptRule;
use Shopware\Core\Framework\Script\Execution\ScriptExecutor;
use Swag\Security\Components\AbstractSecurityFix;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class SecurityFix extends AbstractSecurityFix
{
    public static function getTicket(): string
    {
        return 'NEXT-24667';
    }

    public static function getMinVersion(): string
    {
        return '6.1.0';
    }

    public static function getMaxVersion(): ?string
    {
        return '6.4.18.0';
    }

    public static function boot(ContainerInterface $container): void
    {
        self::loadClasses($container->getParameter('kernel.shopware_version'));
    }

    public static function buildContainer(ContainerBuilder $container): void
    {
        self::loadClasses($container->getParameter('kernel.shopware_version'));

        $securityExtension = new Definition(SecurityExtension::class);
        $securityExtension->setArgument(0, []);
        $securityExtension->addTag('twig.extension');
        $container->setDefinition(SecurityExtension::class, $securityExtension);

        if ($container->hasDefinition('shopware.seo_url.twig')) {
            $container->getDefinition('shopware.seo_url.twig')->addMethodCall('addExtension', [new Reference(SecurityExtension::class)]);
        }
    }

    private static function loadClasses(string $shopwareVersion): void
    {
        if (class_exists(ScriptRule::class, false) === false && version_compare($shopwareVersion, '6.4.12.0', '>=')) {
            require_once __DIR__ . '/ScriptRule.php';
        }

        if (class_exists(ScriptExecutor::class, false) === false && version_compare($shopwareVersion, '6.4.8.0', '>=')) {
            if (version_compare($shopwareVersion, '6.4.12.0', '>=')) {
                require_once __DIR__ . '/Version/6412/ScriptExecutor.php';
            } else if (version_compare($shopwareVersion, '6.4.11.0', '>=')) {
                require_once __DIR__ . '/Version/6411/ScriptExecutor.php';
            } else if (version_compare($shopwareVersion, '6.4.10.0', '>=')) {
                require_once __DIR__ . '/Version/6410/ScriptExecutor.php';
            } else if (version_compare($shopwareVersion, '6.4.9.0', '>=')) {
                require_once __DIR__ . '/Version/649/ScriptExecutor.php';
            } else {
                require_once __DIR__ . '/Version/648/ScriptExecutor.php';
            }
        }

        if (class_exists(SeoUrlGenerator::class, false) === false && version_compare($shopwareVersion, '6.4.6.0', '<')) {
            if (version_compare($shopwareVersion, '6.4.5.0', '>=')) {
                require_once __DIR__ . '/Version/645/SeoUrlGenerator.php';
            } else if (version_compare($shopwareVersion, '6.4.3.0', '>=')) {
                require_once __DIR__ . '/Version/643/SeoUrlGenerator.php';
            } else if (version_compare($shopwareVersion, '6.4.0.0', '>=')) {
                require_once __DIR__ . '/Version/641/SeoUrlGenerator.php';
            } else if (version_compare($shopwareVersion, '6.3.5.0', '>=')) {
                require_once __DIR__ . '/Version/635/SeoUrlGenerator.php';
            } else if (version_compare($shopwareVersion, '6.3.0.0', '>=')) {
                require_once __DIR__ . '/Version/630/SeoUrlGenerator.php';
            } else {
                require_once __DIR__ . '/Version/610/SeoUrlGenerator.php';
            }
        }
    }
}
