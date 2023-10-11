<?php declare(strict_types=1);

namespace Recommendy\Core\Content\Identifier;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Inherited;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class IdentifierDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'recommendy_identifier';

    /**
     * @return string
     */
    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    /**
     * @return string
     */
    public function getEntityClass(): string
    {
        return IdentifierEntity::class;
    }

    /**
     * @return string
     */
    public function getCollectionClass(): string
    {
        return IdentifierCollection::class;
    }

    /**
     * @return FieldCollection
     */
    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new StringField('pon', 'primaryProductId'))->addFlags(new ApiAware(), new Required(), new Inherited()),
            (new StringField('identifier', 'identifier'))->addFlags(new ApiAware(), new Required(), new Inherited()),
        ]);
    }
}
