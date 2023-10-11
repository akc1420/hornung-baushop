<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareDhl\ReturnLabel\Request;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Utils as GuzzleUtils;
use Pickware\PickwareDhl\Adapter\ShipmentOrder\ReturnShipmentOrder;

class CreateReturnLabelRequest extends Request
{
    public function __construct(ReturnShipmentOrder $returnShipmentOrder)
    {
        parent::__construct(
            'POST',
            sprintf('orders?%s', http_build_query(
                ['labelType' => 'SHIPMENT_LABEL'],
            )),
            ['Content-Type' => 'application/json'],
            GuzzleUtils::jsonEncode($returnShipmentOrder),
        );
    }
}
