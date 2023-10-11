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

use DateTime;
use DateTimeZone;
use InvalidArgumentException;
use Pickware\MoneyBundle\Currency;
use Pickware\PickwareDhl\Adapter\ShipmentOrder\Options\AbstractShipmentOrderOption;
use Pickware\PickwareDhl\ApiClient\DhlApiClientException;
use Pickware\PickwareDhl\ApiClient\DhlBillingInformation;
use Pickware\PickwareDhl\ApiClient\DhlProduct;
use Pickware\ShippingBundle\Parcel\Parcel;
use Pickware\ShippingBundle\Parcel\ParcelCustomsInformation;
use Pickware\ShippingBundle\Shipment\Address;

class ShipmentOrder
{
    public const INCOTERM_CODES_EUROPE = [
        'DDP',
        'DXV',
        'DAP',
        'DDX',
        'CPT',
    ];

    public const INCOTERM_CODES_INTERNATIONAL = [
        'DDP',
        'DXV',
        'DAP',
        'DDX',
    ];

    public const EXPORT_TYPE_MAPPING = [
        ParcelCustomsInformation::SHIPMENT_TYPE_COMMERCIAL_SAMPLE => 'COMMERCIAL_SAMPLE',
        ParcelCustomsInformation::SHIPMENT_TYPE_DOCUMENTS => 'DOCUMENT',
        ParcelCustomsInformation::SHIPMENT_TYPE_GIFT => 'PRESENT',
        ParcelCustomsInformation::SHIPMENT_TYPE_OTHER => 'OTHER',
        ParcelCustomsInformation::SHIPMENT_TYPE_RETURNED_GOODS => 'RETURN_OF_GOODS',
        ParcelCustomsInformation::SHIPMENT_TYPE_SALE_OF_GOODS => 'COMMERCIAL_GOODS',
    ];

    /**
     * @var DhlBillingInformation
     */
    private $billingInformation;

    /**
     * @var Address
     */
    private $receiverAddress;

    /**
     * @var Address
     */
    private $senderAddress;

    /**
     * @var Parcel
     */
    private $parcel;

    /**
     * @var DhlProduct
     */
    private $product;

    /**
     * @var bool
     */
    private $exportDocumentCreationEnabled = false;

    /**
     * @var string
     */
    private $termsOfTrade = self::INCOTERM_CODES_INTERNATIONAL[0];

    /**
     * @var DateTime
     */
    private $shipmentDate;

    /**
     * @var AbstractShipmentOrderOption[]
     */
    private $shipmentOrderOptions = [];

    /**
     * @var string
     */
    private $sequenceNumber;

    public function __construct(DhlBillingInformation $billingInformation)
    {
        $this->billingInformation = $billingInformation;
        // DHL expects the shipment date based on the timezone of Germany.
        $this->shipmentDate = new DateTime('now', new DateTimeZone('Europe/Berlin'));
    }

    public function toArray(): array
    {
        $parcelWeight = $this->parcel->getTotalWeight();
        if ($parcelWeight === null) {
            throw DhlApiClientException::parcelHasItemsWithUndefinedWeight();
        }
        $shipmentDetails = [
            'shipmentDate' => $this->shipmentDate->format('Y-m-d'),
            'product' => $this->product->getCode(),
            'accountNumber' => $this->billingInformation->getBillingNumberForProduct($this->product),
            'ShipmentItem' => [
                'weightInKG' => $parcelWeight->convertTo('kg'),
            ],
            'Service' => [],
        ];

        if ($this->parcel->getCustomerReference() !== null) {
            $shipmentDetails['customerReference'] = $this->parcel->getCustomerReference();
        }

        if ($this->parcel->getDimensions() !== null) {
            // Add the parcel dimensions
            $shipmentDetails['ShipmentItem']['lengthInCM'] = ceil(
                $this->parcel->getDimensions()->getLength()->convertTo('cm'),
            );
            $shipmentDetails['ShipmentItem']['widthInCM'] = ceil(
                $this->parcel->getDimensions()->getWidth()->convertTo('cm'),
            );
            $shipmentDetails['ShipmentItem']['heightInCM'] = ceil(
                $this->parcel->getDimensions()->getHeight()->convertTo('cm'),
            );
        }

        self::validateAddress('sender', $this->senderAddress);
        self::validateAddress('receiver', $this->receiverAddress);

        $shipment = [
            'ShipmentDetails' => $shipmentDetails,
            'Shipper' => self::getAddressAsShipperAddressArray($this->senderAddress),
            'Receiver' => self::getAddressAsReceiverAddressArray($this->receiverAddress),
        ];
        if ($this->exportDocumentCreationEnabled) {
            $shipment['ExportDocument'] = $this->createExportDocumentArray();
        }

        $shipmentOrder = [
            'sequenceNumber' => $this->sequenceNumber,
            'Shipment' => $shipment,
        ];

        foreach ($this->shipmentOrderOptions as $shipmentOrderOption) {
            $shipmentOrderOption->applyToShipmentOrderArray($shipmentOrder);
        }

        return $shipmentOrder;
    }

