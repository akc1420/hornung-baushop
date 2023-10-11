<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Form\Aggregate\FormGroup;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Swag\CmsExtensions\Form\Aggregate\FormGroupField\FormGroupFieldDefinition;
use Swag\CmsExtensions\Form\Aggregate\FormGroupTranslation\FormGroupTranslationDefinition;
use Swag\CmsExtensions\Form\FormDefinition;

class FormGroupDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'swag_cms_extensions_form_group';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return FormGroupEntity::class;
    }

    public function getCollectionClass(): string
    {
        return FormGroupCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            new FkField('swag_cms_extensions_form_id', 'formId', FormDefinition::class),
            new TranslatedField('title'),
            (new StringField('technical_name', 'technicalName'))->addFlags(new Required()),
            (new IntField('position', 'position'))->addFlags(new Required()),
            (new OneToManyAssociationField('fields', FormGroupFieldDefinition::class, 'swag_cms_extensions_form_group_id'))->addFlags(new CascadeDelete()),
            new ManyToOneAssociationField('form', 'swag_cms_extensions_form_id', FormDefinition::class),
            new TranslationsAssociationField(FormGroupTranslationDefinition::class, 'swag_cms_extensions_form_group_id'),
        ]);
    }
}
