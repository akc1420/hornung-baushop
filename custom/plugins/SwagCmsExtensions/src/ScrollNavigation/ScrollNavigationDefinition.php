<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\ScrollNavigation;

use Shopware\Core\Content\Cms\Aggregate\CmsSection\CmsSectionDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Swag\CmsExtensions\ScrollNavigation\Aggregate\ScrollNavigationTranslation\ScrollNavigationTranslationDefinition;

class ScrollNavigationDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'swag_cms_extensions_scroll_navigation';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    /**
     * @return array<string, bool>
     */
    public function getDefaults(): array
    {
        return [
            'active' => false,
        ];
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required(), new ApiAware()),
            (new BoolField('active', 'active'))->addFlags(new ApiAware()),
            (new ReferenceVersionField(CmsSectionDefinition::class))->addFlags(new Required(), new ApiAware()),
            (new TranslatedField('displayName'))->addFlags(new ApiAware()),

            (new TranslationsAssociationField(
                ScrollNavigationTranslationDefinition::class,
                self::ENTITY_NAME . '_id'
            ))->addFlags(new Required(), new ApiAware()),
            (new FkField(
                'cms_section_id',
                'cmsSectionId',
                CmsSectionDefinition::class
            ))->addFlags(new ApiAware()),
            (new OneToOneAssociationField(
                'cmsSection',
                'cms_section_id',
                'id',
                CmsSectionDefinition::class
            ))->addFlags(new ApiAware()),
        ]);
    }
}
