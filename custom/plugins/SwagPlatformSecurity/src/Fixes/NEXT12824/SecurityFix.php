<?php declare(strict_types=1);

namespace Swag\Security\Fixes\NEXT12824;

use Shopware\Core\Framework\Routing\KernelListenerPriorities;
use Swag\Security\Components\AbstractSecurityFix;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SecurityFix extends AbstractSecurityFix
{
    public static function getTicket(): string
    {
        return 'NEXT-12824';
    }

    public static function getMinVersion(): string
    {
        return '6.1.0';
    }

    public static function getMaxVersion(): ?string
    {
        return '6.3.5.0';
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => [
                'onKernelRequest', KernelListenerPriorities::KERNEL_CONTROLLER_EVENT_PRIORITY_AUTH_VALIDATE_PRE
            ]
        ];
    }

    public function onKernelRequest(ControllerEvent $event): void
    {
        $request = $event->getRequest();

        if (
            $request->attributes->get('_route') !== 'api.action.download.document' &&
            $request->attributes->get('_route') !== 'api.action.document.preview'
        ) {
            return;
        }

        $request->attributes->set('auth_required', true);
    }
}