    private static function getAddressAsShipperAddressArray(Address $address): array
    {
        return [
            'Name' => $address->getOptimizedNameArray(['name1', 'name2', 'name3']),
            'Address' => [
                'streetName' => $address->getStreet(),
                'streetNumber' => $address->getHouseNumber(),
                'zip' => $address->getZipCode(),
                'city' => $address->getCity(),
                'Origin' => [
                    'countryISOCode' => $address->getCountryIso(),
                    'state' => $address->getStateIso(),
                ],
            ],
            'Communication' => [
                'contactPerson' => sprintf('%s %s', $address->getFirstName(), $address->getLastName()),
                'phone' => $address->getPhone(),
                'email' => $address->getEmail(),
            ],
        ];
    }

    private static function getAddressAsReceiverAddressArray(Address $address): array
    {
        $nameArray = $address->getOptimizedNameArray(['name1', 'name2', 'name3']);

        $addressArray = [
            'name2' => $nameArray['name2'] ?? null,
            'name3' => $nameArray['name3'] ?? null,
            'streetName' => $address->getStreet(),
            'streetNumber' => $address->getHouseNumber(),
            'zip' => $address->getZipCode(),
            'city' => $address->getCity(),
            'Origin' => [
                'countryISOCode' => $address->getCountryIso(),
                'state' => $address->getStateIso(),
            ],
        ];

        return [
            'name1' => $nameArray['name1'] ?? null,
            'Communication' => [
                'contactPerson' => sprintf('%s %s', $address->getFirstName(), $address->getLastName()),
                'phone' => $address->getPhone(),
                'email' => $address->getEmail(),
            ],
            'Address' => $addressArray,
        ];
    }

    /**
     * @param string $addressOwner The owner of the address (i.e. 'sender', 'receiver')
     * @throws DhlApiClientException
     */
    private static function validateAddress(string $addressOwner, Address $address): void
    {
        if (count($address->getOptimizedNameArray()) === 0) {
            throw DhlApiClientException::missingAddressProperty($addressOwner, 'name, company or address addition');
        }
        if ($address->getCountryIso() === '') {
            throw DhlApiClientException::missingAddressProperty($addressOwner, 'country');
        }
    }

    public function setReceiverAddress(Address $receiverAddress): void
    {
        $this->receiverAddress = $receiverAddress;
    }

    public function setSenderAddress(Address $senderAddress): void
    {
        $this->senderAddress = $senderAddress;
    }

    public function getParcel(): Parcel
    {
        return $this->parcel;
    }

    public function setParcel(Parcel $parcel): void
    {
        $this->parcel = $parcel;
    }

    public function setProduct(DhlProduct $product): void
    {
        $this->product = $product;
    }

    /**
     * @return AbstractShipmentOrderOption[]
     */
    public function getShipmentOrderOptions(): array
    {
        return $this->shipmentOrderOptions;
    }

    /**
     * @param AbstractShipmentOrderOption[] $shipmentOrderOptions
     */
    public function setShipmentOrderOptions(array $shipmentOrderOptions): void
    {
        $this->shipmentOrderOptions = $shipmentOrderOptions;
    }

    public function enableExportDocumentCreation(string $termsOfTrade): void
    {
        $validIncoterms = array_unique(array_merge(self::INCOTERM_CODES_EUROPE, self::INCOTERM_CODES_INTERNATIONAL));
        if (!in_array($termsOfTrade, $validIncoterms, true)) {
            throw new InvalidArgumentException(sprintf(
                'Passed terms of trade code "%s" is not a valid incoterm code.',
                $termsOfTrade,
            ));
        }

        $this->exportDocumentCreationEnabled = true;
        $this->termsOfTrade = $termsOfTrade;
    }

