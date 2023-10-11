<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Form\Aggregate\FormGroupTranslation;

use Shopware\Core\Framework\DataAbstractionLayer\EntityTranslationDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Swag\CmsExtensions\Form\Aggregate\FormGroup\FormGroupDefinition;

class FormGroupTranslationDefinition extends EntityTranslationDefinition
{
    public const ENTITY_NAME = 'swag_cms_extensions_form_group_translation';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return FormGroupTranslationCollection::class;
    }

    public function getEntityClass(): string
    {
        return FormGroupTranslationEntity::class;
    }

    protected function getParentDefinitionClass(): string
    {
        return FormGroupDefinition::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            new StringField('title', 'title'),
        ]);
    }
}
