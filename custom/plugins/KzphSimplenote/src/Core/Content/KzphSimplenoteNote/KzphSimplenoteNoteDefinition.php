<?php declare(strict_types=1);

namespace Kzph\Simplenote\Core\Content\KzphSimplenoteNote;

use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\VersionField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;

class KzphSimplenoteNoteDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'kzph_simplenote';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return KzphSimplenoteNoteEntity::class;
    }

    public function getCollectionClass(): string
    {
        return KzphSimplenoteNoteEntityCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),

            (new IdField('entity_id', 'entityId')),
            (new StringField('entity_type', 'entityType')),

            (new StringField('username', 'username')),
            (new LongTextField('note', 'note')),
            (new IntField('replicate_in_order', 'replicateInOrder')),
            (new IntField('done', 'done')),
            (new IntField('show_desktop', 'showDesktop')),
            (new IntField('show_message', 'showMessage')),

            (new ManyToOneAssociationField('order', 'entity_id', OrderDefinition::class, 'id', false)),
        ]);
    }
}