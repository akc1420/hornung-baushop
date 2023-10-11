<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Extension\Feature\Quickview;

use Shopware\Core\Content\Cms\Aggregate\CmsBlock\CmsBlockDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Swag\CmsExtensions\Quickview\QuickviewDefinition;

class CmsBlockEntityExtension extends EntityExtension
{
    public const QUICKVIEW_ASSOCIATION_PROPERTY_NAME = 'swagCmsExtensionsQuickview';

    public function getDefinitionClass(): string
    {
        return CmsBlockDefinition::class;
    }

    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            (new OneToOneAssociationField(
                self::QUICKVIEW_ASSOCIATION_PROPERTY_NAME,
                'id',
                QuickviewDefinition::CMS_BLOCK_FOREIGN_KEY_STORAGE_NAME,
                QuickviewDefinition::class,
                false
            ))->addFlags(new CascadeDelete(), new ApiAware())
        );
    }
}
