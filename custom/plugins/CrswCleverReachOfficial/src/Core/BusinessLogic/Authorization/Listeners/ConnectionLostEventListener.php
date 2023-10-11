<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Listeners;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Language\Translator;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Notification\Contracts\NotificationService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Notification\DTO\Notification;
use Crsw\CleverReachOfficial\Core\Infrastructure\Exceptions\ServiceNotRegisteredException;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\GuidProvider;

/**
 * Class ConnectionLostEventListener
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Listeners
 */
class ConnectionLostEventListener
{
    const CLASS_NAME = __CLASS__;

    /**
     * Push notification when connection lost with the CleverReach
     */
    public static function handle()
    {
        try {
            /** @var NotificationService $notificationService */
            $notificationService = ServiceRegister::getService(NotificationService::CLASS_NAME);
            $notification = new Notification(GuidProvider::getInstance()->generateGuid(), 'connectionLost');
            $notification->setDescription(Translator::translate('connectionLost'));
            $notification->setDate(new \DateTime());
            $notificationService->push($notification);
        } catch (ServiceNotRegisteredException $exception) {
            Logger::logInfo("Notifications are not supported. {$exception->getMessage()}");
        }
    }
}