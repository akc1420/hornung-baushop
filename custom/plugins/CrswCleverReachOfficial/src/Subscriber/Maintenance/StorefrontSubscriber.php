<?php

namespace Crsw\CleverReachOfficial\Subscriber\Maintenance;

use Shopware\Core\SalesChannelRequest;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class StorefrontSubscriber
 *
 * @package Crsw\CleverReachOfficial\Subscriber\Maintenance
 */
class StorefrontSubscriber implements EventSubscriberInterface
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var ParameterBagInterface
     */
    private $params;

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [
                ['maintenanceResolver', 100],
            ],
        ];
    }

    /**
     * StorefrontSubscriber constructor.
     *
     * @param RequestStack $requestStack
     * @param ParameterBagInterface $params
     */
    public function __construct(RequestStack $requestStack, ParameterBagInterface $params)
    {
        $this->requestStack = $requestStack;
        $this->params = $params;
    }

    /**
     * @param RequestEvent $event
     */
    public function maintenanceResolver(RequestEvent $event): void
    {
        if (version_compare($this->params->get('kernel.shopware_version'), '6.4.4', 'lt')) {
            $master = $this->requestStack->getMasterRequest();
        } else {
            $master = $this->requestStack->getMainRequest();
        }

        if (!$master || !$master->attributes->get(SalesChannelRequest::ATTRIBUTE_IS_SALES_CHANNEL_REQUEST)) {
            return;
        }

        $salesChannelMaintenance = $master->attributes
            ->get(SalesChannelRequest::ATTRIBUTE_SALES_CHANNEL_MAINTENANCE);

        if (!$salesChannelMaintenance) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('resolved-uri');

        if (strpos($route, 'cleverreach') !== false) {
            $master->attributes
                ->set(SalesChannelRequest::ATTRIBUTE_SALES_CHANNEL_MAINTENANCE, false);
        }
    }
}
