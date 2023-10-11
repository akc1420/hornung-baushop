<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\SocialShopping\Test\EventListener;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Shopware\Core\Content\ProductExport\ProductExportEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Test\TestCaseBase\SalesChannelFunctionalTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagSocialShopping\Component\Network\Facebook;
use SwagSocialShopping\Component\Network\GoogleShopping;
use SwagSocialShopping\SwagSocialShopping;

class DataFeedEventListenerTest extends TestCase
{
    use SalesChannelFunctionalTestBehaviour;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var string
     */
    protected $salesChannelDomainId;

    /**
     * @var string
     */
    protected $salesChannelId;

    /**
     * @var EntityRepositoryInterface
     */
    protected $productExportRepository;

    protected function setUp(): void
    {
        $this->context = Context::createDefaultContext();
        /** @var EntityRepositoryInterface $productExportRepository */
        $productExportRepository = $this->getContainer()->get('product_export.repository');
        $this->productExportRepository = $productExportRepository;
        $this->salesChannelDomainId = Uuid::randomHex();
    }

    public function testFacebookDataFeedWritten(): void
    {
        $storefrontSalesChannel = $this->createSalesChannel(
            [
                'typeId' => Defaults::SALES_CHANNEL_TYPE_STOREFRONT,
                'domains' => [
                    [
                        'id' => $this->salesChannelDomainId,
                        'currencyId' => Defaults::CURRENCY,
                        'languageId' => Defaults::LANGUAGE_SYSTEM,
                        'snippetSetId' => $this->getSnippetSetIdForLocale('en-GB'),
                        'url' => 'http://test.foo',
                    ],
                ],
            ]
        );

        $this->salesChannelId = $storefrontSalesChannel['id'];

        $this->createProductStream();

        $salesChannel = $this->createSalesChannel(
            [
                'typeId' => SwagSocialShopping::SALES_CHANNEL_TYPE_SOCIAL_SHOPPING,
                'socialShoppingSalesChannel' => [
                    'id' => Uuid::randomHex(),
                    'isValidating' => false,
                    'productStreamId' => '137b079935714281ba80b40f83f8d7eb',
                    'currencyId' => Defaults::CURRENCY,
                    'salesChannelDomainId' => $this->salesChannelDomainId,
                    'network' => Facebook::class,
                    'configuration' => [
                        'interval' => 86400,
                        'generateByCronjob' => true,
                        'includeVariants' => true,
                        'defaultGoogleProductCategory' => '12345',
                    ],
                ],
            ]
        );

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('salesChannelId', $salesChannel['id']));

        /** @var ProductExportEntity|null $productExportEntity */
        $productExportEntity = $this->productExportRepository->search($criteria, $this->context)->first();

