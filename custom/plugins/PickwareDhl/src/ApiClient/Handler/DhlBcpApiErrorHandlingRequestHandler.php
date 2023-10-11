<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareDhl\ApiClient\Handler;

use Pickware\PickwareDhl\ApiClient\DhlApiClientException;
use Pickware\ShippingBundle\Soap\SoapRequest;
use Pickware\ShippingBundle\Soap\SoapRequestHandler;
use SoapFault;
use stdClass;

class DhlBcpApiErrorHandlingRequestHandler implements SoapRequestHandler
{
    public function handle(SoapRequest $request, callable $next): stdClass
    {
        try {
            $response = $next($request);
        } catch (SoapFault $soapFault) {
            throw DhlApiClientException::dhlBcpApiCommunicationException($soapFault);
        }

        if ($response->Status->statusCode !== 0 && !isset($response->CreationState)) {
            throw DhlApiClientException::dhlBcpApiRespondedWithError($response->Status->statusText);
        }

        return $response;
    }
}
