<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareErpStarter\OrderShipping;

use Exception;
use Pickware\HttpUtils\JsonApi\JsonApiError;
use Pickware\HttpUtils\JsonApi\JsonApiErrorSerializable;

class OrderParcelException extends Exception implements JsonApiErrorSerializable
{
    private const ERROR_CODE_NAMESPACE = 'PICKWARE_ERP__ORDER_PARCEL__';
    public const ERROR_CODE_NO_ORDER_DELIVERIES = self::ERROR_CODE_NAMESPACE . 'NO_ORDER_DELIVERIES';
    public const ERROR_CODE_ORDER_OVERFULFILLED = self::ERROR_CODE_NAMESPACE . 'ORDER_OVERFULFILLED';

    private JsonApiError $jsonApiError;

    public function __construct(JsonApiError $jsonApiError)
    {
        $this->jsonApiError = $jsonApiError;
        parent::__construct($jsonApiError->getDetail());
    }

    public static function overfulfillmentOfOrder(string $orderId): self
    {
        return new self(new JsonApiError([
            'code' => self::ERROR_CODE_ORDER_OVERFULFILLED,
            'title' => 'Order is overfulfilled',
            'detail' => 'No more products can be shipped than have been ordered, otherwise the order would be overfulfilled.',
            'meta' => [
                'orderId' => $orderId,
            ],
        ]));
    }

    public function serializeToJsonApiError(): JsonApiError
    {
        return $this->jsonApiError;
    }

    public static function noOrderDeliveries(string $orderId): self
    {
        return new self(new JsonApiError([
            'code' => self::ERROR_CODE_NO_ORDER_DELIVERIES,
            'title' => 'No order deliveries',
            'detail' => 'The given order has no order deliveries.',
            'meta' => [
                'orderId' => $orderId,
            ],
        ]));
    }
}
