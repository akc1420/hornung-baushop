<?php declare(strict_types=1);

namespace Kzph\Simplenote\Core\Content\KzphSimplenoteNote;

use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;


class KzphSimplenoteNoteEntityOrderExtension extends EntityExtension
{
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            new OneToManyAssociationField('kzphSimplenote', KzphSimplenoteNoteDefinition::class, 'entity_id', 'id', false),
        );
    }

    public function getDefinitionClass(): string
    {
        return OrderDefinition::class;
    }
}