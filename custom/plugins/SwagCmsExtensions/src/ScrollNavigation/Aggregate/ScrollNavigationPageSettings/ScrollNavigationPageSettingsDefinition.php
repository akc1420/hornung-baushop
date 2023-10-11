<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\ScrollNavigation\Aggregate\ScrollNavigationPageSettings;

use Shopware\Core\Content\Cms\CmsPageDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class ScrollNavigationPageSettingsDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'swag_cms_extensions_scroll_navigation_page_settings';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    /**
     * @return array<string, string|int|bool>
     */
    public function getDefaults(): array
    {
        return [
            'active' => false,
            'duration' => 1000,
            'easing' => 'inOut',
            'bouncy' => false,
            'easingDegree' => 3,
        ];
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required(), new ApiAware()),
            (new BoolField('active', 'active'))->addFlags(new Required(), new ApiAware()),
            (new IntField('duration', 'duration'))->addFlags(new Required(), new ApiAware()),
            (new StringField('easing', 'easing'))->addFlags(new Required(), new ApiAware()),
            (new BoolField('bouncy', 'bouncy'))->addFlags(new Required(), new ApiAware()),
            (new IntField('easing_degree', 'easingDegree'))->addFlags(new Required(), new ApiAware()),

            (new FkField(
                'cms_page_id',
                'cmsPageId',
                CmsPageDefinition::class
            ))->addFlags(new ApiAware()),
            (new ReferenceVersionField(CmsPageDefinition::class))->addFlags(new ApiAware()),
            (new OneToOneAssociationField(
                'cmsPage',
                'cms_page_id',
                'id',
                CmsPageDefinition::class
            ))->addFlags(new ApiAware()),
        ]);
    }
}
