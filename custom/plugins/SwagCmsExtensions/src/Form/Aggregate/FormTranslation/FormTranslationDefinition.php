<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Form\Aggregate\FormTranslation;

use Shopware\Core\Framework\DataAbstractionLayer\EntityTranslationDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Swag\CmsExtensions\Form\FormDefinition;

class FormTranslationDefinition extends EntityTranslationDefinition
{
    public const ENTITY_NAME = 'swag_cms_extensions_form_translation';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return FormTranslationEntity::class;
    }

    public function getCollectionClass(): string
    {
        return FormTranslationCollection::class;
    }

    protected function getParentDefinitionClass(): string
    {
        return FormDefinition::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            new StringField('title', 'title'),
            new LongTextField('success_message', 'successMessage'),
            new JsonField('receivers', 'receivers'),
        ]);
    }
}
