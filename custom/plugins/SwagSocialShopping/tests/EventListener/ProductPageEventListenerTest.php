<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\SocialShopping\Test\EventListener;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\Test\TestDefaults;
use Shopware\Storefront\Page\MetaInformation;
use Shopware\Storefront\Page\Product\ProductPage;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Swag\SocialShopping\Test\Helper\ServicesTrait;
use SwagSocialShopping\Component\Struct\PinterestMetaInformationExtension;
use SwagSocialShopping\EventListener\ProductPageEventListener;
use Symfony\Component\HttpFoundation\Request;

class ProductPageEventListenerTest extends TestCase
{
    use ServicesTrait;

    /**
     * @var ProductPageEventListener
     */
    private $productPageEventListener;

    /**
     * @var SalesChannelContextFactory
     */
    private $salesChannelContextFactory;

    protected function setUp(): void
    {
        $container = $this->getContainer();

        /** @var ProductPageEventListener $eventListener */
        $eventListener = $container->get(ProductPageEventListener::class);
        $this->productPageEventListener = $eventListener;

        /** @var SalesChannelContextFactory $contextFactory */
        $contextFactory = $container->get(SalesChannelContextFactory::class);
        $this->salesChannelContextFactory = $contextFactory;
    }

    public function testGetSubscribedEvents(): void
    {
        static::assertSame(
            [
                ProductPageLoadedEvent::class => 'addPinterestSnippets',
            ],
            ProductPageEventListener::getSubscribedEvents()
        );
    }

    public function testAddPinterestSnippets(): void
    {
        $this->createSocialShoppingSalesChannel(Uuid::randomHex());
        $salesChannelContext = $this->salesChannelContextFactory->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL);
        $product = new SalesChannelProductEntity();
        $product->setId(Uuid::randomHex());
        $metaInformation = new MetaInformation();
        $productPage = new ProductPage();
        $productPage->setProduct($product);
        $productPage->setMetaInformation($metaInformation);
        $event = new ProductPageLoadedEvent(
            $productPage,
            $salesChannelContext,
            new Request()
        );

        $this->productPageEventListener->addPinterestSnippets($event);

        $information = $event->getPage()->getMetaInformation();
        static::assertNotNull($information);

        $extensions = $information->getExtensions();
        static::assertCount(1, $extensions);
        static::assertArrayHasKey('pinterest', $extensions);
        $pinterestMetaInformationExtension = $extensions['pinterest'];
        static::assertInstanceOf(PinterestMetaInformationExtension::class, $pinterestMetaInformationExtension);
    }

    public function testAddPinterestSnippetsForNoneSocialShoppingSalesChannelDoesNotAddPinterestExtension(): void
    {
        $salesChannelContext = $this->salesChannelContextFactory->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL);
        $event = new ProductPageLoadedEvent(
            new ProductPage(),
            $salesChannelContext,
            new Request()
        );

        $this->productPageEventListener->addPinterestSnippets($event);

        $information = $event->getPage()->getMetaInformation();
        static::assertNull($information);
    }

    public function testAddPinterestSnippetsWithoutMetaInformationDoesNotAddPinterestExtension(): void
    {
        $this->createSocialShoppingSalesChannel(Uuid::randomHex());
        $salesChannelContext = $this->salesChannelContextFactory->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL);
        $product = new SalesChannelProductEntity();
        $product->setId(Uuid::randomHex());
        $productPage = new ProductPage();
        $productPage->setProduct($product);
        $event = new ProductPageLoadedEvent(
            $productPage,
            $salesChannelContext,
            new Request()
        );

        $this->productPageEventListener->addPinterestSnippets($event);

        $information = $event->getPage()->getMetaInformation();
        static::assertNull($information);
    }
}
