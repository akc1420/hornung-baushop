<?php

namespace Swag\Security\Fixes\NEXT20305;

use Shopware\Core\Framework\Api\Context\AdminApiSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\PlatformRequest;
use Swag\Security\Components\AbstractSecurityFix;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class SecurityFix extends AbstractSecurityFix
{
    private const API_PROXY_SWITCH_CUSTOMER_PRIVILEGE = 'api_proxy_switch-customer';

    public static function getTicket(): string
    {
        return 'NEXT-20305';
    }

    public static function getMinVersion(): string
    {
        return '6.3.1.0';
    }

    public static function getMaxVersion(): ?string
    {
        return '6.4.8.2';
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER_ARGUMENTS => 'onSwitchCustomer',
            KernelEvents::RESPONSE => 'onGetAdditionalPrivileges',
        ];
    }

    public function onSwitchCustomer(ControllerArgumentsEvent $event): void
    {
        if ($event->getRequest()->attributes->get('_route') !== 'api.proxy.switch-customer') {
            return;
        }

        /** @var Context|null $context */
        $context = $event->getRequest()->attributes->get(PlatformRequest::ATTRIBUTE_CONTEXT_OBJECT);

        if (!$context) {
            return;
        }

        $source = $context->getSource();

        if (!$source instanceof AdminApiSource) {
            return;
        }

        if ($source->isAdmin() || $source->isAllowed(self::API_PROXY_SWITCH_CUSTOMER_PRIVILEGE)) {
            return;
        }

        throw new HttpException(Response::HTTP_FORBIDDEN, 'Missing permission: ' . self::API_PROXY_SWITCH_CUSTOMER_PRIVILEGE);
    }

    public function onGetAdditionalPrivileges(ResponseEvent $event): void
    {
        if ($event->getRequest()->attributes->get('_route') !== 'api.acl.privileges.additional.get') {
            return;
        }

        /** @var string[] $privileges */
        $privileges = \json_decode($event->getResponse()->getContent(), true);

        if (!\in_array(self::API_PROXY_SWITCH_CUSTOMER_PRIVILEGE, $privileges, true)) {
            $privileges[] = self::API_PROXY_SWITCH_CUSTOMER_PRIVILEGE;
        }

        $event->getResponse()->setContent(\json_encode(\array_values($privileges)));
    }
}
