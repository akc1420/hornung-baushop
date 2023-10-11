<?php

namespace Sisi\Search\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * Class ContextService
 * @package Sisi\Search\Service
 *  @SuppressWarnings(PHPMD.StaticAccess)
 */

class ContextService
{
    public function getContext(): Context
    {
        return Context::createDefaultContext();
    }

    public function getUid(): string
    {
        return Uuid::randomBytes();
    }

    public function getRandom(): string
    {
        return Uuid::randomBytes();
    }

    public function getRandomHex(): string
    {
        return Uuid::randomHex();
    }

    public function getFromHexToBytes(string $valueId): string
    {
        return Uuid::fromHexToBytes($valueId);
    }
}
