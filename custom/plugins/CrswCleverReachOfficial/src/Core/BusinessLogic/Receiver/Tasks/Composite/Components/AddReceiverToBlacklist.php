<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\Components;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Blacklist\Blacklist;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Receiver;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;

/**
 * Class AddReceiverToBlacklist
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\Components
 */
class AddReceiverToBlacklist extends ReceiverSyncSubTask
{
    const CLASS_NAME = __CLASS__;

    /**
     * Blacklists receivers.
     */
    public function execute()
    {
        $receiver = $this->getExecutionContext()->receiver;
        if ($receiver !== null && $this->canBlacklistReceiver($receiver)) {
            try {
                $suffix = $this->getGroupService()->getBlacklistedEmailsSuffix();
                $blacklist = new Blacklist($receiver->getEmail() . $suffix);
                $blacklist->setComment('REST API' . $suffix);
                $this->getReceiverProxy()->blacklist($blacklist);
            } catch (\Exception $e) {
                Logger::logWarning(
                    "Failed to add receiver to a blacklist because: {$e->getMessage()}.",
                    'Core',
                    array('trace' => $e->getTraceAsString())
                );
            }
        }

        $this->reportProgress(100);
    }

    /**
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Receiver $receiver
     *
     * @return bool
     */
    private function canBlacklistReceiver(Receiver $receiver)
    {
        $deactivated = $receiver->getDeactivated();

        return empty($deactivated);
    }
}