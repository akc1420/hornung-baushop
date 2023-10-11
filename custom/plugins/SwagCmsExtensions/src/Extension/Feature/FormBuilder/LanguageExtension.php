<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Extension\Feature\FormBuilder;

use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\Language\LanguageDefinition;
use Swag\CmsExtensions\Form\Aggregate\FormGroupFieldTranslation\FormGroupFieldTranslationDefinition;
use Swag\CmsExtensions\Form\Aggregate\FormGroupTranslation\FormGroupTranslationDefinition;
use Swag\CmsExtensions\Form\Aggregate\FormTranslation\FormTranslationDefinition;

class LanguageExtension extends EntityExtension
{
    public function getDefinitionClass(): string
    {
        return LanguageDefinition::class;
    }

    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            new OneToManyAssociationField(
                'cmsExtensionsFormTranslations',
                FormTranslationDefinition::class,
                'language_id'
            )
        );
        $collection->add(
            new OneToManyAssociationField(
                'cmsExtensionsFormGroupTranslations',
                FormGroupTranslationDefinition::class,
                'language_id'
            )
        );
        $collection->add(
            new OneToManyAssociationField(
                'cmsExtensionsFormGroupFieldTranslations',
                FormGroupFieldTranslationDefinition::class,
                'language_id'
            )
        );
    }
}
