<?php declare(strict_types=1);

namespace Swag\Security\Fixes\NEXT10909;

use Swag\Security\Components\AbstractSecurityFix;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SecurityFix extends AbstractSecurityFix
{
    /**
     * @var string
     */
    private $env;

    public function __construct(string $env)
    {
        $this->env = $env;
    }

    public static function getTicket(): string
    {
        return 'NEXT-10909';
    }

    public static function getMinVersion(): string
    {
        return '6.1.0';
    }

    public static function getMaxVersion(): ?string
    {
        return '6.3.2.0';
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest'
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if ($this->env === 'dev') {
            return;
        }

        $event->getRequest()->headers->remove('hot-reload-mode');
    }
}
