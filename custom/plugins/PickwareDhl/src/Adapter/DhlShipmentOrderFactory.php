<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareDhl\Adapter;

use Pickware\DalBundle\EntityManager;
use Pickware\MoneyBundle\Currency;
use Pickware\MoneyBundle\CurrencyConverter;
use Pickware\MoneyBundle\CurrencyConverterException;
use Pickware\PickwareDhl\Adapter\ShipmentOrder\Options\DispatchNotificationOption;
use Pickware\PickwareDhl\Adapter\ShipmentOrder\ReturnShipmentOrder;
use Pickware\PickwareDhl\Adapter\ShipmentOrder\ShipmentOrder;
use Pickware\PickwareDhl\ApiClient\DhlProduct;
use Pickware\PickwareDhl\Config\DhlConfig;
use Pickware\ShippingBundle\Shipment\Model\ShipmentDefinition;
use Pickware\ShippingBundle\Shipment\Model\ShipmentEntity;
use Pickware\ShippingBundle\Shipment\ShipmentBlueprint;
use Shopware\Core\Framework\Context;

class DhlShipmentOrderFactory
{
    private const WARENPOST_INTERNATIONAL_MAX_CUSTOMS_VALUE_IN_EUR = 1000;
    private const SUPPORTED_CURRENCIES_FOR_CUSTOMS = [
        'EUR',
        'USD',
        'CZK',
        'GBP',
        'CHF',
        'SGD',
    ];

    private CurrencyConverter $currencyConverter;
    private EntityManager $entityManager;

    public function __construct(CurrencyConverter $currencyConverter, EntityManager $entityManager)
    {
        $this->currencyConverter = $currencyConverter;
        $this->entityManager = $entityManager;
    }

    /**
     * @return ShipmentOrder[]
     */
    public function createShipmentOrdersForShipment(string $shipmentId, DhlConfig $dhlConfig, Context $context): array
    {
        /** @var ShipmentEntity $shipment */
        $shipment = $this->entityManager->findByPrimaryKey(
            ShipmentDefinition::class,
            $shipmentId,
            $context,
        );
        if (!$shipment) {
            throw DhlAdapterException::shipmentNotFound($shipmentId);
        }
        $shipmentBlueprint = $shipment->getShipmentBlueprint();
        $receiverAddress = $shipmentBlueprint->getReceiverAddress();
        if (!$dhlConfig->isEmailTransferAllowed()) {
            $receiverAddress = $receiverAddress->copyWithoutEmail();
        }
        if (!$dhlConfig->isPhoneTransferAllowed()) {
            $receiverAddress = $receiverAddress->copyWithoutPhone();
        }

        $dhlShipmentConfig = new DhlShipmentConfig($shipmentBlueprint->getShipmentConfig());
        $product = $dhlShipmentConfig->getProduct();
        $shipmentOrderOptions = $dhlShipmentConfig->getShipmentOrderOptions($dhlConfig);
        if ($dhlConfig->isDispatchNotificationEnabled() && $receiverAddress->getEmail()) {
            $shipmentOrderOptions[] = new DispatchNotificationOption($receiverAddress->getEmail());
        }

        if (count($shipmentBlueprint->getParcels()) === 0) {
            throw DhlAdapterException::shipmentBlueprintHasNoParcels();
        }

        $shipmentOrders = [];
        foreach ($shipmentBlueprint->getParcels() as $parcelIndex => $parcel) {
            $shipmentOrder = new ShipmentOrder($dhlConfig->getBillingInformation());
            $shipmentOrder->setReceiverAddress($receiverAddress);
            $shipmentOrder->setSenderAddress($shipmentBlueprint->getSenderAddress());
            $shipmentOrder->setProduct($product);
            $shipmentOrder->setParcel($parcel);
            $shipmentOrder->setShipmentOrderOptions($shipmentOrderOptions);
            $shipmentOrder->setSequenceNumber(
                (new ParcelReference($shipmentId, $parcelIndex))->toString(),
            );

            $termsOfTrade = $dhlShipmentConfig->getTermsOfTrade();
            if ($termsOfTrade !== null) {
                $shipmentOrder->enableExportDocumentCreation($termsOfTrade);
                try {
                    $parcel->convertAllMoneyValuesToSameCurrency(
                        $this->currencyConverter,
                        new Currency('EUR'),
                        $context,
                    );
                } catch (CurrencyConverterException $e) {
                    throw DhlAdapterException::customsValuesCouldNotBeConvertedToEuro($e);
                }

                if ($parcel->getCustomsInformation()) {
                    $totalParcelValue = $parcel->getCustomsInformation()->getTotalValue();
                    if ($totalParcelValue
                        && $totalParcelValue->getValue() > self::WARENPOST_INTERNATIONAL_MAX_CUSTOMS_VALUE_IN_EUR
                        && $product->getCode() === DhlProduct::CODE_DHL_WARENPOST_INTERNATIONAL
                    ) {
                        throw DhlAdapterException::orderRequiresExportDeclaration(self::WARENPOST_INTERNATIONAL_MAX_CUSTOMS_VALUE_IN_EUR);
                    }
                }
            }
            $shipmentOrders[] = $shipmentOrder;
        }

        return $shipmentOrders;
    }

    /**
     * @return ReturnShipmentOrder[]
     */
    public function createReturnShipmentOrdersForShipment(
        ShipmentBlueprint $shipmentBlueprint,
        DhlConfig $dhlConfig
    ): array {
        $shipmentConfig = new DhlShipmentConfig($shipmentBlueprint->getShipmentConfig());
        $senderAddress = $shipmentBlueprint->getSenderAddress();
        if ($senderAddress !== null) {
            if (!$dhlConfig->isEmailTransferAllowed()) {
                $senderAddress = $senderAddress->copyWithoutEmail();
            }
            if (!$dhlConfig->isPhoneTransferAllowed()) {
                $senderAddress = $senderAddress->copyWithoutPhone();
            }
        }

        if (count($shipmentBlueprint->getParcels()) === 0) {
            throw DhlAdapterException::shipmentBlueprintHasNoParcels();
        }

        $shipmentOrders = [];
        foreach ($shipmentBlueprint->getParcels() as $parcel) {
            $shipmentOrder = new ReturnShipmentOrder($senderAddress, $parcel);
            if ($shipmentConfig->getExportDocumentsActive()) {
                if (!$parcel->getCustomsInformation() || !$parcel->getCustomsInformation()->getTotalValue()) {
                    throw DhlAdapterException::customsInformationMissingTotalValue();
                }

                if (!in_array(
                    $parcel->getCustomsInformation()->getTotalValue()->getCurrency()->getIsoCode(),
                    self::SUPPORTED_CURRENCIES_FOR_CUSTOMS,
                )) {
                    try {
                        $parcel->convertAllMoneyValuesToSameCurrency($this->currencyConverter, new Currency('EUR'));
                    } catch (CurrencyConverterException $e) {
                        throw DhlAdapterException::customsValuesCouldNotBeConverted($e);
                    }
                }

                $shipmentOrder->enableExportDocumentCreation();
            }

            $shipmentOrders[] = $shipmentOrder;
        }

        return $shipmentOrders;
    }
}
