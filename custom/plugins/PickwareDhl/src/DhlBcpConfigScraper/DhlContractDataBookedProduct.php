<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareDhl\DhlBcpConfigScraper;

use JsonSerializable;
use Pickware\PickwareDhl\ApiClient\DhlProduct;

class DhlContractDataBookedProduct implements JsonSerializable
{
    /**
     * @var DhlProduct
     */
    private $product;

    /**
     * @var string[]
     */
    private $billingNumbers;

    /**
     * @var string[]
     */
    private $returnBillingNumbers;

    public function __construct(DhlProduct $product, array $billingNumbers, array $returnBillingNumbers)
    {
        $this->product = $product;
        $this->billingNumbers = $billingNumbers;
        $this->returnBillingNumbers = $returnBillingNumbers;
    }

    public function getProduct(): DhlProduct
    {
        return $this->product;
    }

    public function getBillingNumbers(): array
    {
        return $this->billingNumbers;
    }

    public function getReturnBillingNumbers(): array
    {
        return $this->returnBillingNumbers;
    }

    /**
     * @return string[]
     */
    public function getParticipations(): array
    {
        return array_map(
            fn ($billingNumber) => mb_substr($billingNumber, 12, 2, 'UTF-8'),
            $this->billingNumbers,
        );
    }

    /**
     * @return string[]
     */
    public function getReturnParticipations(): array
    {
        return array_map(
            fn (string $returnBillingNumber) => mb_substr($returnBillingNumber, 12, 2, 'UTF-8'),
            $this->returnBillingNumbers,
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'product' => $this->product,
            'participations' => $this->getParticipations(),
            'returnParticipations' => $this->getReturnParticipations(),
            'billingNumbers' => $this->getBillingNumbers(),
            'returnBillingNumbers' => $this->getReturnBillingNumbers(),
        ];
    }

    /**
     * @param string[] $productBillingNumbersMapping Mapping string:billingNumber => string:bcpProductName
     * @return self[]
     */
    public static function createFromBcpProductNameBillingNumbersMapping(array $productBillingNumbersMapping): array
    {
        $allProducts = DhlProduct::getList();
        $bookedProducts = array_map(fn (DhlProduct $product) => new self($product, [], []), $allProducts);

        foreach ($productBillingNumbersMapping as $billingNumbers => $bcpProductName) {
            $billingNumbers = strval($billingNumbers);
            foreach ($bookedProducts as $bookedProduct) {
                // Check if the (return) BookingTexts of any bookedProduct contains the productName. If no productName
                // match is found we try the match the procedure number with the digits 11-12 of the billingNumber
                if (str_contains($bcpProductName ?? '', $bookedProduct->getProduct()->getBookingText())) {
                    $bookedProduct->billingNumbers[] = $billingNumbers;
                } elseif ($bookedProduct->getProduct()->getReturnBookingText() !== null &&
                    str_contains($bcpProductName ?? '', $bookedProduct->getProduct()->getReturnBookingText())) {
                    $bookedProduct->returnBillingNumbers[] = $billingNumbers;
                } elseif ($bookedProduct->getProduct()->getProcedure() === mb_substr($billingNumbers, 10, 2, 'UTF-8')) {
                    $bookedProduct->billingNumbers[] = $billingNumbers;
                }
            }
        }

        return array_values(array_filter($bookedProducts, fn (self $bookedProduct) => count($bookedProduct->billingNumbers) !== 0 || count($bookedProduct->returnBillingNumbers) !== 0));
    }
}
