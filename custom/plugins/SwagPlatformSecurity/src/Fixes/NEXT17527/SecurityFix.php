<?php declare(strict_types=1);

namespace Swag\Security\Fixes\NEXT17527;

use Swag\Security\Components\AbstractSecurityFix;

class SecurityFix extends AbstractSecurityFix
{
    public static function getTicket(): string
    {
        return 'NEXT-17527';
    }

    public static function getMinVersion(): string
    {
        return '6.4.0.0';
    }

    public static function getMaxVersion(): ?string
    {
        return '6.4.6.1';
    }
}
