<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\SocialShopping\Test\DataAbstractionLayer;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelType\SalesChannelTypeCollection;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelType\SalesChannelTypeEntity;
use Swag\SocialShopping\Test\Mock\SalesChannelRepoMock;
use Swag\SocialShopping\Test\Mock\SalesChannelTypeRepoMock;
use SwagSocialShopping\Component\Network\Facebook;
use SwagSocialShopping\Component\Network\NetworkRegistry;
use SwagSocialShopping\DataAbstractionLayer\SalesChannelTypeRepositoryDecorator;

class SalesChannelTypeRepositoryDecoratorTest extends TestCase
{
    public function testSearch(): void
    {
        $facebookNetwork = new Facebook();
        $innerRepo = new SalesChannelTypeRepoMock();
        $networkRegistry = new NetworkRegistry([$facebookNetwork]);
        $salesChannelRepo = new SalesChannelRepoMock();
        $decorator = new SalesChannelTypeRepositoryDecorator($innerRepo, $networkRegistry, $salesChannelRepo);

        $criteria = new Criteria();
        $context = Context::createDefaultContext();
        $result = $decorator->search($criteria, $context);

        static::assertSame(1, $result->getTotal());

        /** @var SalesChannelTypeCollection $salesChannelTypes */
        $salesChannelTypes = $result->getEntities();

        static::assertCount(1, $salesChannelTypes);

        $salesChannelTypes = $salesChannelTypes->filter(function (SalesChannelTypeEntity $salesChannelType) use ($facebookNetwork) {
            return $salesChannelType->getName() === \ucfirst($facebookNetwork->getName());
        });

        $facebookSalesChannelType = $salesChannelTypes->first();
        static::assertNotNull($facebookSalesChannelType);

        $customFields = $facebookSalesChannelType->getCustomFields();
        static::assertNotNull($customFields);
        static::assertTrue($customFields['isSocialShoppingType']);

        $translationKeys = $facebookSalesChannelType->getTranslated();
        $translationKeyName = 'name';
        $translationKeyDescription = 'description';
        $translationKeyManufacturer = 'manufacturer';
        $translationKeyDescriptionLong = 'descriptionLong';
        static::assertSame($facebookNetwork->getTranslationKey() . '.' . $translationKeyName, $translationKeys[$translationKeyName]);
        static::assertSame($facebookNetwork->getTranslationKey() . '.' . $translationKeyDescription, $translationKeys[$translationKeyDescription]);
        static::assertSame($facebookNetwork->getTranslationKey() . '.' . $translationKeyManufacturer, $translationKeys[$translationKeyManufacturer]);
        static::assertSame($facebookNetwork->getTranslationKey() . '.' . $translationKeyDescriptionLong, $translationKeys[$translationKeyDescriptionLong]);
    }
}
