<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\ShippingBundle\ParcelPacking;

use Pickware\ShippingBundle\Notifications\Notification;
use Pickware\ShippingBundle\ParcelPacking\BinPacking\BinPackingException;

class ParcelPackingNotification extends Notification
{
    public const NOTIFICATION_CODE_BIN_PACKING_FAILED = 'PICKWARE_SHIPPING_BUNDLE__PARCEL_PACKING__BIN_PACKING_FAILED';

    public static function binPackingFailed(BinPackingException $e): self
    {
        return new self(
            self::NOTIFICATION_CODE_BIN_PACKING_FAILED,
            'The shipment could not be distributed into packages because of the following error: ' . $e->getMessage(),
            $e,
        );
    }
}
