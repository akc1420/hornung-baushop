<?php

namespace Swag\Security\Fixes\NEXT26140;

use Swag\Security\Fixes\NEXT24667\SecurityExtension;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ReplaceTwigExtensionCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('Shopware\Core\Framework\Adapter\Twig\SecurityExtension')) {
            return;
        }

        $container
            ->getDefinition('Shopware\Core\Framework\Adapter\Twig\SecurityExtension')
            ->setClass(SecurityExtension::class);
    }
}
