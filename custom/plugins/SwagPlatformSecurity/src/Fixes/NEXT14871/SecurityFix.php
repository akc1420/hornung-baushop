<?php

namespace Swag\Security\Fixes\NEXT14871;

use Shopware\Core\Framework\Api\Serializer\JsonEntityEncoder as CoreJsonEntityEncoder;
use Swag\Security\Components\AbstractSecurityFix;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SecurityFix extends AbstractSecurityFix
{
    public static function getTicket(): string
    {
        return 'NEXT-14871';
    }

    public static function getMinVersion(): string
    {
        return '6.1.0';
    }

    public static function getMaxVersion(): ?string
    {
        return '6.4.0.0';
    }

    public static function buildContainer(ContainerBuilder $container): void
    {
        $definition = $container->getDefinition(CoreJsonEntityEncoder::class);

        if (version_compare($container->getParameter('kernel.shopware_version'), '6.4.0.0', '>=')) {
            $definition->setClass(JsonEntityEncoderV2::class);
        } else {
            $definition->setClass(JsonEntityEncoderV1::class);
        }
    }
}
