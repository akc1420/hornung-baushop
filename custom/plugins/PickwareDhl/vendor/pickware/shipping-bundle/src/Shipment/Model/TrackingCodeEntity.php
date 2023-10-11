<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\ShippingBundle\Shipment\Model;

use Pickware\DalBundle\Association\Exception\AssociationNotLoadedException;
use Pickware\DocumentBundle\Model\DocumentCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class TrackingCodeEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $trackingCode;

    /**
     * @var string|null
     */
    protected $trackingUrl;

    /**
     * @var array carrier specific meta information for this tracking code
     */
    protected $metaInformation;

    /**
     * @var DocumentCollection
     */
    protected $documents;

    /**
     * @var ShipmentEntity
     */
    protected $shipment;

    /**
     * @var string
     */
    protected $shipmentId;

    public function getTrackingCode(): string
    {
        return $this->trackingCode;
    }

    public function setTrackingCode(string $trackingCode): void
    {
        $this->trackingCode = $trackingCode;
    }

    public function getTrackingUrl(): ?string
    {
        return $this->trackingUrl;
    }

    public function setTrackingUrl(?string $trackingUrl): void
    {
        $this->trackingUrl = $trackingUrl;
    }

    public function getDocuments(): DocumentCollection
    {
        if ($this->documents === null) {
            throw new AssociationNotLoadedException('documents', $this);
        }

        return $this->documents;
    }

    public function setDocuments(DocumentCollection $documents): void
    {
        $this->documents = $documents;
    }

    public function getShipment(): ShipmentEntity
    {
        if ($this->shipment === null) {
            throw new AssociationNotLoadedException('shipment', $this);
        }

        return $this->shipment;
    }

    public function setShipment(ShipmentEntity $shipment): void
    {
        $this->shipment = $shipment;
        $this->shipmentId = $shipment->getId();
    }

    public function getShipmentId(): string
    {
        return $this->shipmentId;
    }

    public function setShipmentId(string $shipmentId): void
    {
        if ($this->shipment && $this->shipment->getId() !== $shipmentId) {
            $this->shipment = null;
        }

        $this->shipmentId = $shipmentId;
    }

    public function getMetaInformation(): array
    {
        return $this->metaInformation;
    }

    public function setMetaInformation(array $metaInformation): void
    {
        $this->metaInformation = $metaInformation;
    }
}
