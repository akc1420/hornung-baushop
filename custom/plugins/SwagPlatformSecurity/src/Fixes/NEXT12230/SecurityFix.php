<?php declare(strict_types=1);

namespace Swag\Security\Fixes\NEXT12230;

use Shopware\Core\Framework\Api\Context\AdminApiSource;
use Shopware\Core\Framework\Api\Controller\Exception\PermissionDeniedException;
use Shopware\Core\PlatformRequest;
use Swag\Security\Components\AbstractSecurityFix;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class SecurityFix extends AbstractSecurityFix
{
    public static function getTicket(): string
    {
        return 'NEXT-12230';
    }

    public static function getMinVersion(): string
    {
        return '6.3.3.0';
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
        $context = $event->getRequest()->attributes->get(PlatformRequest::ATTRIBUTE_CONTEXT_OBJECT);
        if ($context === null) {
            return;
        }

        /** @var AdminApiSource $source */
        $source = $context->getSource();

        if (!($source instanceof AdminApiSource)) {
            return;
        }

        if (basename($event->getRequest()->getRequestUri(), 'me') !== false && $event->getRequest()->getMethod() !== Request::METHOD_PATCH) {
            return;
        }

        $data = $event->getRequest()->request->all();

        if ($source->isAdmin() || $source->isAllowed('user:update')) {
            return;
        }

        if (isset($data['id']) && $source->getUserId() !== $data['id']) {
            throw new HttpException(Response::HTTP_FORBIDDEN,'Permission denied');
        }
    }
}
