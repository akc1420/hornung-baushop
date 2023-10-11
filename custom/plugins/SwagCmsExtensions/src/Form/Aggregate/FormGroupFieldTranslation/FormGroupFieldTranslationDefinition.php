<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Form\Aggregate\FormGroupFieldTranslation;

use Shopware\Core\Framework\DataAbstractionLayer\EntityTranslationDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Swag\CmsExtensions\Form\Aggregate\FormGroupField\FormGroupFieldDefinition;

class FormGroupFieldTranslationDefinition extends EntityTranslationDefinition
{
    public const ENTITY_NAME = 'swag_cms_extensions_form_group_field_translation';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return FormGroupFieldTranslationCollection::class;
    }

    public function getEntityClass(): string
    {
        return FormGroupFieldTranslationEntity::class;
    }

    protected function getParentDefinitionClass(): string
    {
        return FormGroupFieldDefinition::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new StringField('label', 'label'))->addFlags(new Required()),
            new StringField('placeholder', 'placeholder'),
            new LongTextField('error_message', 'errorMessage'),
            new JsonField('config', 'config'),
        ]);
    }
}
