<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Segment\Tasks;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Segment\DTO\Segment;
use Crsw\CleverReachOfficial\Core\Infrastructure\Serializer\Serializer;

/**
 * Class DeleteSegmentsTask
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Segment\Tasks
 */
class DeleteSegmentsTask extends SegmentTask
{
    /**
     * List of segment names that identify segments that must be deleted.
     *
     * @var array
     */
    protected $segmentNames;

    /**
     * DeleteSegmentsTask constructor.
     *
     * @param array $segmentNames
     */
    public function __construct(array $segmentNames)
    {
        $this->segmentNames = $segmentNames;
    }

    /**
     * @inheritDoc
     */
    public static function fromArray(array $serializedData)
    {
        return new self($serializedData['segmentNames']);
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return array('segmentNames' => $this->segmentNames);
    }

    /**
     * @inheritDoc
     */
    public function serialize()
    {
        return Serializer::serialize(array('segmentNames' => $this->segmentNames));
    }

    /**
     * @inheritDoc
     */
    public function unserialize($serialized)
    {
        $unserialized = Serializer::unserialize($serialized);
        $this->segmentNames = $unserialized['segmentNames'];
    }

    /**
     * Deletes segments identified by the segment name.
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function execute()
    {
        $groupId = $this->getGroupService()->getId();
        $segments = $this->getSegmentsByGroupId($groupId);
        $this->reportProgress(30);
        foreach ($segments as $name => $segmentGroup) {
            if (in_array($name, $this->segmentNames, true)) {
                $this->deleteSegments($groupId, $segmentGroup);
            }

            $this->reportAlive();
        }

        $this->reportProgress(100);
    }

    /**
     * Deletes list of segments.
     *
     * @param string $groupId
     * @param Segment[] $segments
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    protected function deleteSegments($groupId, array $segments)
    {
        $proxy = $this->getProxy();

        foreach ($segments as $segment) {
            $proxy->deleteSegment($groupId, $segment->getId());
        }
    }
}