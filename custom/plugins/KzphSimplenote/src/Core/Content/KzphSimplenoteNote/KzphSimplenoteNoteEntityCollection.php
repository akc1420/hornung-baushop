<?php declare(strict_types=1);

namespace Kzph\Simplenote\Core\Content\KzphSimplenoteNote;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class KzphSimplenoteNoteEntityCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return KzphSimplenoteNoteEntity::class;
    }
}