<?php


namespace Crsw\CleverReachOfficial\Migration\MigrationSteps;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Group\Contracts\GroupService;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Migration\Repository\V2Repository;

/**
 * Class MigrateGroupId
 *
 * @package Crsw\CleverReachOfficial\Migration\MigrationSteps
 */
class MigrateGroupId extends Step
{
    /**
     * Migrate group id.
     */
    public function execute(): void
    {
        $groupId = V2Repository::getGroupID();

        if (!$groupId) {
            return;
        }

        $this->getGroupService()->setId($groupId['value']);
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