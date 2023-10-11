<?php declare(strict_types=1);

namespace Swag\Security\Fixes\NEXT9689;

use Swag\Security\Components\AbstractSecurityFix;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SecurityFix extends AbstractSecurityFix
{
    public static function getTicket(): string
    {
        return 'NEXT-9689';
    }

    public static function getMinVersion(): string
    {
        return '6.1.0';
    }

    public static function getMaxVersion(): ?string
    {
        return '6.3.4.0';
    }

    public static function buildContainer(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new ReplaceFileFetcherCompilerPass());
    }
}
