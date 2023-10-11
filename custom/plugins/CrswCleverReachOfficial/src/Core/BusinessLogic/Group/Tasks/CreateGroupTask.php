<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Group\Tasks;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Group\Contracts\GroupService;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Task;

/**
 * Class CreateGroupTask
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Group\Tasks
 *
 * @access protected
 */
class CreateGroupTask extends Task
{
    const CLASS_NAME = __CLASS__;

    /**
     * Group service.
     *
     * @var \Crsw\CleverReachOfficial\Core\BusinessLogic\Group\Contracts\GroupService
     */
    protected $groupService;

    /**
     * Creates group (if group does not already exist). Sets group id locally.
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function execute()
    {
        $groupService = $this->getGroupService();
        $name = $groupService->getName();

        $this->reportProgress(5);

        $group = $groupService->getGroupByName($name);

        $this->reportProgress(50);

        if ($group === null) {
            $group = $groupService->createGroup($name);
        }

        $groupService->setId($group->getId());
        $this->reportProgress(100);
    }

    /**
     * Retrieves group service.
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Group\Contracts\GroupService Instance of the GroupService.
     */
    protected function getGroupService()
    {
        if ($this->groupService === null) {
            $this->groupService = ServiceRegister::getService(GroupService::CLASS_NAME);
        }

        return $this->groupService;
    }
}