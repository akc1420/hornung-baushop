<?php declare(strict_types=1);

namespace Recommendy\Core\Content\BundleMatrix;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Inherited;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class BundleMatrixDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'recommendy_bundle_matrix';

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
        return BundleMatrixEntity::class;
    }

    /**
     * @return string
     */
    public function getCollectionClass(): string
    {
        return BundleMatrixCollection::class;
    }

    /**
     * @return FieldCollection
     */
    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new StringField('pon', 'primaryProductId'))->addFlags(new ApiAware(), new Required(), new Inherited()),
            (new StringField('son', 'secondaryProductId'))->addFlags(new ApiAware(), new Required(), new Inherited()),
            (new FloatField('similarity', 'similarity'))->addFlags(new ApiAware(), new Required(), new Inherited()),
            (new StringField('shop', 'shop'))->addFlags(new ApiAware(), new Required(), new Inherited()),
        ]);
    }
}
