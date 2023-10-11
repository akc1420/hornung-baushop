<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareDhl\ApiClient;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Pickware\HttpUtils\JsonApi\JsonApiError;
use Pickware\MoneyBundle\Currency;
use Pickware\PickwareDhl\Adapter\DhlAdapterException;
use Pickware\ShippingBundle\Parcel\Parcel;
use Pickware\ShippingBundle\Parcel\ParcelItem;
use SoapFault;

class DhlApiClientException extends DhlAdapterException
{
    private const ERROR_CODE_NAMESPACE = 'PICKWARE_DHL__API_CLIENT__';
    private const ERROR_CODE_PARCEL_HAS_ITEMS_WITH_UNDEFINED_WEIGHT = self::ERROR_CODE_NAMESPACE . 'PARCEL_HAS_ITEMS_WITH_UNDEFINED_WEIGHT';
    private const ERROR_CODE_NO_PARTICIPATION_CONFIGURED_FOR_PRODUCT = self::ERROR_CODE_NAMESPACE . 'NO_PARTICIPATION_CONFIGURED_FOR_PRODUCT';
    private const ERROR_CODE_NO_RETURN_PARTICIPATION_CONFIGURED_FOR_PRODUCT = self::ERROR_CODE_NAMESPACE . 'NO_RETURN_PARTICIPATION_CONFIGURED_FOR_PRODUCT';
    private const ERROR_CODE_MISSING_ADDRESS_PROPERTY = self::ERROR_CODE_NAMESPACE . 'MISSING_ADDRESS_PROPERTY';
    private const ERROR_CODE_MISSING_CUSTOMS_INFORMATION_FOR_PARCEL = self::ERROR_CODE_NAMESPACE . 'MISSING_CUSTOMS_INFORMATION_FOR_PARCEL';
    private const ERROR_CODE_MISSING_CUSTOMS_INFORMATION_FOR_PARCEL_ITEM = self::ERROR_CODE_NAMESPACE . 'MISSING_CUSTOMS_INFORMATION_FOR_PARCEL_ITEM';
    private const ERROR_CODE_MISSING_CUSTOMS_VALUE_FOR_PARCEL_ITEM = self::ERROR_CODE_NAMESPACE . 'MISSING_CUSTOMS_VALUE_FOR_PARCEL_ITEM';
    private const ERROR_CODE_MISSING_COUNTRY_OF_ORIGIN_FOR_PARCEL_ITEM = self::ERROR_CODE_NAMESPACE . 'MISSING_COUNTRY_OF_ORIGIN_FOR_PARCEL_ITEM';
    private const ERROR_CODE_MISSING_WEIGHT_FOR_PARCEL_ITEM = self::ERROR_CODE_NAMESPACE . 'MISSING_WEIGHT_FOR_PARCEL_ITEM';
    private const ERROR_CODE_FEE_GIVEN_IN_UNSUPPORTED_CURRENCY = self::ERROR_CODE_NAMESPACE . 'FEE_GIVEN_IN_UNSUPPORTED_CURRENCY';
    private const ERROR_CODE_CUSTOMS_VALUE_GIVEN_IN_UNSUPPORTED_CURRENCY = self::ERROR_CODE_NAMESPACE . 'CUSTOMS_VALUE_GIVEN_IN_UNSUPPORTED_CURRENCY';
    private const ERROR_CODE_TYPE_OF_SHIPMENT_MISSING = self::ERROR_CODE_NAMESPACE . 'TYPE_OF_SHIPMENT_MISSING';
    private const ERROR_CODE_DHL_BCP_API_COMMUNICATION_EXCEPTION = self::ERROR_CODE_NAMESPACE . 'DHL_BCP_API_COMMUNICATION_EXCEPTION';
    private const ERROR_CODE_DHL_BCP_API_RESPONDED_WITH_ERROR = self::ERROR_CODE_NAMESPACE . 'DHL_BCP_API_RESPONDED_WITH_ERROR';
    private const ERROR_CODE_DHL_PARCEL_DE_RETURNS_API_RESPONDED_WITH_ERROR = self::ERROR_CODE_NAMESPACE . 'DHL_PARCEL_DE_RETURNS_API_RESPONDED_WITH_ERROR';

