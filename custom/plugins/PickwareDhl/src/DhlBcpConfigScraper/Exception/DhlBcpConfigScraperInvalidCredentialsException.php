<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareDhl\DhlBcpConfigScraper\Exception;

class DhlBcpConfigScraperInvalidCredentialsException extends AbstractDhlBcpConfigScraperException
{
    public static function usernameOrPasswordMissing(): self
    {
        return new self('The user name and the password cannot be empty.');
    }

    public static function loginFailed(): self
    {
        return new self('The login to DHL BCP failed due to invalid credentials. Please check username and password.');
    }
}
