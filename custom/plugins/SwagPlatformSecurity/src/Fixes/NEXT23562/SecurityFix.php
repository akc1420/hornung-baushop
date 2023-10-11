<?php

namespace Swag\Security\Fixes\NEXT23562;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Adapter\Twig\EntityTemplateLoader;
use Shopware\Core\Framework\Feature;
use Swag\Security\Components\AbstractSecurityFix;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class SecurityFix extends AbstractSecurityFix
{
    public static function getTicket(): string
    {
        return 'NEXT-23562';
    }

    public static function getMinVersion(): string
    {
        return '6.1.0';
    }

    public static function getMaxVersion(): ?string
    {
        return '6.4.16.0';
    }

    public static function buildContainer(ContainerBuilder $container): void
    {
        try {
            if (
                $container->hasDefinition(EntityTemplateLoader::class)
                && !array_key_exists('FEATURE_NEXT_10286', Feature::getAll())
            ) {
                $decorated = new Definition(PatchedEntityTemplateLoader::class);
                $decorated->setArguments([
                    $container->getDefinition(EntityTemplateLoader::class)->getArguments(),
                    new Reference(PatchedEntityTemplateLoader::class . '.inner'),
                    new Reference(Connection::class),
                    $_ENV['APP_ENV']
                ]);

                $decorated->setDecoratedService(EntityTemplateLoader::class);
                $container->setDefinition(PatchedEntityTemplateLoader::class, $decorated);
            }
        } catch (\Throwable $e) {
            // Feature class has some errors in 6.3.2
        }

        $container->addCompilerPass(new TwigLoaderConfigCompilerPassPatch());
    }
}
