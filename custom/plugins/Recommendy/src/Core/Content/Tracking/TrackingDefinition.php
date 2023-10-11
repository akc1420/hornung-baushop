<?php declare(strict_types=1);

namespace Recommendy\Core\Content\Tracking;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Inherited;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class TrackingDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'recommendy_tracking';

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
        return TrackingEntity::class;
    }

    /**
     * @return string
     */
    public function getCollectionClass(): string
    {
        return TrackingCollection::class;
    }

    /**
     * @return FieldCollection
     */
    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new IntField('action', 'action'))->addFlags(new ApiAware(), new Required(), new Inherited()),
            (new StringField('pon', 'primaryProductId'))->addFlags(new ApiAware(), new Required(), new Inherited()),
            (new FloatField('price', 'price'))->addFlags(new ApiAware(), new Required(), new Inherited()),
            (new StringField('sessionId', 'sessionId'))->addFlags(new ApiAware(), new Required(), new Inherited()),
            (new StringField('created', 'created'))->addFlags(new ApiAware(), new Required(), new Inherited()),
        ]);
    }
}
