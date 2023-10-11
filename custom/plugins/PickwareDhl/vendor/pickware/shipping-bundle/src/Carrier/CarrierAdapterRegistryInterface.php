<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\ShippingBundle\Carrier;

use Pickware\ShippingBundle\Carrier\Capabilities\CancellationCapability;
use Pickware\ShippingBundle\Carrier\Capabilities\ReturnShipmentCancellationCapability;
use Pickware\ShippingBundle\Carrier\Capabilities\ReturnShipmentsRegistrationCapability;

interface CarrierAdapterRegistryInterface
{
    public function addCarrierAdapter(string $technicalName, AbstractCarrierAdapter $carrierAdapter): void;
    public function hasCarrierAdapter(string $technicalName): bool;
    public function getCarrierAdapterByTechnicalName(string $technicalName): AbstractCarrierAdapter;
    public function getCancellationCapability(string $carrierTechnicalName): CancellationCapability;
    public function getReturnShipmentsCapability(string $carrierTechnicalName): ReturnShipmentsRegistrationCapability;
    public function getReturnShipmentCancellationCapability(string $carrierTechnicalName): ReturnShipmentCancellationCapability;
}
