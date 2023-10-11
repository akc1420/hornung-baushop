<?php declare(strict_types=1);

namespace Swag\Security\Fixes\NEXT14883;

use Shopware\Core\Framework\Api\Context\AdminApiSource;
use Shopware\Core\Framework\Api\Controller\Exception\PermissionDeniedException;
use Shopware\Core\Framework\Routing\KernelListenerPriorities;
use Shopware\Core\PlatformRequest;
use Swag\Security\Components\AbstractSecurityFix;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SecurityFix extends AbstractSecurityFix
{
    private const API_ROUTES = [
        'api.integration.create',
        'api.integration.update',
    ];

    public static function getTicket(): string
    {
        return 'NEXT-14883';
    }

    public static function getMinVersion(): string
    {
        return '6.3.3.0';
    }

    public static function getMaxVersion(): ?string
    {
        return '6.4.0.0';
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => [
                'onKernelRequest', KernelListenerPriorities::KERNEL_CONTROLLER_EVENT_CONTEXT_RESOLVE
            ]
        ];
    }

    public static function buildContainer(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new ReplaceIntegrationDefinitionCompilerPass());
    }


    public function onKernelRequest(ControllerEvent $event): void
    {
        $request = $event->getRequest();

        $route = $request->attributes->get('_route');

        if (!in_array($route, self::API_ROUTES, true)) {
            return;
        }

        $context = $request->attributes->get(PlatformRequest::ATTRIBUTE_CONTEXT_OBJECT);

        /** @var AdminApiSource $source */
        $source = $context->getSource();

        $data = $request->request->all();

        // only an admin is allowed to set the admin field
        if (
            !$source->isAdmin()
            && isset($data['admin'])
        ) {
            if (class_exists(PermissionDeniedException::class)) {
                throw new PermissionDeniedException();
            }

            // In early versions that exception class does not exists yet
            throw new \Swag\Security\Fixes\NEXT14883\PermissionDeniedException();
        }
    }
}
