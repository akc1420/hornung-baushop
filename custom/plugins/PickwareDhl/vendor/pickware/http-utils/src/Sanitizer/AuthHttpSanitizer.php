<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\HttpUtils\Sanitizer;

class AuthHttpSanitizer implements HttpSanitizer
{
    private static $authorizationHeaderNames = [
        'AUTHORIZATION',
        'PHP-AUTH-USER',
        'PHP-AUTH-PW',
    ];

    public function filterHeader(string $headerName, string $headerValue): string
    {
        if ($headerName) {
            if (in_array(mb_strtoupper($headerName), AuthHttpSanitizer::$authorizationHeaderNames)) {
                return '*HIDDEN*';
            }

            return $headerValue;
        }

        return $headerValue;
    }

    public function filterBody(string $body): string
    {
        return $body;
    }
}
