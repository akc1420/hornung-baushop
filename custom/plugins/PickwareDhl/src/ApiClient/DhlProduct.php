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

use InvalidArgumentException;
use JsonSerializable;

class DhlProduct implements JsonSerializable
{
    public const CODE_DHL_PAKET = 'V01PAK';
    public const CODE_DHL_PAKET_INTERNATIONAL = 'V53WPAK';
    public const CODE_DHL_EUROPAKET = 'V54EPAK';
    public const CODE_DHL_PAKET_CONNECT = 'V55PAK';
    public const CODE_DHL_WARENPOST = 'V62WP';
    public const CODE_DHL_WARENPOST_INTERNATIONAL = 'V66WPI';

    private const PRODUCT_CODE_NAME_MAPPING = [
        self::CODE_DHL_PAKET => 'DHL Paket',
        self::CODE_DHL_PAKET_INTERNATIONAL => 'DHL Paket International',
        self::CODE_DHL_EUROPAKET => 'DHL Europaket',
        self::CODE_DHL_PAKET_CONNECT => 'DHL Paket Connect',
        self::CODE_DHL_WARENPOST => 'DHL Warenpost',
        self::CODE_DHL_WARENPOST_INTERNATIONAL => 'DHL Warenpost International',
    ];
    private const PRODUCT_CODE_RETURN_NAME_MAPPING = [
        self::CODE_DHL_PAKET => 'DHL Retoure',
        self::CODE_DHL_PAKET_INTERNATIONAL => 'DHL Retoure International',
    ];

    private const PRODUCT_CODE_BOOKING_TEXT_MAPPING = [
        self::CODE_DHL_PAKET => 'DHL PAKET GKP',
        self::CODE_DHL_PAKET_INTERNATIONAL => 'DHL PAKET INTERN',
        self::CODE_DHL_EUROPAKET => 'DHL Europaket',
        self::CODE_DHL_PAKET_CONNECT => 'DHL Paket Connect',
        self::CODE_DHL_WARENPOST => 'WARENPOST',
        self::CODE_DHL_WARENPOST_INTERNATIONAL => 'WARENPOST INTERN',
    ];
    private const PRODUCT_CODE_RETURN_BOOKING_TEXT_MAPPING = [
        self::CODE_DHL_PAKET => 'DHL RETOURE ONLINE',
        self::CODE_DHL_PAKET_INTERNATIONAL => 'DHL RETOURE INT',
    ];

    /**
     * @var string
     */
    private $code;

    public static function getByCode(string $code): self
    {
        if (!self::isValidProductCode($code)) {
            throw new InvalidArgumentException(sprintf('DHL product with code %s does not exist', $code));
        }

        return new self($code);
    }

    public static function isValidProductCode(string $code): bool
    {
        return array_key_exists($code, self::PRODUCT_CODE_NAME_MAPPING);
    }

    /**
     * @return self[]
     */
    public static function getList(): array
    {
        return array_map(fn (string $code) => self::getByCode($code), array_keys(self::PRODUCT_CODE_NAME_MAPPING));
    }

    private function __construct(string $code)
    {
        $this->code = $code;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getProcedure(): string
    {
        return self::extractProcedureFromCode($this->code);
    }

    public function getReturnProcedure(): string
    {
        if ($this->code === self::CODE_DHL_PAKET || $this->code === self::CODE_DHL_WARENPOST) {
            // The "DHL Beilegretoure für DHL Paket" and "DHL Beilegretoure für DHL Warenpost" have a fixed procedure of "07"
            return '07';
        }

        // It is assumed that in all other cases the procedure code for a return always equals the procedure code
        // of the product. However, this is only a presumption, which we have based on our own contracts in the GKP.
        return $this->getProcedure();
    }

    private static function extractProcedureFromCode(string $code): string
    {
        return mb_substr($code, 1, 2);
    }

    public function getName(): string
    {
        return self::PRODUCT_CODE_NAME_MAPPING[$this->code];
    }

    public function getReturnName(): ?string
    {
        return self::PRODUCT_CODE_RETURN_NAME_MAPPING[$this->code] ?? null;
    }

    public function getBookingText(): string
    {
        return self::PRODUCT_CODE_BOOKING_TEXT_MAPPING[$this->code];
    }

    public function getReturnBookingText(): ?string
    {
        return self::PRODUCT_CODE_RETURN_BOOKING_TEXT_MAPPING[$this->code] ?? null;
    }

    public function jsonSerialize(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->getName(),
            'returnName' => $this->getReturnName(),
            'procedure' => $this->getProcedure(),
            'returnProcedure' => $this->getReturnProcedure(),
        ];
    }
}
