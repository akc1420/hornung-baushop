<?php


namespace Crsw\CleverReachOfficial\Entity\Base\Definition;

use Crsw\CleverReachOfficial\Entity\Base\BaseEntity;
use Crsw\CleverReachOfficial\Entity\Base\Collection\BaseEntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\WriteProtected;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

/**
 * Class BaseEntityDefinition
 *
 * @package Crsw\CleverReachOfficial\Entity\Base\Definition
 */
class BaseEntityDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'cleverreach_entity';

    /**
     * Gets entity name.
     *
     * @return string
     */
    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    /**
     * Gets entity class.
     *
     * @return string
     */
    public function getEntityClass(): string
    {
        return BaseEntity::class;
    }

    /**
     * Gets collection class.
     *
     * @return string
     */
    public function getCollectionClass(): string
    {
        return BaseEntityCollection::class;
    }

    /**
     * @return FieldCollection
     */
    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('shopware_id', 'shopware_id'))->addFlags(new PrimaryKey(), new Required()),
            (new IntField('id', 'id'))->addFlags(new WriteProtected()),
            (new StringField('type', 'type'))->addFlags(new Required()),
            new StringField('index_1', 'index_1'),
            new StringField('index_2', 'index_2'),
            new StringField('index_3', 'index_3'),
            new StringField('index_4', 'index_4'),
            new StringField('index_5', 'index_5'),
            new StringField('index_6', 'index_6'),
            new StringField('index_7', 'index_7'),
            new StringField('index_8', 'index_8'),
            new StringField('index_9', 'index_9'),
            new StringField('index_10', 'index_10'),
            new LongTextField('data', 'data')
        ]);
    }

    /**
     * @return array
     */
    protected function defaultFields(): array
    {
        return [];
    }
}
