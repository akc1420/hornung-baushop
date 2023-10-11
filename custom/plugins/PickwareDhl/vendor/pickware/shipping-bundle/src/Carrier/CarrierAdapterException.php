<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\ShippingBundle\Carrier;

use Exception;
use Pickware\HttpUtils\JsonApi\JsonApiError;
use Pickware\HttpUtils\JsonApi\JsonApiErrorSerializable;
use Throwable;

class CarrierAdapterException extends Exception implements JsonApiErrorSerializable
{
    private JsonApiError $jsonApiError;

    /**
     * @deprecated The first argument will be strictly typed JsonApiError in the next version
     * @param JsonApiError|string $jsonApiErrorOrExceptionString
     */
    public function __construct($jsonApiErrorOrExceptionString, Throwable $previous = null)
    {
        if ($jsonApiErrorOrExceptionString instanceof JsonApiError) {
            $this->jsonApiError = $jsonApiErrorOrExceptionString;
        } else {
            $this->jsonApiError = new JsonApiError(['detail' => $jsonApiErrorOrExceptionString]);
        }
        parent::__construct($this->jsonApiError->getDetail(), 0, $previous);
    }

    public function serializeToJsonApiError(): JsonApiError
    {
        return $this->jsonApiError ?? new JsonApiError(['detail' => $this->message]);
    }
}
