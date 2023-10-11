<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagSocialShopping\DataAbstractionLayer;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\AggregationResultCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Write\CloneBehavior;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelType\SalesChannelTypeEntity;
use SwagSocialShopping\Component\Network\NetworkInterface;
use SwagSocialShopping\Component\Network\NetworkRegistryInterface;
use SwagSocialShopping\Exception\InvalidNetworkException;
use SwagSocialShopping\SwagSocialShopping;

/**
 * @deprecated tag:v3.2.0 - Class will be removed
 */
class SalesChannelTypeRepositoryDecorator implements EntityRepositoryInterface
{
    private EntityRepositoryInterface $innerRepository;

    private NetworkRegistryInterface $networkRegistry;

    private EntityRepositoryInterface $salesChannelRepository;

    public function __construct(
        EntityRepositoryInterface $innerRepository,
        NetworkRegistryInterface $networkRegistry,
        EntityRepositoryInterface $salesChannelRepository
    ) {
        $this->innerRepository = $innerRepository;
        $this->networkRegistry = $networkRegistry;
        $this->salesChannelRepository = $salesChannelRepository;
    }

    public function getDefinition(): EntityDefinition
    {
        return $this->innerRepository->getDefinition();
    }

    public function aggregate(Criteria $criteria, Context $context): AggregationResultCollection
    {
        return $this->innerRepository->aggregate($criteria, $context);
    }

    public function searchIds(Criteria $criteria, Context $context): IdSearchResult
    {
        $this->hideSocialShoppingContainer($criteria);

        return $this->innerRepository->searchIds($criteria, $context);
    }

    public function clone(string $id, Context $context, ?string $newId = null, ?CloneBehavior $behavior = null): EntityWrittenContainerEvent
    {
        return $this->innerRepository->clone($id, $context, $newId, $behavior);
    }

    /**
     * @throws InvalidNetworkException
     */
    public function search(Criteria $criteria, Context $context): EntitySearchResult
    {
        $result = $this->innerRepository->search($criteria, $context);

        if (
            $result->getEntities()->get(SwagSocialShopping::SALES_CHANNEL_TYPE_SOCIAL_SHOPPING) === null
            || !$this->hasStorefrontSalesChannel($context)
        ) {
            return $result;
        }

        // Remove the original Saleschannel type, that got replaced
        $result->getEntities()->remove(SwagSocialShopping::SALES_CHANNEL_TYPE_SOCIAL_SHOPPING);
        $total = $result->getTotal() - 1;

        foreach ($this->networkRegistry->getNetworks() as $network) {
            if (!($network instanceof NetworkInterface)) {
                throw new InvalidNetworkException(\get_class($network));
            }

            $salesChannelType = new SalesChannelTypeEntity();
            $salesChannelType->setName(\ucfirst($network->getName()));
            $salesChannelType->setIconName($network->getIconName());
            $salesChannelType->setUniqueIdentifier(
                \sprintf('%s-%s', SwagSocialShopping::SALES_CHANNEL_TYPE_SOCIAL_SHOPPING, $network->getName())
            );
            $salesChannelType->setTranslated([
                'name' => \sprintf('%s.%s', $network->getTranslationKey(), 'name'),
                'description' => \sprintf('%s.%s', $network->getTranslationKey(), 'description'),
                'manufacturer' => \sprintf('%s.%s', $network->getTranslationKey(), 'manufacturer'),
                'descriptionLong' => \sprintf('%s.%s', $network->getTranslationKey(), 'descriptionLong'),
            ]);
            $salesChannelType->setCustomFields(['isSocialShoppingType' => true]);

            $result->add($salesChannelType);
            ++$total;
        }

        return new EntitySearchResult(
            $this->getDefinition()->getEntityName(),
            $total,
            $result->getEntities(),
            $result->getAggregations(),
            $result->getCriteria(),
            $result->getContext()
        );
    }

    public function update(array $data, Context $context): EntityWrittenContainerEvent
    {
        return $this->innerRepository->update($data, $context);
    }

    public function upsert(array $data, Context $context): EntityWrittenContainerEvent
    {
        return $this->innerRepository->upsert($data, $context);
    }

    public function create(array $data, Context $context): EntityWrittenContainerEvent
    {
        return $this->innerRepository->create($data, $context);
    }

    public function delete(array $ids, Context $context): EntityWrittenContainerEvent
    {
        return $this->innerRepository->delete($ids, $context);
    }

    public function createVersion(string $id, Context $context, ?string $name = null, ?string $versionId = null): string
    {
        return $this->innerRepository->createVersion($id, $context, $name, $versionId);
    }

    public function merge(string $versionId, Context $context): void
    {
        $this->innerRepository->merge($versionId, $context);
    }

    private function hideSocialShoppingContainer(Criteria $criteria): void
    {
        $criteria->addFilter(new NotFilter(
            NotFilter::CONNECTION_AND,
            [
                new EqualsFilter('id', SwagSocialShopping::SALES_CHANNEL_TYPE_SOCIAL_SHOPPING),
            ]
        ));
    }

    private function hasStorefrontSalesChannel(Context $context): bool
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('typeId', Defaults::SALES_CHANNEL_TYPE_STOREFRONT));
        $criteria->setTotalCountMode(Criteria::TOTAL_COUNT_MODE_EXACT);

        return $this->salesChannelRepository->searchIds($criteria, $context)->getTotal() > 0;
    }
}
