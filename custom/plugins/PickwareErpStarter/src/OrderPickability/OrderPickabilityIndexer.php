<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareErpStarter\OrderPickability;

use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexer;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexingMessage;

/**
 * This class exists as a backwards compatibility to prevent container crashes as since SW 6.4.17.0 all indexers are
 * loaded during plugin update (through a subscriber that reacts on all entity written events).
 *
 * For further info see: https://github.com/pickware/shopware-plugins/issues/3892
 *
 * @deprecated Will be removed with the 3.0.0 update as obsolete.
 */
class OrderPickabilityIndexer extends EntityIndexer
{
    public function getName(): string
    {
        return '';
    }

    public function iterate($offset): ?EntityIndexingMessage
    {
        return null;
    }

    public function update(EntityWrittenContainerEvent $event): ?EntityIndexingMessage
    {
        return null;
    }

    public function handle(EntityIndexingMessage $message): void
    {
    }
}
