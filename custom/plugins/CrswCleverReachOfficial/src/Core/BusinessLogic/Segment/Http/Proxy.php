<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Segment\Http;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Http\Proxy as BaseProxy;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Segment\DTO\Segment;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Segment\DTO\Segment\Transofrmers\SubmitTransformer;

/**
 * Class Proxy
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Segment\Http
 */
class Proxy extends BaseProxy
{
    const CLASS_NAME = __CLASS__;

    /**
     * Retrieves all segments in a group.
     *
     * @param string $groupId Group identifier that will be used to retrieve segments.
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Segment\DTO\Segment[]
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function getSegments($groupId)
    {
        $response = $this->get("groups.json/$groupId/filters");

        return Segment::fromBatch($response->decodeBodyToArray());
    }

    /**
     * Creates segment in a receiver group.
     *
     * @param string $groupId Group identifier that will be used when creating segments
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Segment\DTO\Segment $segment Segment data.
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function createSegment($groupId, Segment $segment)
    {
        $this->post("groups.json/$groupId/filters", SubmitTransformer::transform($segment));
    }

    /**
     * Updates segment.
     *
     * @param string $groupId
     * @param string $segmentId
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Segment\DTO\Segment $segment
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function updateSegment($groupId, $segmentId, Segment $segment)
    {
        $this->put("groups.json/$groupId/filters/$segmentId", SubmitTransformer::transform($segment));
    }

    /**
     * Deletes segment in a group identified by segment id.
     *
     * @param string $groupId Group identifier.
     * @param string $segmentId Segment identifier.
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function deleteSegment($groupId, $segmentId)
    {
        $this->delete("groups.json/$groupId/filters/$segmentId");
    }
}