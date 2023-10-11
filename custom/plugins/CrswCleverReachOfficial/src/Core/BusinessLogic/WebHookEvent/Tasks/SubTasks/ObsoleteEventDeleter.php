<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\Tasks\SubTasks;

use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;

class ObsoleteEventDeleter extends SubTask
{
    const CLASS_NAME = __CLASS__;

    /**
     * Removes event that has been previously registered.
     */
    public function execute()
    {
        try {
            $groupId = $this->getGroupService()->getId();
            $type = $this->getEventsService()->getType();
            $this->getEventsProxy()->deleteEvent($groupId, $type);
        } catch (\Exception $e) {
            Logger::logWarning(
                'Failed to delete obsolete event.',
                'Core',
                array('trace' => $e->getTraceAsString())
            );
        }

        $this->reportProgress(100);
    }
}