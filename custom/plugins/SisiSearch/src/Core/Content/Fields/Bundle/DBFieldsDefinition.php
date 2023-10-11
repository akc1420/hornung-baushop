<?php

declare(strict_types=1);

namespace Sisi\Search\Core\Content\Fields\Bundle;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
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
use Swag\BundleExample\Core\Content\Bundle\Aggregate\BundleProduct\BundleProductDefinition;
use Swag\BundleExample\Core\Content\Bundle\Aggregate\BundleTranslation\BundleTranslationDefinition;

/**
 * Class DBFieldsDefinition
 * @package Sisi\Search\Core\Content\Fields\Bundle
 * @SuppressWarnings(PHPMD)
 */

class DBFieldsDefinition extends EntityDefinition
{
    public function getEntityName(): string
    {
        return 's_plugin_sisi_search_es_fields';
    }

    public function getEntityClass(): string
    {
        return DBFieldsEntity::class;
    }

    public function getCollectionClass(): string
    {
        return DBFieldsCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection(
            [
                (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
                (new StringField('name', 'name'))->addFlags(new Required()),
                (new StringField('tablename', 'tablename'))->addFlags(new Required()),
                (new StringField('fieldtype', 'fieldtype')),
                (new StringField('tokenizer', 'tokenizer')),
                (new StringField('shop', 'shop')),
                (new StringField('format', 'format')),
                (new StringField('filter1', 'filter1')),
                (new StringField('filter2', 'filter2')),
                (new StringField('filter3', 'filter3')),
                (new StringField('stemming', 'stemming')),
                (new StringField('booster', 'booster')),
                (new StringField('pattern', 'pattern')),
                (new IntField('minedge', 'minedge')),
                (new StringField('stemmingstop', 'stemmingstop')),
                (new StringField('stop', 'stop')),
                (new IntField('edge', 'edge')),
                (new IntField('minedge', 'minedge')),
                (new StringField('strip', 'strip')),
                (new StringField('strip_str', 'strip_str')),
                (new StringField('fuzzy', 'fuzzy')),
                (new StringField('maxexpansions', 'maxexpansions')),
                (new StringField('slop', 'slop')),
                (new StringField('operator', 'operator')),
                (new StringField('autosynonyms', 'autosynonyms')),
                (new StringField('minimumshouldmatch', 'minimumshouldmatch')),
                (new StringField('prefixlength', 'prefixlength')),
                (new StringField('lenient', 'lenient')),
                (new StringField('synonym', 'synonym', 6000)),
                (new StringField('punctuation', 'punctuation')),
                (new StringField('whitespace', 'whitespace')),
                (new StringField('exclude', 'exclude')),
                (new StringField('merge', 'merge')),
                (new StringField('prefix', 'prefix')),
                (new StringField('phpfilter', 'phpfilter')),
                (new StringField('shoplanguage', 'shoplanguage')),
                (new StringField('onlymain', 'onlymain')),
                (new StringField('excludesearch', 'excludesearch')),
            ]
        );
    }
}
