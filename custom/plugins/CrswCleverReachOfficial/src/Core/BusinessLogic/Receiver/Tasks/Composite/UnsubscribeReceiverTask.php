<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\Components\AddReceiverToBlacklist;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\Components\ReceiverGroupResolver;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\Components\ResolveReceiver;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\Components\SyncServicesResolver;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\Components\UnsubscribeReceiver;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\Components\UpsertReceiver;

class UnsubscribeReceiverTask extends SubscribeReceiverTask
{
    protected function getSubTasks()
    {
        return array(
            ReceiverGroupResolver::CLASS_NAME => 5,
            SyncServicesResolver::CLASS_NAME => 10,
            ResolveReceiver::CLASS_NAME => 5,
            UnsubscribeReceiver::CLASS_NAME => 45,
            AddReceiverToBlacklist::CLASS_NAME => 5,
            UpsertReceiver::CLASS_NAME => 30,
        );
    }
}