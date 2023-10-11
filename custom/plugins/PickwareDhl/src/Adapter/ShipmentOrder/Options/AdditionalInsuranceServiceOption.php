<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareDhl\Adapter\ShipmentOrder\Options;

use InvalidArgumentException;

class AdditionalInsuranceServiceOption extends ServiceOption
{
    public function __construct(float $insuranceAmountInEuro)
    {
        if ($insuranceAmountInEuro <= 0) {
            throw new InvalidArgumentException('Negative insurance amounts are not supported.');
        }
        parent::__construct('AdditionalInsurance', [
            'insuranceAmount' => sprintf('%01.2f', $insuranceAmountInEuro),
        ]);
    }
}
