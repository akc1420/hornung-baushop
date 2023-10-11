<?php declare(strict_types=1);

namespace Swag\Security\Fixes\NEXT10624;

use Swag\Security\Components\AbstractSecurityFix;

class SecurityFix extends AbstractSecurityFix
{
    public static function getTicket(): string
    {
        return 'NEXT-10624';
    }

    public static function getMinVersion(): string
    {
        return '6.1.0';
    }

    public static function getMaxVersion(): ?string
    {
        return '6.3.1.0';
    }
}
