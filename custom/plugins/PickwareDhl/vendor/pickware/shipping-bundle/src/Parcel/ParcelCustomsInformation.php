<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\ShippingBundle\Parcel;

use InvalidArgumentException;
use JsonSerializable;
use Pickware\MoneyBundle\MoneyValue;

class ParcelCustomsInformation implements JsonSerializable
{
    public const SHIPMENT_TYPE_COMMERCIAL_SAMPLE = 'commercial-sample';
    public const SHIPMENT_TYPE_DOCUMENTS = 'documents';
    public const SHIPMENT_TYPE_GIFT = 'gift';
    public const SHIPMENT_TYPE_OTHER = 'other';
    public const SHIPMENT_TYPE_RETURNED_GOODS = 'returned-goods';
    public const SHIPMENT_TYPE_SALE_OF_GOODS = 'sale-of-goods';

    public const SHIPMENT_TYPES = [
        self::SHIPMENT_TYPE_COMMERCIAL_SAMPLE,
        self::SHIPMENT_TYPE_DOCUMENTS,
        self::SHIPMENT_TYPE_GIFT,
        self::SHIPMENT_TYPE_OTHER,
        self::SHIPMENT_TYPE_RETURNED_GOODS,
        self::SHIPMENT_TYPE_SALE_OF_GOODS,
    ];

    public const FEE_TYPE_SHIPPING_COSTS = 'shipping-costs';
    public const FEE_TYPE_INSURANCE = 'insurance';
    public const FEE_TYPES = [
        self::FEE_TYPE_SHIPPING_COSTS,
        self::FEE_TYPE_INSURANCE,
    ];

    private Parcel $parcel;

    /**
     * Type of shipment / Art der Sendung
     *
     * Field number CN 23: 10
     * Field exists on CN 22: yes
     */
    private ?string $typeOfShipment = null;

    /**
     * Explanation / Erklärung
     *
     * Field number CN 23: 10
     * Field exists on CN 22: no
     */
    private ?string $explanationIfTypeOfShipmentIsOther = null;

    /**
     * Comments / Bemerkungen
     *
     * (e.g.: Goods subject to quarantine, sanitary/phytosanitary inspection or other restrictions)
     *
     * Field number CN 23: 11
     * Field exists on CN 22: no
     */
    private string $comment = '';

    /**
     * Place of committal / Einlieferungsstelle
     *
     * Field number CN 23: field does not have a number
     * Field exists on CN 22: no
     */
    private string $officeOfOrigin = '';

    /**
     * Numbers of invoice / Nummern der Rechnung
     *
     * Field number CN 23: 14
     * Field exists on CN 22: no
     */
    private ?string $invoiceNumber = null;

    /**
     * Numbers of Permits or Licences / Nummern der Genehmigungen oder Lizenzen
     *
     * Field number CN 23: 12
     * Field exists on CN 22: no
     *
     * @var string[]
     */
    private array $permitNumbers = [];

    /**
     * Numbers of certificates / Nummern der Bescheinigungen
     *
     * Field number CN 23: 13
     * Field exists on CN 22: no
     *
     * @var string[]
     */
    private array $certificateNumbers = [];

    /**
     * Shipping costs and other fees like insurance / Versand-/Portokosten und Versicherungen
     *
     * Each component of the fees (like shipping costs, insurance, ...) has to be listed separately
     *
     * Field number CN 23: 9
     * Field exists on CN 22: no
     *
     * @var MoneyValue[]
     */
    private array $fees = [];

    /**
     * Date of invoice / Datum der Rechnung
     *
     * Field number CN 23: no
     * Field exists on CN 22: no
     *
     * @var string
     */
    private ?string $invoiceDate = null;

    public function __construct(Parcel $parcel)
    {
        $this->parcel = $parcel;
        $parcel->setCustomsInformation($this);
    }

    public function jsonSerialize(): array
    {
        return [
            'typeOfShipment' => $this->typeOfShipment,
            'officeOfOrigin' => $this->officeOfOrigin,
            'explanationIfTypeOfShipmentIsOther' => $this->explanationIfTypeOfShipmentIsOther,
            'invoiceNumbers' => [$this->invoiceNumber],
            'invoiceNumber' => $this->invoiceNumber,
            'invoiceDate' => $this->invoiceDate,
            'permitNumbers' => $this->permitNumbers,
            'certificateNumbers' => $this->certificateNumbers,
            'fees' => $this->fees,
            'comment' => $this->comment,
        ];
    }

    public static function fromArray(array $array, Parcel $parcel): self
    {
        $self = new self($parcel);

        $self->setTypeOfShipment($array['typeOfShipment']);
        $self->setOfficeOfOrigin($array['officeOfOrigin']);
        $self->setExplanationIfTypeOfShipmentIsOther($array['explanationIfTypeOfShipmentIsOther']);
        if (array_key_exists('invoiceNumbers', $array)) {
            $self->setInvoiceNumber($array['invoiceNumbers'][0] ?? null);
        } else {
            $self->setInvoiceNumber($array['invoiceNumber']);
        }
        $self->setInvoiceDate($array['invoiceDate'] ?? null);
        $self->setPermitNumbers($array['permitNumbers']);
        $self->setCertificateNumbers($array['certificateNumbers']);
        $self->setComment($array['comment']);
        $self->setFees(array_map(fn (array $feeArray) => MoneyValue::fromArray($feeArray), $array['fees']));

        return $self;
    }

