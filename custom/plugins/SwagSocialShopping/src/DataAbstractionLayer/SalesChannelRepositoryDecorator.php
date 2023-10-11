<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagSocialShopping\DataAbstractionLayer;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\AggregationResultCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Write\CloneBehavior;
use SwagSocialShopping\Exception\UnexpectedSalesChannelTypeException;

class SalesChannelRepositoryDecorator implements EntityRepositoryInterface
{
    private EntityRepositoryInterface $innerRepository;

    public function __construct(EntityRepositoryInterface $innerRepository)
    {
        $this->innerRepository = $innerRepository;
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
        return $this->innerRepository->searchIds($criteria, $context);
    }

    public function clone(string $id, Context $context, ?string $newId = null, ?CloneBehavior $behavior = null): EntityWrittenContainerEvent
    {
        return $this->innerRepository->clone($id, $context, $newId, $behavior);
    }

    public function search(Criteria $criteria, Context $context): EntitySearchResult
    {
        $criteria->addAssociation('socialShoppingSalesChannel');

        return $this->innerRepository->search($criteria, $context);
    }

    public function update(array $data, Context $context): EntityWrittenContainerEvent
    {
        return $this->innerRepository->update($data, $context);
    }

    public function upsert(array $data, Context $context): EntityWrittenContainerEvent
    {
        foreach ($data as &$item) {
            if (!\array_key_exists('typeId', $item)) {
                continue;
            }

            $item['typeId'] = $this->cleanTypeId($item['typeId']);
        }

        return $this->innerRepository->upsert($data, $context);
    }

    public function create(array $data, Context $context): EntityWrittenContainerEvent
    {
        foreach ($data as &$item) {
            if (!\array_key_exists('typeId', $item)) {
                continue;
            }

            $item['typeId'] = $this->cleanTypeId($item['typeId']);
        }
        unset($item);

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

    private function cleanTypeId(string $typeId): string
    {
        $cleanedTypeId = \preg_replace('/-\w+/', '', $typeId);

        if ($cleanedTypeId !== null) {
            return $cleanedTypeId;
        }

        throw new UnexpectedSalesChannelTypeException($typeId, \preg_last_error());
    }
}