        static::assertInstanceOf(ProductExportEntity::class, $productExportEntity);
        static::assertEquals($this->salesChannelDomainId, $productExportEntity->getSalesChannelDomainId());
        static::assertEquals($this->salesChannelId, $productExportEntity->getStorefrontSalesChannelId());
        static::assertEquals(\sprintf('facebook_%s.xml', $salesChannel['id']), $productExportEntity->getFileName());
        static::assertTrue($productExportEntity->isGenerateByCronjob());
    }

    public function testGoogleDataFeedWritten(): void
    {
        $storefrontSalesChannel = $this->createSalesChannel(
            [
                'typeId' => Defaults::SALES_CHANNEL_TYPE_STOREFRONT,
                'domains' => [
                    [
                        'id' => $this->salesChannelDomainId,
                        'currencyId' => Defaults::CURRENCY,
                        'languageId' => Defaults::LANGUAGE_SYSTEM,
                        'snippetSetId' => $this->getSnippetSetIdForLocale('en-GB'),
                        'url' => 'http://test.foo',
                    ],
                ],
            ]
        );

        $this->salesChannelId = $storefrontSalesChannel['id'];

        $this->createProductStream();

        $salesChannel = $this->createSalesChannel(
            [
                'typeId' => SwagSocialShopping::SALES_CHANNEL_TYPE_SOCIAL_SHOPPING,
                'socialShoppingSalesChannel' => [
                    'id' => Uuid::randomHex(),
                    'isValidating' => false,
                    'productStreamId' => '137b079935714281ba80b40f83f8d7eb',
                    'currencyId' => Defaults::CURRENCY,
                    'salesChannelDomainId' => $this->salesChannelDomainId,
                    'network' => GoogleShopping::class,
                    'configuration' => [
                        'interval' => 86400,
                        'generateByCronjob' => false,
                        'includeVariants' => true,
                        'defaultGoogleProductCategory' => '12345',
                    ],
                ],
            ]
        );

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('salesChannelId', $salesChannel['id']));

        /** @var ProductExportEntity|null $productExportEntity */
        $productExportEntity = $this->productExportRepository->search($criteria, $this->context)->first();

        static::assertInstanceOf(ProductExportEntity::class, $productExportEntity);
        static::assertEquals($this->salesChannelDomainId, $productExportEntity->getSalesChannelDomainId());
        static::assertEquals($this->salesChannelId, $productExportEntity->getStorefrontSalesChannelId());
        static::assertEquals(\sprintf('google-shopping_%s.xml', $salesChannel['id']), $productExportEntity->getFileName());
        static::assertFalse($productExportEntity->isGenerateByCronjob());
    }

    public function testInactive(): void
    {
        $storefrontSalesChannel = $this->createSalesChannel(
            [
                'typeId' => Defaults::SALES_CHANNEL_TYPE_STOREFRONT,
                'domains' => [
                    [
                        'id' => $this->salesChannelDomainId,
                        'currencyId' => Defaults::CURRENCY,
                        'languageId' => Defaults::LANGUAGE_SYSTEM,
                        'snippetSetId' => $this->getSnippetSetIdForLocale('en-GB'),
                        'url' => 'http://test.foo',
                    ],
                ],
            ]
        );

        $this->salesChannelId = $storefrontSalesChannel['id'];

        $this->createProductStream();

        $salesChannel = $this->createSalesChannel(
            [
                'active' => false,
                'typeId' => SwagSocialShopping::SALES_CHANNEL_TYPE_SOCIAL_SHOPPING,
                'socialShoppingSalesChannel' => [
                    'id' => Uuid::randomHex(),
                    'isValidating' => false,
                    'productStreamId' => '137b079935714281ba80b40f83f8d7eb',
                    'currencyId' => Defaults::CURRENCY,
                    'salesChannelDomainId' => $this->salesChannelDomainId,
                    'network' => Facebook::class,
                    'configuration' => [
                        'interval' => 86400,
                        'generateByCronjob' => true,
                        'includeVariants' => true,
                        'defaultGoogleProductCategory' => '12345',
                    ],
                ],
            ]
        );

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('salesChannelId', $salesChannel['id']));

        /** @var ProductExportEntity|null $productExportEntity */
        $productExportEntity = $this->productExportRepository->search($criteria, $this->context)->first();

        static::assertInstanceOf(ProductExportEntity::class, $productExportEntity);
        static::assertFalse($productExportEntity->isGenerateByCronjob());

        /** @var EntityRepositoryInterface $salesChannelRepository */
        $salesChannelRepository = $this->getContainer()->get('sales_channel.repository');

        $salesChannelRepository->update([['id' => $salesChannel['id'], 'active' => true]], $this->context);

        /** @var ProductExportEntity|null $productExportEntity */
        $productExportEntity = $this->productExportRepository->search($criteria, $this->context)->first();

        static::assertInstanceOf(ProductExportEntity::class, $productExportEntity);
        static::assertTrue($productExportEntity->isGenerateByCronjob());
    }

    private function createProductStream(): void
    {
        /** @var Connection $connection */
        $connection = $this->getContainer()->get(Connection::class);

        $randomProductIds = \implode('|', \array_slice(\array_column($this->createProducts(), 'id'), 0, 2));

        $connection->executeStatement("
            INSERT INTO `product_stream` (`id`, `api_filter`, `invalid`, `created_at`, `updated_at`)
            VALUES
                (UNHEX('137B079935714281BA80B40F83F8D7EB'), '[{\"type\": \"multi\", \"queries\": [{\"type\": \"multi\", \"queries\": [{\"type\": \"equalsAny\", \"field\": \"product.id\", \"value\": \"{$randomProductIds}\"}], \"operator\": \"AND\"}, {\"type\": \"multi\", \"queries\": [{\"type\": \"range\", \"field\": \"product.width\", \"parameters\": {\"gte\": 221, \"lte\": 932}}], \"operator\": \"AND\"}, {\"type\": \"multi\", \"queries\": [{\"type\": \"range\", \"field\": \"product.width\", \"parameters\": {\"lte\": 245}}], \"operator\": \"AND\"}, {\"type\": \"multi\", \"queries\": [{\"type\": \"equals\", \"field\": \"product.manufacturer.id\", \"value\": \"02f6b9aa385d4f40aaf573661b2cf919\"}, {\"type\": \"range\", \"field\": \"product.height\", \"parameters\": {\"gte\": 182}}], \"operator\": \"AND\"}], \"operator\": \"OR\"}]', 0, '2019-08-16 08:43:57.488', NULL);
        ");

        $connection->executeStatement("
            INSERT INTO `product_stream_filter` (`id`, `product_stream_id`, `parent_id`, `type`, `field`, `operator`, `value`, `parameters`, `position`, `custom_fields`, `created_at`, `updated_at`)
            VALUES
                (UNHEX('DA6CD9776BC84463B25D5B6210DDB57B'), UNHEX('137B079935714281BA80B40F83F8D7EB'), NULL, 'multi', NULL, 'OR', NULL, NULL, 0, NULL, '2019-08-16 08:43:57.469', NULL),
                (UNHEX('0EE60B6A87774E9884A832D601BE6B8F'), UNHEX('137B079935714281BA80B40F83F8D7EB'), UNHEX('DA6CD9776BC84463B25D5B6210DDB57B'), 'multi', NULL, 'AND', NULL, NULL, 1, NULL, '2019-08-16 08:43:57.478', NULL),
                (UNHEX('272B4392E7B34EF2ABB4827A33630C1D'), UNHEX('137B079935714281BA80B40F83F8D7EB'), UNHEX('DA6CD9776BC84463B25D5B6210DDB57B'), 'multi', NULL, 'AND', NULL, NULL, 3, NULL, '2019-08-16 08:43:57.486', NULL),
                (UNHEX('4A7AEB36426A482A8BFFA049F795F5E7'), UNHEX('137B079935714281BA80B40F83F8D7EB'), UNHEX('DA6CD9776BC84463B25D5B6210DDB57B'), 'multi', NULL, 'AND', NULL, NULL, 0, NULL, '2019-08-16 08:43:57.470', NULL),
                (UNHEX('BB87D86524FB4E7EA01EE548DD43A5AC'), UNHEX('137B079935714281BA80B40F83F8D7EB'), UNHEX('DA6CD9776BC84463B25D5B6210DDB57B'), 'multi', NULL, 'AND', NULL, NULL, 2, NULL, '2019-08-16 08:43:57.483', NULL),
                (UNHEX('56C5DF0B41954334A7B0CDFEDFE1D7E9'), UNHEX('137B079935714281BA80B40F83F8D7EB'), UNHEX('272B4392E7B34EF2ABB4827A33630C1D'), 'range', 'width', NULL, NULL, '{\"lte\":932,\"gte\":221}', 1, NULL, '2019-08-16 08:43:57.488', NULL),
                (UNHEX('6382E03A768F444E9C2A809C63102BD4'), UNHEX('137B079935714281BA80B40F83F8D7EB'), UNHEX('BB87D86524FB4E7EA01EE548DD43A5AC'), 'range', 'height', NULL, NULL, '{\"gte\":182}', 2, NULL, '2019-08-16 08:43:57.485', NULL),
                (UNHEX('7CBC1236ABCD43CAA697E9600BF1DF6E'), UNHEX('137B079935714281BA80B40F83F8D7EB'), UNHEX('4A7AEB36426A482A8BFFA049F795F5E7'), 'range', 'width', NULL, NULL, '{\"lte\":245}', 1, NULL, '2019-08-16 08:43:57.476', NULL),
                (UNHEX('80B2B90171454467B769A4C161E74B87'), UNHEX('137B079935714281BA80B40F83F8D7EB'), UNHEX('0EE60B6A87774E9884A832D601BE6B8F'), 'equalsAny', 'id', NULL, '{$randomProductIds}', NULL, 1, NULL, '2019-08-16 08:43:57.480', NULL);
    ");
    }

    private function createProducts(): array
    {
        /** @var EntityRepositoryInterface $productRepository */
        $productRepository = $this->getContainer()->get('product.repository');
        $manufacturerId = Uuid::randomHex();
        $taxId = Uuid::randomHex();
        $salesChannelId = $this->salesChannelId;
        $products = [];

        for ($i = 0; $i < 10; ++$i) {
            $products[] = [
                'id' => Uuid::randomHex(),
                'productNumber' => Uuid::randomHex(),
                'stock' => 1,
                'name' => 'Test',
                'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 9, 'linked' => false]],
                'manufacturer' => ['id' => $manufacturerId, 'name' => 'test'],
                'tax' => ['id' => $taxId, 'taxRate' => 17, 'name' => 'with id'],
                'visibilities' => [
                    ['salesChannelId' => $salesChannelId, 'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL],
                ],
            ];
        }

        $productRepository->create($products, $this->context);

        return $products;
    }
}
