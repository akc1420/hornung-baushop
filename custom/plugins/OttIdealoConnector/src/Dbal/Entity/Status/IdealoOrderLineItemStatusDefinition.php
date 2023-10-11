<?php declare(strict_types=1);

namespace Ott\IdealoConnector\Dbal\Entity\Status;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class IdealoOrderLineItemStatusDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'idealo_order_line_item_status';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return IdealoOrderLineItemStatusCollection::class;
    }

    public function getEntityClass(): string
    {
        return IdealoOrderLineItemStatusEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            (new IdField('idealo_order_id', 'idealoOrderId'))->addFlags(new Required()),
            (new IdField('line_item_id', 'lineItemId'))->addFlags(new Required()),
            new StringField('status', 'status'),
        ]);
    }
}
