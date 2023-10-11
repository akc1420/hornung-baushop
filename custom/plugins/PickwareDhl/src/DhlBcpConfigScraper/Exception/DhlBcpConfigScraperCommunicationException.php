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

use Exception;

class DhlBcpConfigScraperCommunicationException extends AbstractDhlBcpConfigScraperException
{
    public static function unexpectedError(Exception $previousException): self
    {
        $message = sprintf('There occurred an unexpected error: %s', $previousException->getMessage());

        return new self($message, 0, $previousException);
    }
}
