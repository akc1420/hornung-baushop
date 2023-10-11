<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Segment\Tasks;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Segment\DTO\Segment;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Segment\Http\Proxy;

class CreateSegmentsTask extends SegmentTask
{
    const CLASS_NAME = __CLASS__;

    /**
     * Creates new segments or updates existing.
     *
     * Segments are identified by segment name, thus to use this one must assure that segment name is unique.
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function execute()
    {
        $groupId = $this->getGroupService()->getId();
        $proxy = $this->getProxy();

        $this->reportProgress(5);

        $segments = $this->getSegmentsByGroupId($groupId);
        $integrationSegments = $this->getSegmentService()->getSegments();

        $this->reportProgress(30);

        foreach ($integrationSegments as $integrationSegment) {
            $this->updateOrCreateSegment($integrationSegment, $segments, $proxy, $groupId);
            $this->reportAlive();
        }

        $this->reportProgress(100);
    }

    /**
     * Updates or creates segment.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Segment\DTO\Segment $integrationSegment
     * @param array $segments
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Segment\Http\Proxy $proxy
     * @param $groupId
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    protected function updateOrCreateSegment(
        Segment $integrationSegment,
        array &$segments,
        Proxy $proxy,
        $groupId
    ) {
        if (array_key_exists($integrationSegment->getName(), $segments)) {
            $segmentGroup = $segments[$integrationSegment->getName()];
            $this->updateSegments($integrationSegment, $segmentGroup, $groupId);
        } else {
            $proxy->createSegment($groupId, $integrationSegment);
        }
    }

    /**
     * Updates segments.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Segment\DTO\Segment $integrationSegment
     * @param Segment[] $segments
     * @param string $groupId
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    protected function updateSegments(Segment $integrationSegment, array &$segments, $groupId)
    {
        $proxy = $this->getProxy();

        foreach ($segments as $existingSegment) {
            $proxy->updateSegment($groupId, $existingSegment->getId(), $integrationSegment);
        }
    }
}