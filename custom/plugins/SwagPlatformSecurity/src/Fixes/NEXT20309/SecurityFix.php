<?php

namespace Swag\Security\Fixes\NEXT20309;

use Shopware\Core\Framework\Event\BeforeSendResponseEvent;
use Swag\Security\Components\AbstractSecurityFix;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SecurityFix extends AbstractSecurityFix
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public static function getTicket(): string
    {
        return 'NEXT-20309';
    }

    public static function getMinVersion(): string
    {
        return '6.1.0';
    }

    public static function getMaxVersion(): string
    {
        return '6.4.8.2';
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeSendResponseEvent::class => 'beforeSendResponse'
        ];
    }

    public function beforeSendResponse(BeforeSendResponseEvent $event): void
    {
        $reverseProxyEnabled = $this->container->hasParameter('storefront.reverse_proxy.enabled') && $this->container->getParameter('storefront.reverse_proxy.enabled');

        if ($reverseProxyEnabled) {
            return;
        }

        $response = $event->getResponse();

        $noStore = $response->headers->getCacheControlDirective('no-store');

        // We don't want that the client will cache the website, if no reverse proxy is configured
        $response->headers->remove('cache-control');
        $response->setPrivate();

        if ($noStore) {
            $response->headers->addCacheControlDirective('no-store');
        } else {
            $response->headers->addCacheControlDirective('no-cache');
        }
    }
}
