<?php declare(strict_types=1);

namespace Swag\Security\Fixes\NEXT13247;

use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Cart\CartRuleLoader;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Framework\Routing\ApiRequestContextResolver;
use Swag\Security\Components\State;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;

class ReplaceSalesChannelContextServicesCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('shopware.api.store.context_lifetime')) {
            $container->setParameter('shopware.api.store.context_lifetime', 'P1D');
        }

        $container->register(RequestContextResolverSyncTokenDecorator::class, RequestContextResolverSyncTokenDecorator::class)
            ->addArgument(new Reference(State::class))
            ->addArgument(new Reference(RequestContextResolverSyncTokenDecorator::class . '.inner'))
            ->setDecoratedService(ApiRequestContextResolver::class);

        if (!$container->hasDefinition(\Shopware\Core\System\SalesChannel\Context\SalesChannelContextPersister::class)) {
            $container->setDefinition(\Shopware\Core\System\SalesChannel\Context\SalesChannelContextPersister::class, new Definition(SalesChannelContextPersister::class));
        }

        $contextPersisterDefinition = $container->getDefinition(\Shopware\Core\System\SalesChannel\Context\SalesChannelContextPersister::class);
        $contextPersisterDefinition->setClass(SalesChannelContextPersister::class);
        $contextPersisterDefinition->setArguments([
            $contextPersisterDefinition->getArguments(),
            new Reference(State::class),
            new Reference(Connection::class),
            new Reference('event_dispatcher'),
            new Parameter('shopware.api.store.context_lifetime')
        ]);

        if (!$container->hasDefinition(\Shopware\Core\System\SalesChannel\Context\SalesChannelContextService::class)) {
            $container->setDefinition(\Shopware\Core\System\SalesChannel\Context\SalesChannelContextService::class, new Definition(SalesChannelContextService::class));
        }
        $contextServiceDefinition = $container->getDefinition(\Shopware\Core\System\SalesChannel\Context\SalesChannelContextService::class);
        $contextServiceDefinition->setClass(SalesChannelContextService::class);
        $contextServiceDefinition->setArguments([
            $contextServiceDefinition->getArguments(),
            new Reference(State::class),
            new Reference(\Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory::class),
            new Reference(CartRuleLoader::class),
            new Reference(\Shopware\Core\System\SalesChannel\Context\SalesChannelContextPersister::class),
            new Reference(CartService::class),
            new Reference('request_stack')
        ]);
    }
}
