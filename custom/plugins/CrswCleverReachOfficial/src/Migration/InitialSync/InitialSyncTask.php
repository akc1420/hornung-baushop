<?php


namespace Crsw\CleverReachOfficial\Migration\InitialSync;

use Crsw\CleverReachOfficial\Core\BusinessLogic\InitialSynchronization\Tasks\Composite\Components\GroupSynchronization;
use Crsw\CleverReachOfficial\Core\BusinessLogic\InitialSynchronization\Tasks\Composite\InitialSyncTask
    as BaseInitialSyncTask;

/**
 * Class InitialSyncTask
 *
 * @package Crsw\CleverReachOfficial\Migration\InitialSync
 */
class InitialSyncTask extends BaseInitialSyncTask
{
    protected function createSubTask($taskKey)
    {
        if ($taskKey === GroupSynchronization::class) {
            return new GroupSync();
        }

        return parent::createSubTask($taskKey);
    }
}