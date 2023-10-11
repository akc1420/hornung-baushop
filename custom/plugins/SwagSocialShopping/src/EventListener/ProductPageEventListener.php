<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagSocialShopping\EventListener;

use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use SwagSocialShopping\Component\Network\Pinterest;
use SwagSocialShopping\Component\Struct\PinterestMetaInformationExtension;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProductPageEventListener implements EventSubscriberInterface
{
    private EntityRepositoryInterface $socialShoppingSalesChannelRepository;

    public function __construct(EntityRepositoryInterface $socialShoppingSalesChannelRepository)
    {
        $this->socialShoppingSalesChannelRepository = $socialShoppingSalesChannelRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductPageLoadedEvent::class => 'addPinterestSnippets',
        ];
    }

    public function addPinterestSnippets(ProductPageLoadedEvent $event): void
    {
        $salesChannel = $event->getSalesChannelContext()->getSalesChannel();

        $criteria = new Criteria();
        $criteria
            ->addAssociation('salesChannelDomain')
            ->addAssociation('salesChannel')
            ->addFilter(
                new EqualsFilter('salesChannelDomain.salesChannelId', $salesChannel->getId()),
                new EqualsFilter('salesChannel.active', true),
                new EqualsFilter('network', Pinterest::class)
            );

        $result = $this->socialShoppingSalesChannelRepository->searchIds($criteria, $event->getContext());

        if ($result->getTotal() !== 1) {
            return;
        }

        $pinterestMetaInformation = new PinterestMetaInformationExtension();
        $pinterestMetaInformation->setIsPinterestSalesChannel(true);
        $pinterestMetaInformation->setAvailability(
            $this->getPinterestProductAvailability($event->getPage()->getProduct())
        );

        $pageMetaInformation = $event->getPage()->getMetaInformation();
        if ($pageMetaInformation === null) {
            return;
        }

        $pageMetaInformation->addExtension('pinterest', $pinterestMetaInformation);
    }

    private function getPinterestProductAvailability(SalesChannelProductEntity $productEntity): string
    {
        if (!$productEntity->getIsCloseout()
            || $productEntity->getAvailableStock() >= $productEntity->getMinPurchase()
        ) {
            return PinterestMetaInformationExtension::AVAILABILITY_IN_STOCK;
        }

        if ($productEntity->getAvailableStock() < $productEntity->getMinPurchase()
            && $productEntity->getRestockTime() && !$productEntity->getIsCloseout()
        ) {
            return PinterestMetaInformationExtension::AVAILABILITY_BACKORDER;
        }

        return PinterestMetaInformationExtension::AVAILABILITY_OUT_OF_STOCK;
    }
}
