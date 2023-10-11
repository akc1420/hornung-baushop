<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\ShippingBundle\ParcelHydration;

use InvalidArgumentException;
use Pickware\ShippingBundle\Notifications\Notification;

class ParcelHydrationNotification extends Notification
{
    public const NOTIFICATION_CODE_PARCEL_ITEM_CUSTOMS_INFORMATION_INVALID = 'PICKWARE_SHIPPING_BUNDLE__PARCEL_HYDRATION__PARCEL_ITEM_CUSTOMS_INFORMATION_INVALID';

    public static function parcelItemCustomsInformationInvalid(
        string $orderLineItemLabel,
        InvalidArgumentException $exception
    ): self {
        return new self(
            self::NOTIFICATION_CODE_PARCEL_ITEM_CUSTOMS_INFORMATION_INVALID,
            sprintf(
                'The order line item "%s" has invalid customs information configured in its respective product\'s custom fields. Error: %s',
                $orderLineItemLabel,
                $exception->getMessage(),
            ),
            $exception,
        );
    }
}
