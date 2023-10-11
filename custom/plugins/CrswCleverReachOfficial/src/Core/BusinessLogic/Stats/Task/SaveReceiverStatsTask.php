<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Stats\Task;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Scheduler\ScheduledTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Stats\Contracts\SnapshotService;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;

/**
 * Class SaveReceiverStatsTas
 *  * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Stats\Task
 */
class SaveReceiverStatsTask extends ScheduledTask
{
    /**
     * @inheritDoc
     */
    public function execute()
    {
        $this->reportProgress(5);

        /** @var SnapshotService $snapshotService */
        $snapshotService = ServiceRegister::getService(SnapshotService::CLASS_NAME);

        $snapshotService->createSnapshot();
        $this->reportProgress(50);

        $snapshotService->remove();
        $this->reportProgress(100);
    }
}
