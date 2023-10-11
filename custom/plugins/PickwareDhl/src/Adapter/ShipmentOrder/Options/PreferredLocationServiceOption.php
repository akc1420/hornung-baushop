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

class PreferredLocationServiceOption extends ServiceOption
{
    private const MAX_LENGTH = 100;

    public function __construct(string $preferredLocation)
    {
        if (mb_strlen($preferredLocation) > self::MAX_LENGTH) {
            throw new InvalidArgumentException(sprintf(
                'The value for the preferred neighbour service should not be longer than %s characters.',
                self::MAX_LENGTH,
            ));
        }

        parent::__construct('PreferredLocation', ['details' => $preferredLocation]);
    }
}
