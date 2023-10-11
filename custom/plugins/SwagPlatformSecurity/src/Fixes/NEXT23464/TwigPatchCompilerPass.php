<?php declare(strict_types=1);

namespace Swag\Security\Fixes\NEXT23464;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class TwigPatchCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('twig.loader.native_filesystem')) {
            $definition = $container->getDefinition('twig.loader.native_filesystem');

            // Interface was removed in twig 3, so if it exists we need to be compatible with twig 2
            if (\interface_exists('Twig\Loader\ExistsLoaderInterface')) {
                $definition->setClass(PatchedTwig2Loader::class);
            } else {
                $definition->setClass(PatchedTwig3Loader::class);
            }
        }
    }
}
