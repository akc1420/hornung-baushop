<?php

declare(strict_types=1);

namespace Sisi\Search\Core\Content\Task\Bundle;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

/**
 * Class DBSchedularDefinition
 * @package Sisi\Search\Core\Content\Task\Bundle
 * @SuppressWarnings(PHPMD)
 */

class DBSchedularDefinition extends EntityDefinition
{
    public function getEntityName(): string
    {
        return 'sisi_search_es_scheduledtask';
    }

    public function getEntityClass(): string
    {
        return DBSchedularEntity::class;
    }

    public function getCollectionClass(): string
    {
        return DBSchedularCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection(
            [
                (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
                (new StringField('title', 'title'))->addFlags(new Required()),
                (new IntField('time', 'time')),
                (new StringField('shop', 'shop')),
                (new StringField('language', 'language')),
                (new IntField('limit', 'limit')),
                (new IntField('days', 'days')),
                (new IntField('all', 'all')),
                (new StringField('kind', 'kind')),
                (new StringField('aktive', 'aktive')),
                new DateTimeField('last_execution_time', 'lastExecutionTime'),
                (new DateTimeField('next_execution_time', 'nextExecutionTime'))->addFlags(new Required()),
            ]
        );
    }
}
