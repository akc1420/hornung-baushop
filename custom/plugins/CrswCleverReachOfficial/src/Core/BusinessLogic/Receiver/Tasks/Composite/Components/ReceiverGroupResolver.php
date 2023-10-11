<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\Components;

class ReceiverGroupResolver extends ReceiverSyncSubTask
{
    const CLASS_NAME = __CLASS__;

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $this->getExecutionContext()->groupId = $this->getGroupService()->getId();
        $this->reportProgress(100);
    }
}