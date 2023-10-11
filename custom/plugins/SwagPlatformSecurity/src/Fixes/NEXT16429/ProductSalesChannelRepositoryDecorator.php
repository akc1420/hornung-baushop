<?php declare(strict_types=1);

namespace Swag\Security\Fixes\NEXT16429;

use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\AggregationResultCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepositoryInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class ProductSalesChannelRepositoryDecorator implements SalesChannelRepositoryInterface
{
    /**
     * @var SalesChannelRepositoryInterface $inner
     */
    private $inner;

    public function __construct(SalesChannelRepositoryInterface $inner) {
        $this->inner = $inner;
    }

    public function search(Criteria $criteria, SalesChannelContext $salesChannelContext): EntitySearchResult
    {
        if ($criteria->hasAssociation('productReviews')) {
            $association = $criteria->getAssociation('productReviews');
            $activeReviewsFilter = new MultiFilter(MultiFilter::CONNECTION_OR, [new EqualsFilter('status', true)]);
            if ($customer = $salesChannelContext->getCustomer()) {
                $activeReviewsFilter->addQuery(new EqualsFilter('customerId', $customer->getId()));
            }

            $association->addFilter($activeReviewsFilter);
        }

        return $this->inner->search($criteria, $salesChannelContext);
    }

    public function aggregate(Criteria $criteria, SalesChannelContext $salesChannelContext): AggregationResultCollection
    {
        return $this->inner->aggregate($criteria, $salesChannelContext);
    }

    public function searchIds(Criteria $criteria, SalesChannelContext $salesChannelContext): IdSearchResult
    {
        return $this->inner->searchIds($criteria, $salesChannelContext);
    }
}
