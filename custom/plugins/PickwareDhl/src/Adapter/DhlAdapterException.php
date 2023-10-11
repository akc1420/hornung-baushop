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

use Pickware\HttpUtils\JsonApi\JsonApiError;
use Pickware\MoneyBundle\CurrencyConverterException;
use Pickware\ShippingBundle\Carrier\CarrierAdapterException;

class DhlAdapterException extends CarrierAdapterException
{
    private const ERROR_CODE_NAMESPACE = 'PICKWARE_DHL__ADAPTER__';
    private const ERROR_CODE_CUSTOMS_VALUES_COULD_NOT_BE_CONVERTED_TO_EURO = self::ERROR_CODE_NAMESPACE . 'CUSTOMS_VALUES_COULD_NOT_BE_CONVERTED_TO_EURO';
    private const ERROR_CODE_NO_PRODUCT_CODE = self::ERROR_CODE_NAMESPACE . 'NO_PRODUCT_CODE';
    private const ERROR_CODE_INVALID_PRODUCT_CODE = self::ERROR_CODE_NAMESPACE . 'INVALID_PRODUCT_CODE';
    private const ERROR_CODE_SHIPMENT_BLUEPRINT_HAS_NO_PARCELS = self::ERROR_CODE_NAMESPACE . 'SHIPMENT_BLUEPRINT_HAS_NO_PARCELS';
    private const ERROR_CODE_SHIPMENT_CONFIG_IS_MISSING_TERMS_OF_TRADE = self::ERROR_CODE_NAMESPACE . 'SHIPMENT_CONFIG_IS_MISSING_TERMS_OF_TRADE';
    private const ERROR_CODE_SHIPMENT_CONFIG_IS_MISSING_DATE_OF_BIRTH_OR_IN_WRONG_FORMAT = self::ERROR_CODE_NAMESPACE . 'SHIPMENT_CONFIG_IS_MISSING_DATE_OF_BIRTH_OR_IN_WRONG_FORMAT';
    private const ERROR_CODE_SHIPMENT_NOT_FOUND = self::ERROR_CODE_NAMESPACE . 'SHIPMENT_NOT_FOUND';
    private const ERROR_CODE_ORDER_REQUIRES_EXPORT_DECLARATION = self::ERROR_CODE_NAMESPACE . 'ORDER_REQUIRES_EXPORT_DECLARATION';
    private const ERROR_CODE_CUSTOMS_INFORMATION_MISSING_TOTAL_VALUE = self::ERROR_CODE_NAMESPACE . 'CUSTOMS_INFORMATION_MISSING_TOTAL_VALUE';

    public static function customsValuesCouldNotBeConvertedToEuro(
        CurrencyConverterException $currencyConverterException
    ): self {
        return new self(new JsonApiError([
            'code' => self::ERROR_CODE_CUSTOMS_VALUES_COULD_NOT_BE_CONVERTED_TO_EURO,
            'title' => 'Customs values could not be converted to euro',
            'detail' => sprintf(
                'The DHL BCP API does support customs values in EUR only. At least one customs value of your shipment ' .
                'was not provided in EUR and could not be converted to EUR because of the following reason: %s',
                $currencyConverterException->getMessage(),
            ),
            'meta' => [
                'reason' => $currencyConverterException->getMessage(),
            ],
        ]), $currencyConverterException);
    }

    public static function customsValuesCouldNotBeConverted(
        CurrencyConverterException $currencyConverterException
    ): self {
        return new self(new JsonApiError([
            'code' => self::ERROR_CODE_CUSTOMS_VALUES_COULD_NOT_BE_CONVERTED_TO_EURO,
            'title' => 'Customs values could not be converted',
            'detail' => sprintf(
                'The DHL Parcel DE Returns API does support customs values in EUR, USD, CZK, GBP, CHF and SGD ' .
                'only. At least one customs value of your shipment was not provided in one of ' .
                'the supported currencies and could not be converted because of the following reason: %s',
                $currencyConverterException->getMessage(),
            ),
            'meta' => [
                'reason' => $currencyConverterException->getMessage(),
            ],
        ]), $currencyConverterException);
    }

    public static function invalidProductCode(string $productCode): self
    {
        if ($productCode === '') {
            return new self(new JsonApiError([
                'code' => self::ERROR_CODE_NO_PRODUCT_CODE,
                'title' => 'No DHL BCP product specified',
                'detail' => 'No DHL BCP product was specified.',
            ]));
        }

        return new self(new JsonApiError([
            'code' => self::ERROR_CODE_INVALID_PRODUCT_CODE,
            'title' => 'Invalid DHL BCP product specified',
            'detail' => sprintf(
                'The specified value "%s" is not a valid code for a DHL BCP product.',
                $productCode,
            ),
            'meta' => [
                'productCode' => $productCode,
            ],
        ]));
    }

    public static function shipmentBlueprintHasNoParcels(): self
    {
        return new self(new JsonApiError([
            'code' => self::ERROR_CODE_SHIPMENT_BLUEPRINT_HAS_NO_PARCELS,
            'title' => 'Shipment blueprint has no parcels',
            'detail' => 'The shipment has no parcels and therefore a label cannot be created.',
        ]));
    }

    public static function shipmentConfigIsMissingTermsOfTrade(): self
    {
        return new self(new JsonApiError([
            'code' => self::ERROR_CODE_SHIPMENT_CONFIG_IS_MISSING_TERMS_OF_TRADE,
            'title' => 'Shipment config is missing terms of trade',
            'detail' => 'It was requested to create export documents for the shipment but no incoterm was given in the ' .
                'configuration.',
        ]));
    }

    public static function shipmentConfigIsMissingDateOfBirthOrInWrongFormat(): self
    {
        return new self(new JsonApiError([
            'code' => self::ERROR_CODE_SHIPMENT_CONFIG_IS_MISSING_DATE_OF_BIRTH_OR_IN_WRONG_FORMAT,
            'title' => 'Shipment config is missing date of birth',
            'detail' => 'The date of birth is missing or in wrong format for service option Ident-Check.',
        ]));
    }

    public static function shipmentNotFound(string $shipmentId): self
    {
        return new self(new JsonApiError([
            'code' => self::ERROR_CODE_SHIPMENT_NOT_FOUND,
            'title' => 'Shipment not found',
            'detail' => sprintf('The shipment with ID %s was not found.', $shipmentId),
            'meta' => [
                'shipmentId' => $shipmentId,
            ],
        ]));
    }

    public static function orderRequiresExportDeclaration(float $maxCustomsValue): self
    {
        return new self(new JsonApiError([
            'code' => self::ERROR_CODE_ORDER_REQUIRES_EXPORT_DECLARATION,
            'title' => 'Order requires export declaration',
            'detail' => sprintf(
                'This order requires an export declaration as it exceeds a customs value of %s€. Please use ' .
                'the product DHL Paket International for this order.',
                $maxCustomsValue,
            ),
            'meta' => [
                'maxCustomsValue' => $maxCustomsValue,
            ],
        ]));
    }

    public static function customsInformationMissingTotalValue(): self
    {
        return new self(new JsonApiError([
            'code' => self::ERROR_CODE_CUSTOMS_INFORMATION_MISSING_TOTAL_VALUE,
            'title' => 'Parcel is missing the total value for customs',
            'detail' => 'At least one item in the parcel is missing a customs value and therefore the total parcel ' .
                'value cannot be determined.',
        ]));
    }
}