    public static function parcelHasItemsWithUndefinedWeight(): self
    {
        return new self(new JsonApiError([
            'code' => self::ERROR_CODE_PARCEL_HAS_ITEMS_WITH_UNDEFINED_WEIGHT,
            'title' => 'Parcel has items with undefined weight',
            'detail' => 'The parcel has at least one item with an undefined weight. Therefore the total weight ' .
                'of the shipment cannot be determined.',
        ]));
    }

    public static function noParticipationConfiguredForProduct(DhlProduct $product): self
    {
        return new self(new JsonApiError([
            'code' => self::ERROR_CODE_NO_PARTICIPATION_CONFIGURED_FOR_PRODUCT,
            'title' => 'No participation configured for product',
            'detail' => sprintf(
                'No participation configured for product %s.',
                $product->getName(),
            ),
            'meta' => [
                'productName' => $product->getName(),
            ],
        ]));
    }

    public static function noReturnParticipationConfiguredForProduct(DhlProduct $product): self
    {
        return new self(new JsonApiError([
            'code' => self::ERROR_CODE_NO_RETURN_PARTICIPATION_CONFIGURED_FOR_PRODUCT,
            'title' => 'No return participation configured for product',
            'detail' => sprintf(
                'No return participation configured for product %s.',
                $product->getName(),
            ),
            'meta' => [
                'productName' => $product->getName(),
            ],
        ]));
    }

    /**
     * @param string $addressOwner The owner of the address (i.e. 'receiver' or 'sender')
     */
    public static function missingAddressProperty(string $addressOwner, string $addressPropertyName): self
    {
        return new self(new JsonApiError([
            'code' => self::ERROR_CODE_MISSING_ADDRESS_PROPERTY,
            'title' => 'Missing address property',
            'detail' => sprintf(
                'The %s address is missing the following property: %s.',
                $addressOwner,
                ucfirst($addressPropertyName),
            ),
            'meta' => [
                'addressOwner' => $addressOwner,
                'addressPropertyName' => $addressPropertyName,
            ],
        ]));
    }

    public static function missingCustomsInformationForParcel(Parcel $parcel): self
    {
        return new self(new JsonApiError([
            'code' => self::ERROR_CODE_MISSING_CUSTOMS_INFORMATION_FOR_PARCEL,
            'title' => 'Missing customs information for parcel',
            'detail' => sprintf(
                'No customs information configured for %s.',
                $parcel->getDescription(),
            ),
            'meta' => [
                'parcelDescription' => $parcel->getDescription(),
            ],
        ]));
    }

    public static function missingCustomsInformationForParcelItem(ParcelItem $item): self
    {
        return new self(new JsonApiError([
            'code' => self::ERROR_CODE_MISSING_CUSTOMS_INFORMATION_FOR_PARCEL_ITEM,
            'title' => 'Missing customs information for parcel item',
            'detail' => sprintf(
                'No customs information configured for item %s.',
                $item->getName(),
            ),
            'meta' => [
                'parcelItemName' => $item->getName(),
            ],
        ]));
    }

    public static function missingCustomsValueForParcelItem(ParcelItem $item): self
    {
        return new self(new JsonApiError([
            'code' => self::ERROR_CODE_MISSING_CUSTOMS_VALUE_FOR_PARCEL_ITEM,
            'title' => 'Missing customs information for parcel item',
            'detail' => sprintf(
                'No customs value configured for item %s.',
                $item->getName(),
            ),
            'meta' => [
                'parcelItemName' => $item->getName(),
            ],
        ]));
    }

    public static function missingCountryOfOriginForParcelItem(ParcelItem $item): self
    {
        return new self(new JsonApiError([
            'code' => self::ERROR_CODE_MISSING_COUNTRY_OF_ORIGIN_FOR_PARCEL_ITEM,
            'title' => 'Missing country of origin for parcel item',
            'detail' => sprintf(
                'No country of origin configured for item %s.',
                $item->getName(),
            ),
            'meta' => [
                'parcelItemName' => $item->getName(),
            ],
        ]));
    }

    public static function missingWeightForParcelItem(ParcelItem $item): self
    {
        return new self(new JsonApiError([
            'code' => self::ERROR_CODE_MISSING_WEIGHT_FOR_PARCEL_ITEM,
            'title' => 'Missing weight for parcel item',
            'detail' => sprintf(
                'No weight configured for item %s.',
                $item->getName(),
            ),
            'meta' => [
                'parcelItemName' => $item->getName(),
            ],
        ]));
    }

