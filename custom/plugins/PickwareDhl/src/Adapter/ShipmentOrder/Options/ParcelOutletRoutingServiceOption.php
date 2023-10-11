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

/**
 * ParcelOutletRouting - Wenn aktiviert, wird das Paket bei Nicht-Zustellbarkeit zuerst an eine nahegelegene Filiale
 * übergeben, wo der Kunde dann eine zweite Chance bekommt es entgegenzunehmen. Verringert Chance auf Rücksendung.
 */
class ParcelOutletRoutingServiceOption extends ServiceOption
{
    public function __construct()
    {
        parent::__construct('ParcelOutletRouting');
    }
}
