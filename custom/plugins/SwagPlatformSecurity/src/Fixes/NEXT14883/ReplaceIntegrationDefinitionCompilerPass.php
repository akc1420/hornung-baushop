<?php declare(strict_types=1);

namespace Swag\Security\Fixes\NEXT14883;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ReplaceIntegrationDefinitionCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (version_compare($container->getParameter('kernel.shopware_version'), '6.3.3.0', '>=')) {
            $def = $container->getDefinition(\Shopware\Core\System\Integration\IntegrationDefinition::class);
            $def->setClass(IntegrationDefinition::class);
        }
    }
}
