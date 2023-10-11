<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Test\Storefront\Pagelet\Quickview;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\Exception\ProductNotFoundException;
use Shopware\Core\Content\Property\PropertyGroupCollection;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Storefront\Pagelet\PageletLoadedEvent;
use Shopware\Storefront\Test\Page\StorefrontPageTestBehaviour;
use Shopware\Storefront\Test\Page\StorefrontPageTestConstants;
use Swag\CmsExtensions\Storefront\Pagelet\Quickview\QuickviewPagelet;
use Swag\CmsExtensions\Storefront\Pagelet\Quickview\QuickviewPageletLoadedEvent;
use Swag\CmsExtensions\Storefront\Pagelet\Quickview\QuickviewPageletLoader;
use Swag\CmsExtensions\Storefront\Pagelet\Quickview\QuickviewPageletLoaderInterface;
use Symfony\Component\HttpFoundation\Request;

class QuickviewPageletLoaderTest extends TestCase
{
    use IntegrationTestBehaviour;
    use StorefrontPageTestBehaviour;

    /**
     * @var QuickviewPageletLoaderInterface
     */
    private $quickviewPageletLoader;

    public function setUp(): void
    {
        $quickviewPageletLoader = $this->getContainer()->get(QuickviewPageletLoader::class);
        static::assertInstanceOf(QuickviewPageletLoader::class, $quickviewPageletLoader);

        $this->quickviewPageletLoader = $quickviewPageletLoader;
    }

    public function testItRequiresProductParam(): void
    {
        $request = new Request();
        $context = $this->createSalesChannelContext();

        $this->expectParamMissingException('productId');
        $this->getPageLoader()->load($request, $context);
    }

    public function testItRequiresAValidProductParam(): void
    {
        $request = new Request([], [], ['productId' => 'ffffffffffffffffffffffffffffffff']);
        $context = $this->createSalesChannelContext();

        $this->expectException(ProductNotFoundException::class);
        $this->getPageLoader()->load($request, $context);
    }

    public function testItDoesLoadATestProduct(): void
    {
        $context = $this->createSalesChannelContext();
        $product = $this->getRandomProduct($context);

        $request = new Request([], [], ['productId' => $product->getId()]);

        $event = null;
        $this->catchEvent(QuickviewPageletLoadedEvent::class, $event);

        /** @var QuickviewPagelet $pagelet */
        $pagelet = $this->getPageLoader()->load($request, $context);

        static::assertSame(StorefrontPageTestConstants::PRODUCT_NAME, $pagelet->getProduct()->getName());
        static::assertInstanceOf(PageletLoadedEvent::class, $event);
        static::assertSame($context, $event->getSalesChannelContext());
        static::assertSame($context->getContext(), $event->getContext());
        static::assertSame($request, $event->getRequest());
        static::assertSame($pagelet, $event->getPagelet());
        static::assertSame($product->getId(), $event->getPagelet()->getListingProductId());

        $reviews = $event->getPagelet()->getReviews();
        static::assertIsObject($reviews);
        static::assertEquals(0, $reviews->getMatrix()->getAverageRating());
        static::assertEquals(0, $reviews->getMatrix()->getTotalReviewCount());
        static::assertEquals(0, $event->getPagelet()->getTotalReviews());
        static::assertInstanceOf(PropertyGroupCollection::class, $event->getPagelet()->getConfiguratorSettings());
    }

    protected function getPageLoader(): QuickviewPageletLoaderInterface
    {
        return $this->quickviewPageletLoader;
    }
}
