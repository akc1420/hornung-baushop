<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\ShippingBundle\Http;

use GuzzleHttp\Psr7\Message;
use InvalidArgumentException;
use Pickware\HttpUtils\Sanitizer\HttpSanitizer as HttpUtilsHttpSanitizer;
use Pickware\HttpUtils\Sanitizer\HttpSanitizing;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class HttpLogger
{
    private LoggerInterface $logger;
    private HttpSanitizing $httpSanitizing;

    /**
     * @param HttpSanitizing|HttpSanitizer[]|HttpUtilsHttpSanitizer[] $httpSanitizing
     *
     * @deprecated The second constructor argument will be natively typed as HttpSanitizing.
     */
    public function __construct(LoggerInterface $logger, $httpSanitizing)
    {
        if ($httpSanitizing instanceof HttpSanitizing) {
            $this->httpSanitizing = $httpSanitizing;
        } elseif (is_array($httpSanitizing)) {
            $this->httpSanitizing = new HttpSanitizing(...$httpSanitizing);
        } else {
            throw new InvalidArgumentException(sprintf(
                'Second constructor argument must bei either %s or an array',
                HttpSanitizing::class,
            ));
        }

        $this->logger = $logger;
    }

    public function logSuccess(RequestInterface $request, ResponseInterface $response): void
    {
        $this->logger->debug('HTTP request successful', $this->getContext($request, $response));
    }

    public function logError(Throwable $reason, RequestInterface $request, ?ResponseInterface $response): void
    {
        $this->logger->error('HTTP request failed: ' . $reason->getMessage(), $this->getContext($request, $response));
    }

    private function getContext(RequestInterface $request, ?ResponseInterface $response): array
    {
        return [
            'request' => Message::toString($this->httpSanitizing->sanitizeRequest($request)),
            'response' => $response ? Message::toString($this->httpSanitizing->sanitizeResponse($response)) : null,
        ];
    }
}
