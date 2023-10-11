<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Form;

use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotDefinition;
use Shopware\Core\Content\MailTemplate\MailTemplateDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\SearchRanking;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\SetNullOnDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Swag\CmsExtensions\Form\Aggregate\FormGroup\FormGroupDefinition;
use Swag\CmsExtensions\Form\Aggregate\FormTranslation\FormTranslationDefinition;

class FormDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'swag_cms_extensions_form';
    public const CMS_SLOT_FOREIGN_KEY_STORAGE_NAME = 'cms_slot_id';
    public const MAIL_TEMPLATE_FOREIGN_KEY_STORAGE_NAME = 'mail_template_id';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return FormCollection::class;
    }

    public function getEntityClass(): string
    {
        return FormEntity::class;
    }

    /**
     * @return array<string, bool>
     */
    public function getDefaults(): array
    {
        return [
            'isTemplate' => false,
        ];
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            new FkField(self::CMS_SLOT_FOREIGN_KEY_STORAGE_NAME, 'cmsSlotId', CmsSlotDefinition::class),
            (new ReferenceVersionField(CmsSlotDefinition::class))->addFlags(new Required()),
            (new BoolField('is_template', 'isTemplate'))->addFlags(new Required()),
            (new StringField('technical_name', 'technicalName'))->addFlags(new Required(), new SearchRanking(SearchRanking::HIGH_SEARCH_RANKING)),
            new TranslatedField('title'),
            new TranslatedField('successMessage'),
            new TranslatedField('receivers'),
            (new FkField(self::MAIL_TEMPLATE_FOREIGN_KEY_STORAGE_NAME, 'mailTemplateId', MailTemplateDefinition::class))->addFlags(new Required(), new SetNullOnDelete()),

            (new OneToManyAssociationField('groups', FormGroupDefinition::class, 'swag_cms_extensions_form_id'))->addFlags(new CascadeDelete()),
            new OneToOneAssociationField('cmsSlot', self::CMS_SLOT_FOREIGN_KEY_STORAGE_NAME, 'id', CmsSlotDefinition::class, false),
            new ManyToOneAssociationField('mailTemplate', self::MAIL_TEMPLATE_FOREIGN_KEY_STORAGE_NAME, MailTemplateDefinition::class, 'id', false),
            new TranslationsAssociationField(FormTranslationDefinition::class, 'swag_cms_extensions_form_id'),
        ]);
    }
}
