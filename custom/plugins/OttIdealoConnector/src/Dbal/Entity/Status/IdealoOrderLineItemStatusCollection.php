<?php declare(strict_types=1);

namespace Ott\IdealoConnector\Dbal\Entity\Status;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class IdealoOrderLineItemStatusCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return IdealoOrderLineItemStatusEntity::class;
    }
}
