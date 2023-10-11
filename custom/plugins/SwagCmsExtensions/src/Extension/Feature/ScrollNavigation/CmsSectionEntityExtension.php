<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Extension\Feature\ScrollNavigation;

use Shopware\Core\Content\Cms\Aggregate\CmsSection\CmsSectionDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Swag\CmsExtensions\ScrollNavigation\ScrollNavigationDefinition;

class CmsSectionEntityExtension extends EntityExtension
{
    public const SCROLL_NAVIGATION_ASSOCIATION_PROPERTY_NAME = 'swagCmsExtensionsScrollNavigation';

    public function getDefinitionClass(): string
    {
        return CmsSectionDefinition::class;
    }

    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            (new OneToOneAssociationField(
                self::SCROLL_NAVIGATION_ASSOCIATION_PROPERTY_NAME,
                'id',
                'id',
                ScrollNavigationDefinition::class,
                false
            ))->addFlags(new ApiAware())
        );
    }
}
