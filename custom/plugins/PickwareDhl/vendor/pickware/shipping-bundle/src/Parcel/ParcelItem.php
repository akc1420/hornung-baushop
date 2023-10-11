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

use JsonSerializable;
use Pickware\UnitsOfMeasurement\Dimensions\BoxDimensions;
use Pickware\UnitsOfMeasurement\PhysicalQuantity\Weight;

class ParcelItem implements JsonSerializable
{
    private int $quantity;
    private ?Weight $unitWeight;
    private ?BoxDimensions $unitDimensions = null;
    private ?string $name = null;
    private ?ParcelItemCustomsInformation $customsInformation = null;

    public function __construct(int $quantity, ?Weight $unitWeight = null)
    {
        $this->quantity = $quantity;
        $this->unitWeight = $unitWeight;
    }

    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'unitWeight' => $this->unitWeight,
            'unitDimensions' => $this->unitDimensions,
            'quantity' => $this->quantity,
            'customsInformation' => $this->customsInformation,
        ];
    }

    public static function fromArray(array $array): self
    {
        $self = new self(intval($array['quantity'] ?? 0));

        $self->setName($array['name']);
        $self->setUnitWeight(isset($array['unitWeight']) ? Weight::fromArray($array['unitWeight']) : null);
        $self->setUnitDimensions(isset($array['unitDimensions']) ? BoxDimensions::fromArray($array['unitDimensions']) : null);
        $self->setCustomsInformation(isset($array['customsInformation']) ? ParcelItemCustomsInformation::fromArray($array['customsInformation'], $self) : null);

        return $self;
    }

    public function __clone()
    {
        if ($this->unitWeight) {
            $this->unitWeight = clone $this->unitWeight;
        }
        if ($this->unitDimensions) {
            $this->unitDimensions = clone $this->unitDimensions;
        }
    }

    public function getTotalWeight(): ?Weight
    {
        if (!$this->getUnitWeight()) {
            return null;
        }

        return $this->unitWeight->multiplyWithScalar($this->quantity);
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getUnitWeight(): ?Weight
    {
        return $this->unitWeight;
    }

    public function setUnitWeight(?Weight $unitWeight): void
    {
        $this->unitWeight = $unitWeight;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function getUnitDimensions(): ?BoxDimensions
    {
        return $this->unitDimensions;
    }

    public function setUnitDimensions(?BoxDimensions $unitDimensions): void
    {
        $this->unitDimensions = $unitDimensions;
    }

    public function getCustomsInformation(): ?ParcelItemCustomsInformation
    {
        return $this->customsInformation;
    }

    public function setCustomsInformation(?ParcelItemCustomsInformation $customsInformation): void
    {
        $this->customsInformation = $customsInformation;
    }
}
