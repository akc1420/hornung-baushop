<?php declare(strict_types=1);

namespace Swag\Security\Fixes\NEXT10905;

use Swag\Security\Components\AbstractSecurityFix;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SecurityFix extends AbstractSecurityFix
{
    public static function getTicket(): string
    {
        return 'NEXT-10905';
    }

    public static function getMinVersion(): string
    {
        return '6.1.0';
    }

    public static function getMaxVersion(): ?string
    {
        return '6.3.2.0';
    }
}
