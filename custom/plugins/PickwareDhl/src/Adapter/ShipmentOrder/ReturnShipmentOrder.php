<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareDhl\Adapter\ShipmentOrder;

use JsonSerializable;
use LogicException;
use Pickware\MoneyBundle\Currency;
use Pickware\PickwareDhl\ApiClient\DhlApiClientException;
use Pickware\ShippingBundle\Parcel\Parcel;
use Pickware\ShippingBundle\Shipment\Address;
use Pickware\ShippingBundle\Shipment\Country;

class ReturnShipmentOrder implements JsonSerializable
{
    private Address $shipperAddress;
    private Parcel $parcel;
    private ?string $receiverId = null;
    private bool $exportDocumentEnabled = false;

    public function __construct(Address $shipperAddress, Parcel $parcel)
    {
        $this->shipperAddress = $shipperAddress;
        $this->parcel = $parcel;
    }

    public function jsonSerialize(): array
    {
        if (!$this->receiverId) {
            throw new LogicException('No recieverId set for ReturnShipmentOrder');
        }

        $shipmentOrder = [
            'customerReference' => $this->parcel->getCustomerReference(),
            'shipper' => self::getAddressAsShipperAddressArray($this->shipperAddress),
            'receiverId' => $this->receiverId,
        ];

        if ($this->parcel->getTotalWeight() !== null) {
            $shipmentOrder['itemWeight'] = [
                'uom' => 'kg',
                'value' => $this->parcel->getTotalWeight()->convertTo('kg'),
            ];
        }

        if ($this->exportDocumentEnabled) {
            $shipmentOrder['customsDetails'] = $this->createExportDocumentArray();
        }

        return $shipmentOrder;
    }

    public function getParcel(): Parcel
    {
        return $this->parcel;
    }

    public function getShipperAddress(): Address
    {
        return $this->shipperAddress;
    }

    public function setReceiverId(?string $receiverId): void
    {
        $this->receiverId = $receiverId;
    }

    public function enableExportDocumentCreation(): void
    {
        $this->exportDocumentEnabled = true;
    }

    private static function getAddressAsShipperAddressArray(Address $address): array
    {
        $names = $address->getOptimizedNameArray(['name1', 'name2', 'name3']);

        $country = new Country($address->getCountryIso());

        return array_merge($names, [
            'addressStreet' => $address->getStreet(),
            'addressHouse' => $address->getHouseNumber(),
            'postalCode' => $address->getZipCode(),
            'city' => $address->getCity(),
            'country' => $country->getIso3Code(),
            'phone' => $address->getPhone(),
            'email' => $address->getEmail(),
            'state' => $address->getStateIso(),
        ]);
    }

    private function createExportDocumentArray(): array
    {
        $euro = new Currency('EUR');

        // Create export document
        $exportDocument = [
            'items' => [],
        ];

        foreach ($this->parcel->getItems() as $item) {
            $itemCustomsInformation = $item->getCustomsInformation();
            if ($itemCustomsInformation === null) {
                throw DhlApiClientException::missingCustomsInformationForParcelItem($item);
            }

            $customsValue = $itemCustomsInformation->getCustomsValue();
            if ($customsValue === null) {
                throw DhlApiClientException::missingCustomsValueForParcelItem($item);
            }

            if (!$customsValue->getCurrency()->equals($euro)) {
                throw DhlApiClientException::customsValueGivenInUnsupportedCurrency(
                    $item,
                    $customsValue->getCurrency(),
                );
            }

            if ($item->getUnitWeight() === null) {
                throw DhlApiClientException::missingWeightForParcelItem($item);
            }

            if ($itemCustomsInformation->getCountryIsoOfOrigin() === null) {
                throw DhlApiClientException::missingCountryOfOriginForParcelItem($item);
            }

            $country = new Country($itemCustomsInformation->getCountryIsoOfOrigin());

            $exportDocument['items'][] = [
                'itemDescription' => $itemCustomsInformation->getDescription(),
                'packagedQuantity' => $item->getQuantity(),
                'itemWeight' => [
                    'uom' => 'g',
                    'value' => $item->getUnitWeight()->convertTo('g'),
                ],
                'itemValue' => [
                    'currency' => $customsValue->getCurrency()->getIsoCode(),
                    'value' => round($customsValue->getValue(), 2),
                ],
                'countryOfOrigin' => $country->getIso3Code(),
                'hsCode' => $itemCustomsInformation->getTariffNumber(),
            ];
        }

        return $exportDocument;
    }
}
