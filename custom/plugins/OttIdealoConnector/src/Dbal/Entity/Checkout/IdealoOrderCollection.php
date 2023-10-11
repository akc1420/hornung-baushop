<?php declare(strict_types=1);

namespace Ott\IdealoConnector\Dbal\Entity\Checkout;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class IdealoOrderCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return IdealoOrderEntity::class;
    }
}
