<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagSocialShopping\EventListener;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\SalesChannel\SalesChannelEvents;
use SwagSocialShopping\Component\DataFeed\DataFeedHandler;
use SwagSocialShopping\SwagSocialShopping;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DataFeedEventListener implements EventSubscriberInterface
{
    private DataFeedHandler $dataFeedHandler;

    private EntityRepositoryInterface $salesChannelRepository;

    public function __construct(DataFeedHandler $dataFeedHandler, EntityRepositoryInterface $salesChannelRepository)
    {
        $this->dataFeedHandler = $dataFeedHandler;
        $this->salesChannelRepository = $salesChannelRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SwagSocialShopping::SOCIAL_SHOPPING_SALES_CHANNEL_WRITTEN_EVENT => 'afterWrite',
            SalesChannelEvents::SALES_CHANNEL_WRITTEN => 'afterWriteSalesChannel',
        ];
    }

    public function afterWrite(EntityWrittenEvent $event): void
    {
        foreach ($event->getWriteResults() as $writeResult) {
            if (!$this->needsDataFeed($writeResult)) {
                continue;
            }

            $this->dataFeedHandler->createDataFeedForWriteResult($writeResult, $event);
        }
    }

    public function afterWriteSalesChannel(EntityWrittenEvent $event): void
    {
        foreach ($event->getWriteResults() as $writeResult) {
            if ($writeResult->getOperation() === EntityWriteResult::OPERATION_DELETE
                || $writeResult->getOperation() === EntityWriteResult::OPERATION_INSERT) {
                continue;
            }

            $payload = $writeResult->getPayload();

            if (!empty($payload['typeId']) && $payload['typeId'] !== SwagSocialShopping::SALES_CHANNEL_TYPE_SOCIAL_SHOPPING) {
                continue;
            }

            if (empty($payload['id'])) {
                continue;
            }

            if (empty($payload['typeId'])) {
                /** @var SalesChannelEntity|null $salesChannel */
                $salesChannel = $this->salesChannelRepository->search(new Criteria([$payload['id']]), $event->getContext())->first();

                if ($salesChannel === null || $salesChannel->getTypeId() !== SwagSocialShopping::SALES_CHANNEL_TYPE_SOCIAL_SHOPPING) {
                    continue;
                }
            }

            $this->dataFeedHandler->updateActiveStatus($writeResult, $event->getContext());
        }
    }

    private function needsDataFeed(EntityWriteResult $writeResult): bool
    {
        if ($writeResult->getEntityName() !== 'swag_social_shopping_sales_channel') {
            return false;
        }

        if (\in_array($writeResult->getOperation(), [
            EntityWriteResult::OPERATION_DELETE,
            EntityWriteResult::OPERATION_UPDATE,
        ], true)) {
            return false;
        }

        if (isset($writeResult->getPayload()['network'])
            && \in_array($writeResult->getPayload()['network'], DataFeedHandler::RELEVANT_NETWORKS, true)) {
            return true;
        }

        return !empty($writeResult->getPayload()['id']);
    }
}
