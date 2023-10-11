<?php

declare(strict_types=1);

namespace Swag\Security\Fixes\PPI737;

use Swag\PayPal\Checkout\Payment\Handler\AbstractPaymentHandler as V2AbstractPaymentHandler;
use Swag\PayPal\Checkout\Payment\Handler\EcsSpbHandler as V2EcsSpbHandler;
use Swag\PayPal\Checkout\Payment\Handler\PlusPuiHandler;
use Swag\PayPal\OrdersApi\Patch\PurchaseUnitPatchBuilder;
use Swag\PayPal\Payment\Builder\Util\PriceFormatter;
use Swag\PayPal\Payment\Handler\AbstractPaymentHandler as V1AbstractPaymentHandler;
use Swag\PayPal\Payment\Handler\EcsSpbHandler as V1EcsSpbHandler;
use Swag\PayPal\PayPal\Resource\PaymentResource as V1PaymentResource;
use Swag\PayPal\RestApi\V1\Resource\PaymentResource as V2PaymentResource;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\SwagPayPal;
use Swag\Security\Components\AbstractSecurityFix;
use Swag\Security\Fixes\PPI737\PlusHandler\PlusHandlerV1;
use Swag\Security\Fixes\PPI737\PlusHandler\PlusHandlerV2ToV5;
use Swag\Security\Fixes\PPI737\SpbHandler\SpbHandlerV1;
use Swag\Security\Fixes\PPI737\SpbHandler\SpbHandlerV2ToV5;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Swag\PayPal\Payment\Handler\PlusHandler;

class SecurityFix extends AbstractSecurityFix
{
    public static function getTicket(): string
    {
        return 'PPI-737';
    }

    public static function getMinVersion(): string
    {
        return '6.1.0';
    }

    public static function getMaxVersion(): ?string
    {
        return '6.4.20.0';
    }

    public static function buildContainer(ContainerBuilder $container): void
    {
        if (!\class_exists(SwagPayPal::class)) {
            return;
        }

        $container->addDefinitions(\array_merge(
            self::getV1Definitions(),
            self::getV2ToV5Definitions()
        ));
    }

    /**
     * @return array<string, Definition>
     */
    private static function getV1Definitions(): array
    {
        if (!\class_exists(V1AbstractPaymentHandler::class)) {
            // exclude SwagPayPal >=2
            return [];
        }

        $plusDefinition = new Definition(PlusHandlerV1::class);
        $plusDefinition->setDecoratedService(PlusHandler::class, null, 0, ContainerInterface::IGNORE_ON_INVALID_REFERENCE);
        $plusDefinition->setArguments([
            new Reference(PlusHandlerV1::class . '.inner'),
            new Reference(V1PaymentResource::class)
        ]);

        $spbDefinition = new Definition(SpbHandlerV1::class);
        $spbDefinition->setDecoratedService(V1EcsSpbHandler::class, null, 0, ContainerInterface::IGNORE_ON_INVALID_REFERENCE);
        $spbDefinition->setArguments([
            new Reference(SpbHandlerV1::class . '.inner'),
            new Reference(V1PaymentResource::class)
        ]);

        return [
            PlusHandlerV1::class => $plusDefinition,
            SpbHandlerV1::class => $spbDefinition
        ];
    }

    /**
     * @return array<string, Definition>
     */
    private static function getV2ToV5Definitions(): array
    {
        if (!\class_exists(V2AbstractPaymentHandler::class)) {
            // exclude SwagPayPal >=6 and <2
            return [];
        }

        if (\class_exists(PurchaseUnitPatchBuilder::class) && \method_exists(PurchaseUnitPatchBuilder::class, 'createFinalPurchaseUnitPatch')) {
            // exclude SwagPayPal >= 5.4.4
            return [];
        }

        $plusDefinition = new Definition(PlusHandlerV2ToV5::class);
        $plusDefinition->setDecoratedService(PlusPuiHandler::class, null, 0, ContainerInterface::IGNORE_ON_INVALID_REFERENCE);
        $plusDefinition->setArguments([
            new Reference(PlusHandlerV2ToV5::class . '.inner'),
            new Reference(V2PaymentResource::class)
        ]);

        $spbDefinition = new Definition(SpbHandlerV2ToV5::class);
        $spbDefinition->setDecoratedService(V2EcsSpbHandler::class, null, 0, ContainerInterface::IGNORE_ON_INVALID_REFERENCE);
        $spbDefinition->setArguments([
            new Reference(SpbHandlerV2ToV5::class . '.inner'),
            new Reference(OrderResource::class)
        ]);

        return [
            PlusHandlerV2ToV5::class => $plusDefinition,
            SpbHandlerV2ToV5::class => $spbDefinition
        ];
    }
}
