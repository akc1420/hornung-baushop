<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\ShippingBundle\Logging;

use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Log\LoggerFactory;

class PickwareShippingLoggerFactory extends LoggerFactory
{
    /**
     * @var LoggerFactory
     */
    private $shopwareLoggerFactory;

    /**
     * @var bool
     */
    private $debug;

    public function __construct(LoggerFactory $shopwareLoggerFactory, bool $debug)
    {
        $this->shopwareLoggerFactory = $shopwareLoggerFactory;
        $this->debug = $debug;
    }

    public function createRotating(
        string $filePrefix,
        ?int $fileRotationCount = null,
        ?int $loggerLevel = null
    ): LoggerInterface {
        if ($loggerLevel === null) {
            $loggerLevel = $this->debug ? Logger::DEBUG : Logger::INFO;
        }

        return $this->shopwareLoggerFactory->createRotating($filePrefix, $fileRotationCount, $loggerLevel);
    }
}
