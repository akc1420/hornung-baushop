<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\ShippingBundle\ParcelPacking;

use JsonSerializable;
use Pickware\UnitsOfMeasurement\Dimensions\BoxDimensions;
use Pickware\UnitsOfMeasurement\PhysicalQuantity\Length;
use Pickware\UnitsOfMeasurement\PhysicalQuantity\Weight;

class ParcelPackingConfiguration implements JsonSerializable
{
    /**
     * @var Weight
     */
    private $fillerWeightPerParcel;

    /**
     * @var Weight|null
     */
    private $fallbackParcelWeight;

    /**
     * @var Weight|null
     */
    private $maxParcelWeight;
    private BoxDimensions $defaultBoxDimensions;

    public function __construct(
        Weight $fillerWeightPerParcel = null,
        ?Weight $fallbackParcelWeight = null,
        ?Weight $maxParcelWeight = null,
        ?BoxDimensions $defaultBoxDimensions = null
    ) {
        $this->fillerWeightPerParcel = $fillerWeightPerParcel;
        if ($this->fillerWeightPerParcel === null) {
            $this->fillerWeightPerParcel = new Weight(0, 'kg');
        }
        $this->fallbackParcelWeight = $fallbackParcelWeight;
        $this->maxParcelWeight = $maxParcelWeight;

        $this->defaultBoxDimensions = $defaultBoxDimensions ?? new BoxDimensions(
            new Length(0, 'm'),
            new Length(0, 'm'),
            new Length(0, 'm'),
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'fillerWeightPerParcel' => $this->fillerWeightPerParcel,
            'fallbackParcelWeight' => $this->fallbackParcelWeight,
            'maxParcelWeight' => $this->maxParcelWeight,
            'defaultBoxDimensions' => $this->defaultBoxDimensions,
        ];
    }

    public static function fromArray(array $array): self
    {
        return new self(
            isset($array['fillerWeightPerParcel']) ? Weight::fromArray($array['fillerWeightPerParcel']) : null,
            isset($array['fallbackParcelWeight']) ? Weight::fromArray($array['fallbackParcelWeight']) : null,
            isset($array['maxParcelWeight']) ? Weight::fromArray($array['maxParcelWeight']) : null,
            isset($array['defaultBoxDimensions']) ? BoxDimensions::fromArray($array['defaultBoxDimensions']) : null,
        );
    }

    public static function createDefault(): self
    {
        return new self();
    }

    /**
     * Creates a copy
     */
    public function createCopy(): self
    {
        $self = new self();
        $self->fillerWeightPerParcel = $this->fillerWeightPerParcel;
        $self->fallbackParcelWeight = $this->fallbackParcelWeight;
        $self->maxParcelWeight = $this->maxParcelWeight;
        $self->defaultBoxDimensions = $this->defaultBoxDimensions;

        return $self;
    }

    public function getFillerWeightPerParcel(): Weight
    {
        return $this->fillerWeightPerParcel;
    }

    public function getFallbackParcelWeight(): ?Weight
    {
        return $this->fallbackParcelWeight;
    }

    public function getMaxParcelWeight(): ?Weight
    {
        return $this->maxParcelWeight;
    }

    public function getDefaultBoxDimensions(): BoxDimensions
    {
        return $this->defaultBoxDimensions;
    }

    public function setMaxParcelWeight(?Weight $maxParcelWeight): void
    {
        $this->maxParcelWeight = $maxParcelWeight;
    }
}
