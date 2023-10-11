<?php
declare(strict_types=1);

namespace Swag\Security\Fixes\NEXT23325;

use Shopware\Core\Content\Product\Cart\ProductStockReachedError as OriginalProductStockReachedError;

class ProductStockReachedError extends OriginalProductStockReachedError
{
    public function getLevel(): int
    {
        return self::LEVEL_ERROR;
    }

    public function isPersistent(): bool
    {
        return false;
    }
}
