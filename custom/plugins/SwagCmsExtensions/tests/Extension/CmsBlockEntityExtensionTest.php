<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Test\Extension;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Cms\Aggregate\CmsBlock\CmsBlockDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Swag\CmsExtensions\BlockRule\BlockRuleDefinition;
use Swag\CmsExtensions\Extension\Feature\BlockRule\CmsBlockEntityExtension as BlockRuleCmsBlockEntityExtension;
use Swag\CmsExtensions\Extension\Feature\Quickview\CmsBlockEntityExtension as QuickviewCmsBlockEntityExtension;
use Swag\CmsExtensions\Quickview\QuickviewDefinition;

class CmsBlockEntityExtensionTest extends TestCase
{
    public function testQuickviewExtendFieldsAddsOneToOneAssociationField(): void
    {
        $collection = $this->getMockBuilder(FieldCollection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['add'])
            ->getMock();

        $collection
            ->expects(static::atLeastOnce())
            ->method('add')
            ->withConsecutive(
                [
                    (new OneToOneAssociationField(
                        QuickviewCmsBlockEntityExtension::QUICKVIEW_ASSOCIATION_PROPERTY_NAME,
                        'id',
                        QuickviewDefinition::CMS_BLOCK_FOREIGN_KEY_STORAGE_NAME,
                        QuickviewDefinition::class,
                        false
                    ))->addFlags(new CascadeDelete(), new ApiAware()),
                ]
            );

        (new QuickviewCmsBlockEntityExtension())->extendFields($collection);
    }

    public function testBlockRuleExtendFieldsAddsOneToOneAssociationField(): void
    {
        $collection = $this->getMockBuilder(FieldCollection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['add'])
            ->getMock();

        $collection
            ->expects(static::atLeastOnce())
            ->method('add')
            ->withConsecutive(
                [
                    (new OneToOneAssociationField(
                        BlockRuleCmsBlockEntityExtension::BLOCK_RULE_ASSOCIATION_PROPERTY_NAME,
                        'id',
                        BlockRuleDefinition::CMS_BLOCK_FOREIGN_KEY_STORAGE_NAME,
                        BlockRuleDefinition::class,
                        false
                    ))->addFlags(new CascadeDelete(), new ApiAware()),
                ]
            );

        (new BlockRuleCmsBlockEntityExtension())->extendFields($collection);
    }

    public function testGetDefinitionClassReturnsCmsBlockDefinitionClass(): void
    {
        static::assertSame(
            CmsBlockDefinition::class,
            (new QuickviewCmsBlockEntityExtension())->getDefinitionClass()
        );

        static::assertSame(
            CmsBlockDefinition::class,
            (new BlockRuleCmsBlockEntityExtension())->getDefinitionClass()
        );
    }
}