    public function copyToParcel(Parcel $parcel): self
    {
        $copy = new self($parcel);
        $parcel->setCustomsInformation($copy);

        $copy->typeOfShipment = $this->typeOfShipment;
        $copy->explanationIfTypeOfShipmentIsOther = $this->explanationIfTypeOfShipmentIsOther;
        $copy->comment = $this->comment;
        $copy->officeOfOrigin = $this->officeOfOrigin;
        $copy->invoiceNumber = $this->invoiceNumber;
        $copy->invoiceDate = $this->invoiceDate;
        $copy->permitNumbers = $this->permitNumbers;
        $copy->certificateNumbers = $this->certificateNumbers;
        // MoneyValues are immutable, so we can safely just pass the same objects
        $copy->fees = $this->fees;

        return $copy;
    }

    public function getParcel(): Parcel
    {
        return $this->parcel;
    }

    public function getTypeOfShipment(): ?string
    {
        return $this->typeOfShipment;
    }

    public function setTypeOfShipment(?string $typeOfShipment): void
    {
        if ($typeOfShipment !== null && !in_array($typeOfShipment, self::SHIPMENT_TYPES, true)) {
            throw new InvalidArgumentException(sprintf('Type of shipment "%s" is not known.', $typeOfShipment));
        }
        $this->typeOfShipment = $typeOfShipment;
    }

    public function getExplanationIfTypeOfShipmentIsOther(): ?string
    {
        return $this->explanationIfTypeOfShipmentIsOther;
    }

    public function setExplanationIfTypeOfShipmentIsOther(?string $explanationIfTypeOfShipmentIsOther): void
    {
        $this->explanationIfTypeOfShipmentIsOther = $explanationIfTypeOfShipmentIsOther;
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function setComment(string $comment): void
    {
        $this->comment = $comment;
    }

    public function getOfficeOfOrigin(): string
    {
        return $this->officeOfOrigin;
    }

    public function setOfficeOfOrigin(string $officeOfOrigin): void
    {
        $this->officeOfOrigin = $officeOfOrigin;
    }

    /**
     * @deprecated Will be removed with next major version, use getInvoiceNumber instead.
     */
    public function getInvoiceNumbers(): array
    {
        return array_filter([$this->invoiceNumber]);
    }

    /**
     * @deprecated Will be removed with next major version, use setInvoiceNumber instead.
     */
    public function setInvoiceNumbers(array $invoiceNumbers): void
    {
        $this->invoiceNumber = $invoiceNumbers[0] ?? null;
    }

    /**
     * @deprecated Will be removed with next major version.
     */
    public function addInvoiceNumber(string $invoiceNumber): void
    {
        $this->invoiceNumber = $invoiceNumber;
    }

    /**
     * @return string[]
     */
    public function getPermitNumbers(): array
    {
        return $this->permitNumbers;
    }

    /**
     * @param string[] $permitNumbers
     */
    public function setPermitNumbers(array $permitNumbers): void
    {
        $this->permitNumbers = $permitNumbers;
    }

    public function addPermitNumber(string $numberOfPermit): void
    {
        $this->permitNumbers[] = $numberOfPermit;
    }

    /**
     * @return string[]
     */
    public function getCertificateNumbers(): array
    {
        return $this->certificateNumbers;
    }

    public function setCertificateNumbers(array $certificateNumbers): void
    {
        $this->certificateNumbers = $certificateNumbers;
    }

    public function addCertificateNumber(string $numberOfCertificate): void
    {
        $this->certificateNumbers[] = $numberOfCertificate;
    }

    /**
     * @return MoneyValue[]
     */
    public function getFees(): array
    {
        return $this->fees;
    }

    public function getFee(string $feeType): ?MoneyValue
    {
        if (!in_array($feeType, self::FEE_TYPES, true)) {
            throw new InvalidArgumentException(sprintf('Fee type "%s" is not known.', $feeType));
        }

        return $this->fees[$feeType];
    }

    /**
     * @param MoneyValue[] $fees
     */
    public function setFees(array $fees): void
    {
        $this->fees = [];
        foreach ($fees as $feeType => $fee) {
            $this->addFee($feeType, $fee);
        }
    }

    public function addFee(string $feeType, MoneyValue $fee): void
    {
        if (!in_array($feeType, self::FEE_TYPES, true)) {
            throw new InvalidArgumentException(sprintf('Fee type "%s" is not known.', $feeType));
        }
        if (isset($this->fees[$feeType])) {
            $this->fees[$feeType] = MoneyValue::sum($this->fees[$feeType], $fee);
        } else {
            $this->fees[$feeType] = $fee;
        }
    }

    public function getTotalFees(): MoneyValue
    {
        return MoneyValue::sum(...array_values($this->fees));
    }

    /**
     * Returns the total customs value of all items with the total fees ontop.
     * If any of the customs value of the items is not set, this value cannot be determined.
     */
    public function getTotalValue(): ?MoneyValue
    {
        $customsValues = array_map(function (ParcelItem $item) {
            $customsInformation = $item->getCustomsInformation();

            return $customsInformation ? $customsInformation->getCustomsValue() : null;
        }, $this->parcel->getItems());

        if (in_array(null, $customsValues)) {
            return null;
        }

        return MoneyValue::sum(
            $this->getTotalFees(),
            ...$customsValues,
        );
    }

    public function setInvoiceDate(?string $invoiceDate): void
    {
        $this->invoiceDate = $invoiceDate;
    }

    public function getInvoiceDate(): ?string
    {
        return $this->invoiceDate;
    }

    public function setInvoiceNumber(?string $invoiceNumber): void
    {
        $this->invoiceNumber = $invoiceNumber;
    }

    public function getInvoiceNumber(): ?string
    {
        return $this->invoiceNumber;
    }
}
