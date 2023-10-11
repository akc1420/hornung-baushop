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
use Pickware\ShippingBundle\Shipment\Country;

class GetAvailableReturnLocationRequest extends Request
{
    public function __construct(Country $country)
    {
        parent::__construct(
            'GET',
            sprintf('locations?%s', http_build_query(
                ['countryCode' => $country->getIso3Code()],
            )),
        );
    }
}
