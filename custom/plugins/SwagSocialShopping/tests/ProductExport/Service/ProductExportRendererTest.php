<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\SocialShopping\Test\ProductExport\Service;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Test\Cart\Common\Generator;
use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Shopware\Core\Content\ProductExport\ProductExportEntity;
use Shopware\Core\Content\Test\Media\MediaFixtures;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepositoryInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\Tax\TaxCollection;
use Shopware\Core\Test\TestDefaults;
use Swag\SocialShopping\Test\Helper\ServicesTrait;
use SwagSocialShopping\Component\Network\Facebook;
use SwagSocialShopping\Component\Network\GoogleShopping;
use SwagSocialShopping\ProductExport\Service\ProductExportRenderer;
use SwagSocialShopping\SwagSocialShopping;

class ProductExportRendererTest extends TestCase
{
    use ServicesTrait;
    use MediaFixtures;

    private ProductExportRenderer $productExportRenderer;

    private EntityRepositoryInterface $productExportRepository;

    private EntityRepositoryInterface $productRepository;

    private EntityRepositoryInterface $currencyRepository;

    private EntityRepositoryInterface $taxRepository;

    private EntityRepositoryInterface $productMediaRepository;

    private EntityRepositoryInterface $salesChannelRepository;

    private SalesChannelRepositoryInterface $salesChannelProductRepository;

    private SalesChannelContext $salesChannelContext;

    private Context $context;

    private string $TEST_PRODUCT_NAME = 'example-product';

    protected function setUp(): void
    {
        /** @var ProductExportRenderer $productExportRenderer */
        $productExportRenderer = $this->getContainer()->get(ProductExportRenderer::class);
        $this->productExportRenderer = $productExportRenderer;

        /** @var EntityRepositoryInterface $productExportRepository */
        $productExportRepository = $this->getContainer()->get('product_export.repository');
        $this->productExportRepository = $productExportRepository;

        /** @var EntityRepositoryInterface $productRepository */
        $productRepository = $this->getContainer()->get('product.repository');
        $this->productRepository = $productRepository;

        /** @var EntityRepositoryInterface $currencyRepository */
        $currencyRepository = $this->getContainer()->get('currency.repository');
        $this->currencyRepository = $currencyRepository;

        /** @var EntityRepositoryInterface $taxRepository */
        $taxRepository = $this->getContainer()->get('tax.repository');
        $this->taxRepository = $taxRepository;

        /** @var EntityRepositoryInterface $salesChannelRepository */
        $salesChannelRepository = $this->getContainer()->get('sales_channel.repository');
        $this->salesChannelRepository = $salesChannelRepository;

        /** @var SalesChannelRepositoryInterface $salesChannelProductRepository */
        $salesChannelProductRepository = $this->getContainer()->get('sales_channel.product.repository');
        $this->salesChannelProductRepository = $salesChannelProductRepository;

        /** @var EntityRepositoryInterface $productMediaRepository */
        $productMediaRepository = $this->getContainer()->get('product_media.repository');
        $this->productMediaRepository = $productMediaRepository;

        $this->context = Context::createDefaultContext();
    }

    public function testFacebookProductExportIncludesSalePrice(): void
    {
        $productExport = $this->getProductExport(Facebook::class);

        $productCriteria = (new Criteria())
            ->addAssociation('cover')
            ->addAssociation('media')
            ->addFilter(new EqualsFilter('name', $this->TEST_PRODUCT_NAME));

        $product = $this->salesChannelProductRepository->search($productCriteria, $this->salesChannelContext)->first();
        $content = $this->productExportRenderer->renderBody(
            $productExport,
            $this->salesChannelContext,
            [
                'product' => $product,
                'context' => $this->salesChannelContext,
            ]
        );

        static::assertStringContainsString('</g:sale_price>', $content);
    }

