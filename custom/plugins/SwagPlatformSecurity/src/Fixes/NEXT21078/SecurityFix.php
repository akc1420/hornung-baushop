<?php

namespace Swag\Security\Fixes\NEXT21078;

use Swag\Security\Components\AbstractSecurityFix;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SecurityFix extends AbstractSecurityFix
{
    public static function getTicket(): string
    {
        return 'NEXT-21078';
    }

    public static function getMinVersion(): string
    {
        return '6.4.9.0';
    }

    public static function getMaxVersion(): ?string
    {
        return '6.4.10.1';
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onController'
        ];
    }

    public function onController(ControllerEvent $event): void
    {
        if ($event->getRequest()->attributes->get('_route') === 'api.action.extension-sdk.run-action') {
            throw new \RuntimeException('Feature is not active');
        }
    }
}
