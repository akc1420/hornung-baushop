<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Quickview;

use Shopware\Core\Content\Cms\Aggregate\CmsBlock\CmsBlockDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\RestrictDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class QuickviewDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'swag_cms_extensions_quickview';
    public const CMS_BLOCK_FOREIGN_KEY_STORAGE_NAME = CmsBlockDefinition::ENTITY_NAME . '_id';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required(), new ApiAware()),
            (new BoolField('active', 'active'))->addFlags(new ApiAware()),

            (new FkField(
                self::CMS_BLOCK_FOREIGN_KEY_STORAGE_NAME,
                'cmsBlockId',
                CmsBlockDefinition::class
            ))->addFlags(new ApiAware()),
            (new ReferenceVersionField(CmsBlockDefinition::class))->addFlags(new Required(), new ApiAware()),
            (new OneToOneAssociationField(
                'cmsBlock',
                self::CMS_BLOCK_FOREIGN_KEY_STORAGE_NAME,
                'id',
                CmsBlockDefinition::class,
                false
            ))->addFlags(new RestrictDelete(), new ApiAware()),
        ]);
    }
}
