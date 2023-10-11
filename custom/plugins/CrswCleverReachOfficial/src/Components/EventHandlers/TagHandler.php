<?php

namespace Crsw\CleverReachOfficial\Components\EventHandlers;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Group\Contracts\GroupService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Tag\Tag;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\Configuration\SyncConfiguration;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\ReceiverSyncTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Segment\Contracts\SegmentService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Segment\Http\Proxy;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Segment\Tasks\CreateSegmentsTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Segment\Tasks\DeleteSegmentsTask;
use Crsw\CleverReachOfficial\Core\Infrastructure\Exceptions\BaseException;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\QueryFilter\Operators;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\QueryFilter\QueryFilter;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\RepositoryRegistry;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueItem;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Tag\TagService;

/**
 * Class TagHandler
 *
 * @package Crsw\CleverReachOfficial\Components\EventHandlers
 */
class TagHandler extends BaseHandler
{
    /**
     * Handles customer group or sales channel created event.
     */
    public function tagCreated(): void
    {
        $filter = (new QueryFilter())
            ->where('taskType', Operators::EQUALS, 'CreateSegmentsTask')
            ->where('status', Operators::EQUALS, QueueItem::QUEUED);
        $queuedSegmentTasksCount = RepositoryRegistry::getQueueItemRepository()->count($filter);

        if ($queuedSegmentTasksCount === 0) {
            $this->enqueueTask(new CreateSegmentsTask());
        }
    }

    /**
     * Resyncs segments.
     */
    public function resyncSegments(): void
    {
        try {
            $crSegments = $this->getProxy()->getSegments($this->getGroupService()->getId());

            $swSegments = explode(', ', $this->getSegmentsService()->getSegmentNames());
            $segmentsForDelete = [];

            foreach ($crSegments as $crSegment) {
                if (!in_array($crSegment->getName(), $swSegments, true)) {
                    $segmentsForDelete[] = $crSegment->getName();
                }
            }

            if ($segmentsForDelete) {
                $this->enqueueTask(new DeleteSegmentsTask($segmentsForDelete));
            }
        } catch (BaseException $e) {
            Logger::logError($e->getMessage());
        }
    }

    /**
     * Deletes customer group segment.
     *
     * @param $name
     */
    public function deleteSegment(string $name): void
    {
        $tag = new Tag(TagService::SOURCE, $name);
        $tag->setType(TagService::CUSTOMER_GROUP_TAG);

        $this->enqueueTask(new DeleteSegmentsTask([TagService::CUSTOMER_GROUP_TAG . ': ' . $name]));
        $this->enqueueTask(new ReceiverSyncTask(new SyncConfiguration([], [$tag])));
    }

    /**
     * Handles sales channel deleted event.
     *
     * @param string $name
     */
    public function salesChannelDeleted(string $name): void
    {
        $tag = new Tag(TagService::SOURCE, $name);
        $tag->setType(TagService::SHOP_TAG);

        $this->enqueueTask(new DeleteSegmentsTask([TagService::SHOP_TAG . ': ' . $name]));
        $this->enqueueTask(new ReceiverSyncTask(new SyncConfiguration([], [$tag])));
    }

    /**
     * @return SegmentService
     */
    private function getSegmentsService(): SegmentService
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(SegmentService::class);
    }

    /**
     * @return Proxy
     */
    private function getProxy(): Proxy
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(Proxy::class);
    }

    /**
     * @return GroupService
     */
    private function getGroupService(): GroupService
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(GroupService::class);
    }
}