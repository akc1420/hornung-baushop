<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\Components;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Receiver;

class ResolveReceiver extends ReceiverSyncSubTask
{
    const CLASS_NAME = __CLASS__;

    /**
     * Prepares receiver for subscription state change (subscribe / unsubscribe receiver task).
     */
    public function execute()
    {
        $context = $this->getExecutionContext();

        if (empty($context->email)) {
            $this->reportProgress(100);

            return;
        }

        $receiver = new Receiver();
        $receiver->setEmail($context->email);
        $context->receiver = $receiver;

        $this->reportProgress(100);
    }
}