<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareDhl\Adapter\Request;

use InvalidArgumentException;
use Pickware\ShippingBundle\Soap\SoapRequest;

class CreateShipmentOrderRequest extends SoapRequest
{
    /**
     * @var array
     */
    private $shipmentOrders;

    public function __construct(array $shipmentOrders)
    {
        if (count($shipmentOrders) === 0) {
            throw new InvalidArgumentException(sprintf(
                'The array passed to %s must contain at least one element.',
                __METHOD__,
            ));
        }
        parent::__construct('createShipmentOrder', []);
        $this->shipmentOrders = $shipmentOrders;
    }

    public function getBody(): array
    {
        return [
            'ShipmentOrder' => $this->shipmentOrders,
            'labelResponseType' => 'B64',
            'combinedPrinting' => 0,
        ];
    }
}
