<?php declare(strict_types=1);

namespace Swag\Security\Fixes\NEXT12359;

use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\Framework\Api\Controller\Exception\PermissionDeniedException;
use Shopware\Core\PlatformRequest;
use Swag\Security\Components\AbstractSecurityFix;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SecurityFix extends AbstractSecurityFix
{
    private const STOREFRONT_ROUTES = [
        'frontend.account.order.single.document',
        'frontend.account.order.single.page',
    ];

    public static function getTicket(): string
    {
        return 'NEXT-12359';
    }

    public static function getMinVersion(): string
    {
        return '6.2.0';
    }

    public static function getMaxVersion(): ?string
    {
        return '6.3.4.0';
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER_ARGUMENTS => 'onKernelRequest'
        ];
    }

    public function onKernelRequest(ControllerArgumentsEvent $event): void
    {
        $request = $event->getRequest();

        if (!in_array($request->attributes->get('_route'), self::STOREFRONT_ROUTES, true)) {
            return;
        }

        $context = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);

        if ($context->getCustomer() === null) {
            throw new CustomerNotLoggedInException();
        }
    }
}
