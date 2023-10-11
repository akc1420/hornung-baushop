<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\ShippingBundle\Shipment;

use JsonSerializable;
use Pickware\ShippingBundle\Parcel\Parcel;

class ShipmentBlueprint implements JsonSerializable
{
    private Address $senderAddress;
    private Address $receiverAddress;

    /**
     * @var Parcel[]
     */
    private array $parcels = [];

    private ?string $carrierTechnicalName = null;
    private array $shipmentConfig = [];
    private ?string $customerReference = null;

    /**
     * @param Parcel[] $parcels
     */
    public function __construct(array $parcels = [])
    {
        $this->senderAddress = new Address();
        $this->receiverAddress = new Address();
        $this->parcels = $parcels;
    }

    public function jsonSerialize(): array
    {
        return [
            'senderAddress' => $this->senderAddress,
            'receiverAddress' => $this->receiverAddress,
            'parcels' => $this->parcels,
            'carrierTechnicalName' => $this->carrierTechnicalName,
            'shipmentConfig' => $this->shipmentConfig,
            'customerReference' => $this->customerReference,
        ];
    }

    public static function fromArray(array $array): self
    {
        $self = new self();
        $self->senderAddress = is_array($array['senderAddress'] ?? null) ? Address::fromArray($array['senderAddress']) : new Address();
        $self->receiverAddress = is_array($array['receiverAddress'] ?? null) ? Address::fromArray($array['receiverAddress']) : new Address();
        $self->parcels = array_map(fn (array $parcelArray) => Parcel::fromArray($parcelArray), $array['parcels'] ?? []);
        $self->carrierTechnicalName = isset($array['carrierTechnicalName']) ? strval($array['carrierTechnicalName']) : null;
        $self->shipmentConfig = is_array($array['shipmentConfig'] ?? null) ? $array['shipmentConfig'] : [];
        $self->customerReference = isset($array['customerReference']) ? strval($array['customerReference']) : null;

        return $self;
    }

    public function getSenderAddress(): Address
    {
        return $this->senderAddress;
    }

    public function setSenderAddress(Address $senderAddress): void
    {
        $this->senderAddress = $senderAddress;
    }

    public function getReceiverAddress(): Address
    {
        return $this->receiverAddress;
    }

    public function setReceiverAddress(Address $receiverAddress): void
    {
        $this->receiverAddress = $receiverAddress;
    }

    /**
     * @return Parcel[]
     */
    public function getParcels(): array
    {
        return $this->parcels;
    }

    public function addParcel(Parcel $parcel): void
    {
        $this->parcels[] = $parcel;
    }

    /**
     * @param Parcel[] $parcels
     */
    public function setParcels(array $parcels): void
    {
        $this->parcels = $parcels;
    }

    public function getCarrierTechnicalName(): ?string
    {
        return $this->carrierTechnicalName;
    }

    public function setCarrierTechnicalName(?string $carrierTechnicalName): void
    {
        $this->carrierTechnicalName = $carrierTechnicalName;
    }

    public function getShipmentConfig(): array
    {
        return $this->shipmentConfig;
    }

    public function setShipmentConfig(array $shipmentConfig): void
    {
        $this->shipmentConfig = $shipmentConfig;
    }

    public function getCustomerReference(): ?string
    {
        return $this->customerReference;
    }

    public function setCustomerReference(?string $customerReference): void
    {
        $this->customerReference = $customerReference;
    }
}
