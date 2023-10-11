<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\SocialShopping\Test\Installer;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Api\Util\AccessKeyHelper;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Migration\MigrationCollection;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Test\TestCaseBase\BasicTestDataBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Test\TestDefaults;
use SwagSocialShopping\Exception\ExistingSocialShoppingSalesChannelsException;
use SwagSocialShopping\Installer\SalesChannelInstaller;
use SwagSocialShopping\SwagSocialShopping;

class InstallerTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;
    use BasicTestDataBehaviour;

    public function testDeactivateWithSalesChannels(): void
    {
        $context = Context::createDefaultContext();

        $this->createSalesChannel($context);

        $installer = new SalesChannelInstaller($this->getContainer());

        $this->expectException(ExistingSocialShoppingSalesChannelsException::class);
        $this->expectExceptionMessage('There is still 1 Social Shopping sales channel left. [Test Sales Channel]');

        $installer->deactivate(
            new DeactivateContext(
                new SwagSocialShopping(true, ''),
                $context,
                '',
                '',
                $this->createMock(MigrationCollection::class)
            )
        );
    }

    public function testDeactivateWithTwoSalesChannels(): void
    {
        $context = Context::createDefaultContext();

        $this->createSalesChannel($context);
        $this->createSalesChannel($context, 'Test Sales Channel 2');

        $installer = new SalesChannelInstaller($this->getContainer());

        $this->expectException(ExistingSocialShoppingSalesChannelsException::class);
        $this->expectExceptionMessageMatches(
            '/There are still 2 Social Shopping sales channels left. \[( ?Test Sales Channel ?[0-9]?,?)+\]/'
        );

        $installer->deactivate(
            new DeactivateContext(
                new SwagSocialShopping(true, ''),
                $context,
                '',
                '',
                $this->createMock(MigrationCollection::class)
            )
        );
    }

    public function testDeactivateWithoutSalesChannels(): void
    {
        $context = Context::createDefaultContext();

        $installer = new SalesChannelInstaller($this->getContainer());

        $installer->deactivate(
            new DeactivateContext(
                new SwagSocialShopping(true, ''),
                $context,
                '',
                '',
                $this->createMock(MigrationCollection::class)
            )
        );
    }

    public function testUninstallWithSalesChannels(): void
    {
        $context = Context::createDefaultContext();

        $this->createSalesChannel($context);

        $installer = new SalesChannelInstaller($this->getContainer());

        $this->expectException(ExistingSocialShoppingSalesChannelsException::class);
        $this->expectExceptionMessage('There is still 1 Social Shopping sales channel left.');

        $installer->uninstall(
            new UninstallContext(
                new SwagSocialShopping(true, ''),
                $context,
                '',
                '',
                $this->createMock(MigrationCollection::class),
                false
            )
        );
    }

    public function testUninstallWithTwoSalesChannels(): void
    {
        $context = Context::createDefaultContext();

        $this->createSalesChannel($context);
        $this->createSalesChannel($context, 'Test Sales Channel 2');

        $installer = new SalesChannelInstaller($this->getContainer());

        $this->expectException(ExistingSocialShoppingSalesChannelsException::class);
        $this->expectExceptionMessageMatches(
            '/There are still 2 Social Shopping sales channels left. \[( ?Test Sales Channel ?[0-9]?,?)+\]/'
        );

        $installer->uninstall(
            new UninstallContext(
                new SwagSocialShopping(true, ''),
                $context,
                '',
                '',
                $this->createMock(MigrationCollection::class),
                false
            )
        );
    }

    private function createSalesChannel(Context $context, string $name = 'Test Sales Channel'): void
    {
        $salesChannelRepository = $this->getContainer()->get('sales_channel.repository');

        static::assertNotNull($salesChannelRepository);

        $customerGroupId = $salesChannelRepository->search(new Criteria([TestDefaults::SALES_CHANNEL]), Context::createDefaultContext())->first()->get('customerGroupId');

        $salesChannelRepository->create([[
            'name' => $name,
            'typeId' => SwagSocialShopping::SALES_CHANNEL_TYPE_SOCIAL_SHOPPING,
            'accessKey' => AccessKeyHelper::generateAccessKey('sales-channel'),
            'languageId' => Defaults::LANGUAGE_SYSTEM,
            'snippetSetId' => $this->getSnippetSetIdForLocale('en-GB'),
            'currencyId' => Defaults::CURRENCY,
            'currencyVersionId' => Defaults::LIVE_VERSION,
            'paymentMethodId' => $this->getValidPaymentMethodId(),
            'paymentMethodVersionId' => Defaults::LIVE_VERSION,
            'shippingMethodId' => $this->getValidShippingMethodId(),
            'shippingMethodVersionId' => Defaults::LIVE_VERSION,
            'navigationCategoryId' => $this->getValidCategoryId(),
            'navigationCategoryVersionId' => Defaults::LIVE_VERSION,
            'countryId' => $this->getValidCountryId(),
            'countryVersionId' => Defaults::LIVE_VERSION,
            'currencies' => [['id' => Defaults::CURRENCY]],
            'languages' => [['id' => Defaults::LANGUAGE_SYSTEM]],
            'paymentMethods' => [['id' => $this->getValidPaymentMethodId()]],
            'shippingMethods' => [['id' => $this->getValidShippingMethodId()]],
            'countries' => [['id' => $this->getValidCountryId()]],
            'customerGroupId' => $customerGroupId,
        ],
        ], $context);
    }
}
