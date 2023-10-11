<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\Components;

use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;

/**
 * Class RemoveReceiverFromBlacklist
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\Components
 */
class RemoveReceiverFromBlacklist extends ReceiverSyncSubTask
{
    const CLASS_NAME = __CLASS__;

    /**
     * Whitelists receiver.
     */
    public function execute()
    {
        $receiver = $this->getExecutionContext()->receiver;
        if ($receiver !== null) {
            try {
                $suffix = $this->getGroupService()->getBlacklistedEmailsSuffix();
                $this->getReceiverProxy()->whitelist($receiver->getEmail() . $suffix);
            } catch (\Exception $e) {
                Logger::logWarning(
                    "Failed to remove receiver from a blacklist because: {$e->getMessage()}.",
                    'Core',
                    array('trace' => $e->getTraceAsString())
                );
            }
        }

        $this->reportProgress(100);
    }
}
