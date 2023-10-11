<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareDhl\Installation;

use Pickware\PickwareDhl\Config\DhlConfig;
use Pickware\PickwareDhl\ReturnLabel\ReturnLabelMailTemplate;
use Pickware\ShippingBundle\Carrier\Carrier;
use Pickware\ShippingBundle\ParcelPacking\ParcelPackingConfiguration;
use Pickware\UnitsOfMeasurement\PhysicalQuantity\Weight;

class DhlCarrier extends Carrier
{
    public const TECHNICAL_NAME = 'dhl';

    public function __construct()
    {
        parent::__construct(
            self::TECHNICAL_NAME,
            'DHL Geschäftskundenversand',
            'DHL',
            DhlConfig::CONFIG_DOMAIN,
            __DIR__ . '/../Resources/config/ShipmentConfigDescription.yaml',
            __DIR__ . '/../Resources/config/StorefrontConfigDescription.yaml',
            __DIR__ . '/../Resources/config/ReturnShipmentConfigDescription.yaml',
            new ParcelPackingConfiguration(
                null,
                null,
                new Weight(31.5, 'kg'),
            ),
            ReturnLabelMailTemplate::TECHNICAL_NAME,
            10,
        );
    }
}
