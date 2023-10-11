<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Segment;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Group\Contracts\GroupService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Segment\DTO\Segment;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Segment\Http\Proxy;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;

/**
 * Class SegmentService
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Segment
 */
abstract class SegmentService implements Contracts\SegmentService
{
    /**
     * @var GroupService
     */
    protected $groupService;
    /**
     * @var Proxy
     */
    protected $proxy;

    /**
     * @param string $filter
     *
     * @return Segment|null
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function getSegment($filter)
    {
        $segments = $this->getProxy()->getSegments($this->getGroupService()->getId());
        foreach ($segments as $segment) {
            if ($segment->isConditionMatch($filter)) {
                return $segment;
            }
        }

        return null;
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
