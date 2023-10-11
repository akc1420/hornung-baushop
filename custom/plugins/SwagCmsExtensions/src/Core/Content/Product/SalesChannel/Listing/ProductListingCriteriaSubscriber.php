<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Core\Content\Product\SalesChannel\Listing;

use Shopware\Core\Content\Product\Events\ProductListingCriteriaEvent;
use Shopware\Core\Content\Product\ProductEvents;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Swag\CmsExtensions\Storefront\Controller\QuickviewController;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProductListingCriteriaSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ProductEvents::PRODUCT_LISTING_CRITERIA => 'filterByParentId',
        ];
    }

    /**
     * filterByParentId adds a filter for the parentId supplied in the request to the
     * product listing criteria object. This is used to determine the original product id
     * shown in the listing (listingProductId) when switching variants via the quickview.
     *
     * @see \Swag\CmsExtensions\Storefront\Pagelet\Quickview\QuickviewVariantPageletLoader::getListingProductId()
     */
    public function filterByParentId(ProductListingCriteriaEvent $event): void
    {
        if ($event->getRequest()->attributes->get('_route') !== QuickviewController::QUICKVIEW_VARIANT_ROUTE) {
            return;
        }

        $event->getCriteria()
            ->addFilter(new EqualsFilter('product.parentId', $event->getRequest()->request->get('parentId')))
            ->setLimit(1);
    }
}
