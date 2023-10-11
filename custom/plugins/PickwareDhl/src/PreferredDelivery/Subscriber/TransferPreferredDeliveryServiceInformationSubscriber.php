<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareDhl\PreferredDelivery\Subscriber;

use DateTime;
use Pickware\DalBundle\EntityManager;
use Pickware\PickwareDhl\PickwareDhl;
use Pickware\PickwareDhl\SalesChannelContext\Model\SalesChannelApiContextDefinition;
use Pickware\PickwareDhl\SalesChannelContext\Model\SalesChannelApiContextEntity;
use Pickware\ShippingBundle\Config\Model\ShippingMethodConfigDefinition;
use Pickware\ShippingBundle\Config\Model\ShippingMethodConfigEntity;
use Pickware\ShippingBundle\Shipment\ShipmentBlueprintCreatedEvent;
use Shopware\Core\Checkout\Cart\Order\CartConvertedEvent;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TransferPreferredDeliveryServiceInformationSubscriber implements EventSubscriberInterface
{
    public const CUSTOM_FIELD_PREFIX = 'pickware_dhl_';
    public const PREFERRED_DAY = 'preferred_day';
    public const PREFERRED_LOCATION = 'preferred_location';
    public const PREFERRED_NEIGHBOUR = 'preferred_neighbour';
    public const NO_NEIGHBOUR_DELIVERY = 'no_neighbour_delivery';

    public const PREFERRED_DELIVERY_SERVICES__STOREFRONT_CONFIG_MAPPING = [
        self::PREFERRED_DAY => 'showPreferredDay',
        self::PREFERRED_LOCATION => 'showPreferredLocation',
        self::PREFERRED_NEIGHBOUR => 'showPreferredNeighbour',
        self::NO_NEIGHBOUR_DELIVERY => 'showNoNeighbourDelivery',
    ];

    public const PREFERRED_DELIVERY_SERVICES__SHIPMENT_CONFIG_MAPPING = [
        self::PREFERRED_DAY => 'preferredDay',
        self::PREFERRED_LOCATION => 'preferredLocation',
        self::PREFERRED_NEIGHBOUR => 'preferredNeighbour',
        self::NO_NEIGHBOUR_DELIVERY => 'noNeighbourDelivery',
    ];

    private EntityManager $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CartConvertedEvent::class => 'onCartConverted',
            ShipmentBlueprintCreatedEvent::EVENT_NAME => 'onShipmentBlueprintCreated',
        ];
    }

    public function onCartConverted(CartConvertedEvent $event): void
    {
        /** @var SalesChannelApiContextEntity $pickwareSalesChannelContext */
        $pickwareSalesChannelContext = $this->entityManager->findOneBy(
            SalesChannelApiContextDefinition::class,
            (new Criteria())->addFilter(new EqualsFilter('salesChannelContextToken', $event->getSalesChannelContext()->getToken())),
            $event->getContext(),
        );

        if (!$pickwareSalesChannelContext) {
            return;
        }

        $shippingMethodConfigCriteria = new Criteria();
        $shippingMethodConfigCriteria->addFilter(new EqualsFilter(
            'shippingMethodId',
            $event->getSalesChannelContext()->getShippingMethod()->getId(),
        ));

        /** @var ShippingMethodConfigEntity $shippingMethodConfiguration */
        $shippingMethodConfiguration = $this->entityManager->findOneBy(
            ShippingMethodConfigDefinition::class,
            $shippingMethodConfigCriteria,
            $event->getContext(),
        );

        if ($shippingMethodConfiguration === null || $shippingMethodConfiguration->getCarrierTechnicalName() !== PickwareDhl::CARRIER_TECHNICAL_NAME_DHL) {
            return;
        }

        $preferredDeliverySettings = $pickwareSalesChannelContext->getValue(['dhl_preferred_delivery']) ?? [];
        // This filters out all settings configured by the user for which either no mapping to a shipment config exists
        // or the shipment config is configured such that the setting should not be enabled.
        $filteredPreferredDeliverySettings = array_filter($preferredDeliverySettings, function ($key) use ($shippingMethodConfiguration) {
            if (!array_key_exists($key, self::PREFERRED_DELIVERY_SERVICES__STOREFRONT_CONFIG_MAPPING)) {
                return false;
            }

            return $shippingMethodConfiguration->getStorefrontConfig()[self::PREFERRED_DELIVERY_SERVICES__STOREFRONT_CONFIG_MAPPING[$key]] ?? null;
        }, ARRAY_FILTER_USE_KEY);

        if (array_key_exists(self::NO_NEIGHBOUR_DELIVERY, $filteredPreferredDeliverySettings)) {
            $filteredPreferredDeliverySettings[self::NO_NEIGHBOUR_DELIVERY] = (bool) $filteredPreferredDeliverySettings[self::NO_NEIGHBOUR_DELIVERY];
        }

        $convertedCart = $event->getConvertedCart();
        $convertedCart['customFields'] = $convertedCart['customFields'] ?? [];
        foreach ($filteredPreferredDeliverySettings as $settingKey => $settingValue) {
            $convertedCart['customFields'][self::CUSTOM_FIELD_PREFIX . $settingKey] = $settingValue;
        }
        $event->setConvertedCart($convertedCart);

        $this->removePreferredDeliveryServiceInformationFromContext($pickwareSalesChannelContext, $event->getContext());
    }

    public function onShipmentBlueprintCreated(ShipmentBlueprintCreatedEvent $event): void
    {
        /** @var OrderEntity $order */
        $order = $this->entityManager->getByPrimaryKey(
            OrderDefinition::class,
            $event->getOrderId(),
            $event->getContext(),
        );

        $preferredServicesInformation = [];
        foreach ($order->getCustomFields() ?? [] as $key => $value) {
            if (!str_starts_with($key, self::CUSTOM_FIELD_PREFIX)) {
                continue;
            }

            $keyWithoutPrefix = str_replace(self::CUSTOM_FIELD_PREFIX, '', $key);
            if (in_array($keyWithoutPrefix, array_keys(self::PREFERRED_DELIVERY_SERVICES__SHIPMENT_CONFIG_MAPPING))) {
                $preferredServicesInformation[$keyWithoutPrefix] = $value;
            }
        }

        // The custom field stores this as a datetime value (with hours, minutes and seconds) to ease configuring it
        // in the custom fields of the order. The shipment config itself should only process dates in Y-m-d format.
        if (isset($preferredServicesInformation[self::PREFERRED_DAY])) {
            $preferredServicesInformation[self::PREFERRED_DAY] = (new DateTime(
                $preferredServicesInformation[self::PREFERRED_DAY],
            ))->format('Y-m-d');
        }

        $shipmentBlueprint = $event->getShipmentBlueprint();
        $shipmentConfig = $shipmentBlueprint->getShipmentConfig();

        foreach ($preferredServicesInformation as $key => $value) {
            $shipmentConfig[self::PREFERRED_DELIVERY_SERVICES__SHIPMENT_CONFIG_MAPPING[$key]] = $value;
        }

        $shipmentBlueprint->setShipmentConfig($shipmentConfig);
    }

    private function removePreferredDeliveryServiceInformationFromContext(
        SalesChannelApiContextEntity $pickwareSalesChannelContextEntity,
        Context                      $context
    ): void {
        $contextPayload = $pickwareSalesChannelContextEntity->getPayload();
        unset($contextPayload['dhl_preferred_delivery']);

        $this->entityManager->update(
            SalesChannelApiContextDefinition::class,
            [[
                'id' => $pickwareSalesChannelContextEntity->getId(),
                'salesChannelContextToken' => $pickwareSalesChannelContextEntity->getSalesChannelContextToken(),
                'payload' => $contextPayload,
            ],
            ],
            $context,
        );
    }
}