    public static function feeGivenInUnsupportedCurrency(string $feeType, Currency $currency): self
    {
        return new self(new JsonApiError([
            'code' => self::ERROR_CODE_FEE_GIVEN_IN_UNSUPPORTED_CURRENCY,
            'title' => 'Fee given in unsupported currency',
            'detail' => sprintf(
                'The parcel has a fee for "%s" that is given in an unsupported currency "%s". ' .
                'The DHL BCP currently supports EUR only.',
                $feeType,
                $currency->getIsoCode(),
            ),
            'meta' => [
                'feeType' => $feeType,
                'currencyIsoCode' => $currency->getIsoCode(),
            ],
        ]));
    }

    public static function customsValueGivenInUnsupportedCurrency(ParcelItem $item, Currency $currency): self
    {
        return new self(new JsonApiError([
            'code' => self::ERROR_CODE_CUSTOMS_VALUE_GIVEN_IN_UNSUPPORTED_CURRENCY,
            'title' => 'Fee given in unsupported currency',
            'detail' => sprintf(
                'The the customs value for parcel item "%s" is given in an unsupported currency "%s". ' .
                'The DHL BCP currently supports EUR only.',
                $item->getName(),
                $currency->getIsoCode(),
            ),
            'meta' => [
                'parcelItemName' => $item->getName(),
                'currencyIsoCode' => $currency->getIsoCode(),
            ],
        ]));
    }

    public static function typeOfShipmentMissing(): self
    {
        return new self(new JsonApiError([
            'code' => self::ERROR_CODE_TYPE_OF_SHIPMENT_MISSING,
            'title' => 'Type of shipment missing',
            'detail' => 'The type of the shipment is missing in the customs information for the shipment.',
        ]));
    }

    public static function dhlBcpApiCommunicationException(SoapFault $soapFault): self
    {
        return new self(new JsonApiError([
            'code' => self::ERROR_CODE_DHL_BCP_API_COMMUNICATION_EXCEPTION,
            'title' => 'DHL BCP API communication exception',
            'detail' => sprintf(
                'The communication with the DHL BCP API is not possible. Error: %s',
                $soapFault->getMessage(),
            ),
            'meta' => [
                'error' => $soapFault->getMessage(),
            ],
        ]));
    }

    public static function dhlBcpApiRespondedWithError(string $statusText): self
    {
        return new self(new JsonApiError([
            'code' => self::ERROR_CODE_DHL_BCP_API_RESPONDED_WITH_ERROR,
            'title' => 'DHL BCP API responded with an error',
            'detail' => sprintf('The DHL BCP API responded with an error: %s', $statusText),
            'meta' => [
                'error' => $statusText,
            ],
        ]));
    }

    public static function fromClientException(ClientException $e): self
    {
        if ($e->getResponse()->getHeader('content-type') === ['application/json']) {
            $errorMessage = self::getErrorMessageFromJson((string)$e->getResponse()->getBody());
        } else {
            $errorMessage = '';
        }

        return new self(
            new JsonApiError([
                'code' => self::ERROR_CODE_DHL_PARCEL_DE_RETURNS_API_RESPONDED_WITH_ERROR,
                'title' => 'DHL Parcel DE Returns API responded with an error',
                'detail' => sprintf('The DHL Parcel DE Returns API responded with an error: %s', $errorMessage),
                'meta' => [
                    'error' => $errorMessage,
                ],
            ]),
            $e,
        );
    }

    public static function fromServerException(ServerException $e): self
    {
        if ($e->getResponse()->getHeader('content-type') === ['application/json']) {
            $errorMessage = self::getErrorMessageFromJson((string)$e->getResponse()->getBody());
        } else {
            $errorMessage = '';
        }

        return new self(
            new JsonApiError([
                'code' => self::ERROR_CODE_DHL_PARCEL_DE_RETURNS_API_RESPONDED_WITH_ERROR,
                'title' => 'DHL Parcel DE Returns API responded with an error',
                'detail' => sprintf(
                    'The DHL Parcel DE Returns API request failed due to an unexpected DHL Server error: %s',
                    $errorMessage,
                ),
                'meta' => [
                    'error' => $errorMessage,
                ],
            ]),
            $e,
        );
    }

    private static function getErrorMessageFromJson(string $jsonString): string
    {
        $json = json_decode($jsonString, true);

        return $json['detail'];
    }
}
