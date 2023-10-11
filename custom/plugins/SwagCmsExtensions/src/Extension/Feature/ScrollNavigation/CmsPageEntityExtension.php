<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Extension\Feature\ScrollNavigation;

use Shopware\Core\Content\Cms\CmsPageDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Swag\CmsExtensions\ScrollNavigation\Aggregate\ScrollNavigationPageSettings\ScrollNavigationPageSettingsDefinition;

class CmsPageEntityExtension extends EntityExtension
{
    public const SCROLL_NAVIGATION_PAGE_SETTINGS_PROPERTY_NAME = 'swagCmsExtensionsScrollNavigationPageSettings';

    public function getDefinitionClass(): string
    {
        return CmsPageDefinition::class;
    }

    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            (new OneToOneAssociationField(
                self::SCROLL_NAVIGATION_PAGE_SETTINGS_PROPERTY_NAME,
                'id',
                'id',
                ScrollNavigationPageSettingsDefinition::class,
                false
            ))->addFlags(new ApiAware())
        );
    }
}