    public function isExportDocumentCreationEnabled(): bool
    {
        return $this->exportDocumentCreationEnabled;
    }

    public function getTermsOfTrade(): string
    {
        return $this->termsOfTrade;
    }

    public function getShipmentDate(): DateTime
    {
        return $this->shipmentDate;
    }

    public function setShipmentDate(DateTime $shipmentDate): void
    {
        $this->shipmentDate = $shipmentDate;
    }

    private function createExportDocumentArray(): array
    {
        $parcelCustomsInformation = $this->parcel->getCustomsInformation();

        if (!$parcelCustomsInformation) {
            throw DhlApiClientException::missingCustomsInformationForParcel($this->parcel);
        }

        $euro = new Currency('EUR');
        foreach ($parcelCustomsInformation->getFees() as $feeType => $fee) {
            if (!$fee->getCurrency()->equals($euro)) {
                throw DhlApiClientException::feeGivenInUnsupportedCurrency($feeType, $fee->getCurrency());
            }
        }

        if (!$parcelCustomsInformation->getTypeOfShipment()) {
            throw DhlApiClientException::typeOfShipmentMissing();
        }

        // Create export document
        $exportDocument = [
            'exportType' => self::EXPORT_TYPE_MAPPING[$parcelCustomsInformation->getTypeOfShipment()],
            'exportTypeDescription' => $parcelCustomsInformation->getExplanationIfTypeOfShipmentIsOther(),
            'termsOfTrade' => $this->termsOfTrade,
            'placeOfCommital' => $parcelCustomsInformation->getOfficeOfOrigin(), // Sic! "Commital" is a typo by DHL!
            'additionalFee' => $parcelCustomsInformation->getTotalFees()->getValue(),
            'invoiceNumber' => implode(',', $parcelCustomsInformation->getInvoiceNumbers()),
            'permitNumber' => implode(',', $parcelCustomsInformation->getPermitNumbers()),
            'attestationNumber' => implode(',', $parcelCustomsInformation->getCertificateNumbers()),
            'sendersCustomsReference' => $this->senderAddress->getCustomsReference(),
            'addresseesCustomsReference' => $this->receiverAddress->getCustomsReference(),
            'ExportDocPosition' => [],
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

            $exportDocument['ExportDocPosition'][] = [
                'description' => $itemCustomsInformation->getDescription(),
                'countryCodeOrigin' => $itemCustomsInformation->getCountryIsoOfOrigin(),
                'customsTariffNumber' => $itemCustomsInformation->getTariffNumber(),
                'amount' => $item->getQuantity(),
                'customsValue' => round($customsValue->getValue(), 2),
                // When rounding the weights of individual items in a parcel, it's possible for the total weight of the
                // parcel to be less than the sum of each item's weight. For example, if one item weighs 4.5g and
                // another weighs 9.5g, the sum is 14g. However, if the weights are rounded before adding them together,
                // the sum would be 15g. DHL requires that the sum of the items' weights be less than or equal to the
                // total weight of the parcel, or else the label creation process will fail. To avoid this issue, we use
                // the floor() function to round down the weights of the individual items instead of rounding them.
                // Unfortunately, floor() does not accept a $precision parameter, so we need to convert the weights to
                // grams, round them down to the nearest gram, and then convert them back to kilograms.
                'netWeightInKG' => floor($item->getUnitWeight()->convertTo('g')) / 1000,
            ];
        }

        return $exportDocument;
    }

    public function getReceiverAddress(): Address
    {
        return $this->receiverAddress;
    }

    public function getSenderAddress(): Address
    {
        return $this->senderAddress;
    }

    public function getProduct(): DhlProduct
    {
        return $this->product;
    }

    public function getBillingInformation(): DhlBillingInformation
    {
        return $this->billingInformation;
    }

    public function getSequenceNumber(): string
    {
        return $this->sequenceNumber;
    }

    public function setSequenceNumber(string $sequenceNumber): void
    {
        $this->sequenceNumber = $sequenceNumber;
    }
}
