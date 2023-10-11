<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareErpStarter\ReturnOrder;

use Exception;
use Pickware\HttpUtils\JsonApi\JsonApiError;
use Pickware\HttpUtils\JsonApi\JsonApiErrors;
use Pickware\HttpUtils\JsonApi\JsonApiErrorSerializable;

class ReturnOrderException extends Exception implements JsonApiErrorSerializable
{
    private const ERROR_CODE_NAMESPACE = 'PICKWARE_ERP_RETURN_ORDER_BASIC_ADMINISTRATION_BUNDLE__RETURN_ORDER__';
    public const INVALID_VERSION_CONTEXT = self::ERROR_CODE_NAMESPACE . 'INVALID_VERSION_CONTEXT';
    public const MISSING_WAREHOUSE_ID = self::ERROR_CODE_NAMESPACE . 'MISSING_WAREHOUSE_ID';
    public const INVALID_QUANTITIES = self::ERROR_CODE_NAMESPACE . 'INVALID_QUANTITIES';
    public const ERRORS_DURING_CREATION = self::ERROR_CODE_NAMESPACE . 'ERRORS_DURING_CREATION';
    public const NO_TRANSACTION_IN_ORDER = self::ERROR_CODE_NAMESPACE . 'NO_TRANSACTION_IN_ORDER';
    public const RETURN_ORDER_NOT_FOUND = self::ERROR_CODE_NAMESPACE . 'RETURN_ORDER_NOT_FOUND';
    public const ORDER_NOT_FOUND = self::ERROR_CODE_NAMESPACE . 'ORDER_NOT_FOUND';

    private JsonApiError $jsonApiError;

    public function __construct(JsonApiError $jsonApiError)
    {
        $this->jsonApiError = $jsonApiError;
        parent::__construct($jsonApiError->getDetail());
    }

    public function serializeToJsonApiError(): JsonApiError
    {
        return $this->jsonApiError;
    }

    public static function errorsDuringCreation(JsonApiErrors $errors): self
    {
        return new self(new JsonApiError([
            'code' => self::ERRORS_DURING_CREATION,
            'title' => 'Multiple errors while creating the return order',
            'detail' => 'The return order could not be created because errors occurred',
            'meta' => ['errors' => $errors->jsonSerialize()],
        ]));
    }

    public static function returnOrderNotFound(array $expectedReturnOrderIds, array $actualReturnOrderIds): self
    {
        return new self(new JsonApiError([
            'code' => self::RETURN_ORDER_NOT_FOUND,
            'title' => 'Return order was not found',
            'detail' => sprintf(
                'At least one of the requested return orders was not found. Expected return orders : %s. Actual return orders: %s.',
                implode(', ', $expectedReturnOrderIds),
                implode(', ', $actualReturnOrderIds),
            ),
            'meta' => [
                'expectedOrderIds' => $expectedReturnOrderIds,
                'actualOrderIds' => $actualReturnOrderIds,
            ],
        ]));
    }

    public static function orderNotFound(array $expectedOrderIds, array $actualOrderIds): self
    {
        return new self(new JsonApiError([
            'code' => self::ORDER_NOT_FOUND,
            'title' => 'Order was not found',
            'detail' => sprintf(
                'At least one of the requested orders was not found. Expected orders : %s. Actual orders: %s.',
                implode(', ', $expectedOrderIds),
                implode(', ', $actualOrderIds),
            ),
            'meta' => [
                'expectedOrderIds' => $expectedOrderIds,
                'actualOrderIds' => $actualOrderIds,
            ],
        ]));
    }

    public static function noTransactionInOrder(string $orderId): self
    {
        return new self(new JsonApiError([
            'code' => self::NO_TRANSACTION_IN_ORDER,
            'title' => 'There is no transaction in the order',
            'detail' => 'There is no transaction in the order. No refund payment method can bet set.',
            'meta' => ['orderId' => $orderId],
        ]));
    }

    public static function invalidVersionContext(): self
    {
        return new self(new JsonApiError([
            'code' => self::INVALID_VERSION_CONTEXT,
            'title' => 'Invalid version context',
            'detail' => 'Creating a return order is only allowed in live version context.',
        ]));
    }

    public static function missingWarehouseIdForRestocked($lineItemId): self
    {
        return new self(new JsonApiError([
            'code' => self::MISSING_WAREHOUSE_ID,
            'title' => 'Missing warehouse id',
            'detail' => sprintf(
                'In order to restock the product with id "%s", a warehouse must be specified to which the product should ' .
                'be restocked via the property "warehouseId".',
                $lineItemId,
            ),
            'meta' => ['lineItemId' => $lineItemId],
        ]));
    }
}
