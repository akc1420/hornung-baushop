<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareDhl\ApiClient;

use Pickware\ShippingBundle\Config\Config;

class DhlApiClientConfig
{
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function getUsername(): string
    {
        return $this->config['username'] ?? '';
    }

    public function getPassword(): string
    {
        return $this->config['password'] ?? '';
    }

    public function shouldUseTestingEndpoint(): bool
    {
        return $this->config['useTestingEndpoint'] ?? false;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }
}
