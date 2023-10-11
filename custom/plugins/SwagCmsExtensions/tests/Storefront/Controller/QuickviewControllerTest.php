<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Test\Storefront\Controller;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Framework\Routing\RequestTransformer;
use Shopware\Storefront\Test\Page\StorefrontPageTestBehaviour;
use Swag\CmsExtensions\Storefront\Controller\QuickviewController;
use Swag\CmsExtensions\Storefront\Pagelet\Quickview\QuickviewPageletLoader;
use Swag\CmsExtensions\Storefront\Pagelet\Quickview\QuickviewPageletLoaderInterface;
use Swag\CmsExtensions\Storefront\Pagelet\Quickview\QuickviewVariantPageletLoader;
use Swag\CmsExtensions\Test\Helper\QuickviewHelperTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class QuickviewControllerTest extends TestCase
{
    use IntegrationTestBehaviour;
    use StorefrontPageTestBehaviour;
    use QuickviewHelperTrait;

    /**
     * @var QuickviewController
     */
    private $quickviewController;

    /**
     * @var SalesChannelContext
     */
    private $salesChannelContext;

    public function setUp(): void
    {
        $quickviewController = $this->getContainer()->get(QuickviewController::class);
        static::assertInstanceOf(QuickviewController::class, $quickviewController);

        $this->quickviewController = $quickviewController;
        $this->salesChannelContext = $this->createSalesChannelContext();
    }

    public function testQuickviewRendersCorrectly(): void
    {
        $randomProduct = $this->getRandomProduct($this->salesChannelContext);
        $request = $this->getQuickviewRequest($randomProduct);

        $this->getRequestStack()->push($request);

        $result = $this->quickviewController->quickview($this->salesChannelContext, $request);
        static::assertNotNull($result);
        static::assertEquals(200, $result->getStatusCode());
    }

    public function testQuickviewRendersVariantCorrectly(): void
    {
        $this->createProduct($this->salesChannelContext);
        $options = $this->getVariantOptions();

        $request = $this->getVariantRequest($options, $this->salesChannelContext);
        $this->getRequestStack()->push($request);

        $result = $this->quickviewController->quickviewVariant($this->salesChannelContext, $request);
        static::assertNotNull($result);
        static::assertEquals(200, $result->getStatusCode());
    }

    protected function getPageLoader(): QuickviewPageletLoaderInterface
    {
        $quickviewPageletLoader = $this->getContainer()->get(QuickviewPageletLoader::class);
        static::assertInstanceOf(QuickviewPageletLoader::class, $quickviewPageletLoader);

        return $quickviewPageletLoader;
    }

    protected function getVariantPageLoader(): QuickviewVariantPageletLoader
    {
        $quickviewVariantPageletLoader = $this->getContainer()->get(QuickviewVariantPageletLoader::class);
        static::assertInstanceOf(QuickviewVariantPageletLoader::class, $quickviewVariantPageletLoader);

        return $quickviewVariantPageletLoader;
    }

    private function getQuickviewRequest(ProductEntity $product): Request
    {
        $request = new Request([], [], ['productId' => $product->getId()]);
        $request->attributes->set(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT, $this->salesChannelContext);
        $request->attributes->set(RequestTransformer::STOREFRONT_URL, '');

        return $request;
    }

    private function getRequestStack(): RequestStack
    {
        $requestStack = $this->getContainer()->get('request_stack');
        static::assertInstanceOf(RequestStack::class, $requestStack);

        return $requestStack;
    }
}
