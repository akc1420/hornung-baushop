<?php declare(strict_types=1);

namespace Swag\Security\Fixes\NEXT13247;

use Shopware\Core\Framework\Routing\ApiRequestContextResolver;
use Swag\Security\Components\AbstractSecurityFix;
use Swag\Security\Components\State;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class SecurityFix extends AbstractSecurityFix
{
    public static function getTicket(): string
    {
        return 'NEXT-13247';
    }

    public static function getMinVersion(): string
    {
        return '6.3.0.0';
    }

    public static function getMaxVersion(): ?string
    {
        return '6.3.5.0';
    }

    public static function buildContainer(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new ReplaceSalesChannelContextServicesCompilerPass());
    }
}
