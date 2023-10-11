<?php declare(strict_types=1);

namespace Swag\Security\Fixes\NEXT16429;

use Swag\Security\Components\AbstractSecurityFix;

class SecurityFix extends AbstractSecurityFix
{
    public static function getTicket(): string
    {
        return 'NEXT-16429';
    }

    public static function getMaxVersion(): ?string
    {
        return '6.4.3.1';
    }

    public static function getMinVersion(): string
    {
        return '6.3.2.0';
    }
}
