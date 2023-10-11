<?php declare(strict_types=1);

namespace Ott\IdealoConnector\Dbal\Entity\Checkout;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class IdealoOrderDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'idealo_order';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return IdealoOrderCollection::class;
    }

    public function getEntityClass(): string
    {
        return IdealoOrderEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            (new IdField('order_id', 'orderId'))->addFlags(new Required()),
            new StringField('idealo_transaction_id', 'idealoTransactionId'),
        ]);
    }
}
