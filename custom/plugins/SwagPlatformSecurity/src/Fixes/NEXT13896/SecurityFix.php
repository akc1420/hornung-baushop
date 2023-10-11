<?php

namespace Swag\Security\Fixes\NEXT13896;

use Shopware\Core\Framework\Api\Context\AdminApiSource;
use Shopware\Core\Framework\Api\Controller\Exception\PermissionDeniedException;
use Shopware\Core\Framework\Api\Exception\MissingPrivilegeException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\KernelListenerPriorities;
use Shopware\Core\PlatformRequest;
use Swag\Security\Components\AbstractSecurityFix;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SecurityFix extends AbstractSecurityFix
{
    public static function getTicket(): string
    {
        return 'NEXT-13896';
    }

    public static function getMinVersion(): string
    {
        return '6.3.0.0';
    }

    public static function getMaxVersion(): ?string
    {
        return '6.3.5.1';
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => ['onController', KernelListenerPriorities::KERNEL_CONTROLLER_EVENT_SCOPE_VALIDATE]
        ];
    }

    public function onController(ControllerEvent $event)
    {
        $route = $event->getRequest()->attributes->get('_route', '');

        if (strpos($route, 'api.action.plugin') === false) {
            return;
        }

        /** @var Context $context */
        $context = $event->getRequest()->attributes->get(PlatformRequest::ATTRIBUTE_CONTEXT_OBJECT);

        if ($context->getScope() !== 'user') {
            return;
        }

        /** @var AdminApiSource $source */
        $source = $context->getSource();

        if ($source->isAdmin()) {
            return;
        }

        if ($context->isAllowed('system.plugin_maintain')) {
            return;
        }

        $reflectionClass = new \ReflectionClass(MissingPrivilegeException::class);
        $parameter = $reflectionClass->getConstructor()->getParameters()[0];

        if ($parameter->getType()->getName() === 'string') {
            throw new MissingPrivilegeException('system.plugin_maintain');
        }

        throw new MissingPrivilegeException(['system.plugin_maintain']);
    }
}
