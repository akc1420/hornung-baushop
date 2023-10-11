<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Form\Aggregate\FormGroupField;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Swag\CmsExtensions\Form\Aggregate\FormGroup\FormGroupDefinition;
use Swag\CmsExtensions\Form\Aggregate\FormGroupFieldTranslation\FormGroupFieldTranslationDefinition;

class FormGroupFieldDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'swag_cms_extensions_form_group_field';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return FormGroupFieldEntity::class;
    }

    public function getCollectionClass(): string
    {
        return FormGroupFieldCollection::class;
    }

    /**
     * @return array<string, bool>
     */
    public function getDefaults(): array
    {
        return [
            'required' => false,
        ];
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            new FkField('swag_cms_extensions_form_group_id', 'groupId', FormGroupDefinition::class),
            (new IntField('position', 'position'))->addFlags(new Required()),
            (new IntField('width', 'width', 1, 12))->addFlags(new Required()),
            (new StringField('type', 'type'))->addFlags(new Required()),
            (new StringField('technical_name', 'technicalName'))->addFlags(new Required()),
            (new BoolField('required', 'required'))->addFlags(new Required()),
            new TranslatedField('label'),
            new TranslatedField('placeholder'),
            new TranslatedField('errorMessage'),
            new TranslatedField('config'),
            new TranslationsAssociationField(FormGroupFieldTranslationDefinition::class, 'swag_cms_extensions_form_group_field_id'),
            new ManyToOneAssociationField('group', 'swag_cms_extensions_form_group_id', FormGroupDefinition::class),
        ]);
    }
}
