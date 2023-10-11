<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareDhl\Config\Subscriber;

use Pickware\PickwareDhl\Config\DhlConfig;
use Pickware\ShippingBundle\Config\Model\ShippingMethodConfigEntity;
use Shopware\Core\Framework\Struct\Struct;

class DhlConfigurationPageExtension extends Struct
{
    public const PAGE_EXTENSION_NAME = 'pickwareDhlConfiguration';

    /**
     * @var ShippingMethodConfigEntity[]
     */
    protected array $shippingMethodConfigurations;
    protected DhlConfig $dhlConfig;

    public function getShippingMethodConfigurations(): array
    {
        return $this->shippingMethodConfigurations;
    }

    public function getDhlConfig(): DhlConfig
    {
        return $this->dhlConfig;
    }
}
