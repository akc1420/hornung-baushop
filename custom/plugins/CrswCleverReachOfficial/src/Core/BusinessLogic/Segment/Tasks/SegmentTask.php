<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Segment\Tasks;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Group\Contracts\GroupService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Segment\Contracts\SegmentService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Segment\Http\Proxy;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Task;

/**
 * Class SegmentTask
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Segment\Tasks
 */
abstract class SegmentTask extends Task
{
    /**
     * Segment service.
     *
     * @var \Crsw\CleverReachOfficial\Core\BusinessLogic\Segment\Contracts\SegmentService
     */
    protected $segmentService;
    /**
     * Group service.
     *
     * @var \Crsw\CleverReachOfficial\Core\BusinessLogic\Group\Contracts\GroupService
     */
    protected $groupService;
    /**
     * Segment proxy.
     *
     * @var \Crsw\CleverReachOfficial\Core\BusinessLogic\Segment\Http\Proxy
     */
    protected $proxy;

    /**
     * Retrieves segments by group id.
     *
     * @param string $groupId
     *
     * @return array
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    protected function getSegmentsByGroupId($groupId)
    {
        $result = array();
        $segments = $this->getProxy()->getSegments($groupId);

        foreach ($segments as $segment) {
            $result[$segment->getName()][] = $segment;
        }

        return $result;
    }

    /**
     * Retrieve segment service.
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Segment\Contracts\SegmentService
     */
    protected function getSegmentService()
    {
        if ($this->segmentService === null) {
            $this->segmentService = ServiceRegister::getService(SegmentService::CLASS_NAME);
        }

        return $this->segmentService;
    }

    /**
     * Retrieve group service.
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Group\Contracts\GroupService
     */
    protected function getGroupService()
    {
        if ($this->groupService === null) {
            $this->groupService = ServiceRegister::getService(GroupService::CLASS_NAME);
        }

        return $this->groupService;
    }

    /**
     * Retrieve proxy.
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Segment\Http\Proxy
     */
    protected function getProxy()
    {
        if ($this->proxy === null) {
            $this->proxy = ServiceRegister::getService(Proxy::CLASS_NAME);
        }

        return $this->proxy;
    }
}