    public function testGoogleProductExportIncludesSalePrice(): void
    {
        $productExport = $this->getProductExport(GoogleShopping::class);

        $productCriteria = (new Criteria())
            ->addAssociation('cover')
            ->addAssociation('media')
            ->addFilter(new EqualsFilter('name', $this->TEST_PRODUCT_NAME));

        $product = $this->salesChannelProductRepository->search($productCriteria, $this->salesChannelContext)->first();
        $content = $this->productExportRenderer->renderBody(
            $productExport,
            $this->salesChannelContext,
            [
                'product' => $product,
                'context' => $this->salesChannelContext,
            ]
        );

        static::assertStringContainsString('</g:sale_price>', $content);
    }

    private function getProductExport(string $networkInterface): ProductExportEntity
    {
        $salesChannelId = Uuid::randomHex();
        $this->createSalesChannel($salesChannelId, [
            'typeId' => SwagSocialShopping::SALES_CHANNEL_TYPE_SOCIAL_SHOPPING,
            'socialShoppingSalesChannel' => [
                'id' => Uuid::randomHex(),
                'salesChannelId' => TestDefaults::SALES_CHANNEL,
                'salesChannelDomain' => [
                    'url' => 'http://example.com',
                    'salesChannelId' => TestDefaults::SALES_CHANNEL,
                    'languageId' => Defaults::LANGUAGE_SYSTEM,
                    'currencyId' => Defaults::CURRENCY,
                    'snippetSetId' => $this->getValidSnippetSetId(),
                ],
                'currencyId' => $this->context->getCurrencyId(),
                'network' => $networkInterface,
                'configuration' => [
                    'interval' => 1800,
                    'includeVariants' => true,
                    'generateByCronjob' => true,
                    'defaultGoogleProductCategory' => 4,
                ],
                'isValidating' => true,
                'productStream' => [
                    'name' => 'test-product-stream',
                    'filters' => [
                        [
                            'type' => 'equals',
                            'value' => $this->TEST_PRODUCT_NAME,
                            'field' => 'product.name',
                        ],
                    ],
                ],
            ],
        ]);

        $criteria = new Criteria();
        $criteria->addAssociation('rules');

        $salesChannel = $this->salesChannelRepository->search(new Criteria([$salesChannelId]), $this->context)->first();
        $currency = $this->currencyRepository->search(new Criteria([$this->context->getCurrencyId()]), Context::createDefaultContext())->first();
        $tax = $this->taxRepository->search($criteria, $this->context)->first();
        $this->salesChannelContext = Generator::createSalesChannelContext(
            $this->context,
            null,
            null,
            $salesChannel,
            $currency,
            new TaxCollection([$tax])
        );

        $productId = Uuid::randomHex();

        $this->createProduct($productId, $tax->getId(), [
            'name' => $this->TEST_PRODUCT_NAME,
            'price' => [
                [
                    'currencyId' => Defaults::CURRENCY,
                    'gross' => 10, 'net' => 9,
                    'linked' => true,
                    'listPrice' => [
                        'gross' => 20,
                        'net' => 15,
                        'linked' => false,
                    ],
                ],
            ],
            'visibilities' => [
                [
                    'salesChannelId' => $salesChannelId,
                    'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL,
                ],
            ],
        ]);

        $productMediaId = Uuid::randomHex();
        $this->productMediaRepository->create(
            [[
                'id' => $productMediaId,
                'productId' => $productId,
                'mediaId' => $this->getEmptyMedia()->getId(),
            ]],
            $this->context
        );

        $this->productRepository->update([[
            'id' => $productId,
            'coverId' => $productMediaId,
        ]], $this->context);

        $productCriteria = new Criteria();
        $productCriteria->addFilter(new EqualsFilter('id', $productId));
        $productCriteria->addAssociation('media');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('salesChannelId', $salesChannelId));
        $criteria->addAssociation('salesChannel');
        $criteria->addAssociation('salesChannelDomain');

        /** @var ProductExportEntity|null $productExport */
        $productExport = $this->productExportRepository->search($criteria, $this->context)->first();
        static::assertNotNull($productExport);

        return $productExport;
    }
}
