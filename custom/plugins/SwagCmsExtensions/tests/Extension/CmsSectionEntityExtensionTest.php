<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Test\Extension;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Cms\Aggregate\CmsSection\CmsSectionDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Swag\CmsExtensions\Extension\Feature\ScrollNavigation\CmsSectionEntityExtension;
use Swag\CmsExtensions\ScrollNavigation\ScrollNavigationDefinition;

class CmsSectionEntityExtensionTest extends TestCase
{
    public function testExtendFieldsAddsOneToOneAssociationField(): void
    {
        $collection = $this->getMockBuilder(FieldCollection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['add'])
            ->getMock();

        $collection
            ->expects(static::once())
            ->method('add')
            ->with(
                (new OneToOneAssociationField(
                    CmsSectionEntityExtension::SCROLL_NAVIGATION_ASSOCIATION_PROPERTY_NAME,
                    'id',
                    'id',
                    ScrollNavigationDefinition::class,
                    false
                ))->addFlags(new ApiAware())
            );

        (new CmsSectionEntityExtension())->extendFields($collection);
    }

    public function testGetDefinitionClassReturnsCmsSectionDefinitionClass(): void
    {
        static::assertSame(
            CmsSectionDefinition::class,
            (new CmsSectionEntityExtension())->getDefinitionClass()
        );
    }
}
