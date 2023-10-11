<?php

namespace Crsw\CleverReachOfficial\Components\OfflineMode;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Contracts\AuthorizationService;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Service\BusinessLogic\OfflineMode\OfflineModeCheckService;
use DateInterval;
use DateTime;

/**
 * Class OfflineModeTickHandler
 *
 * @package Crsw\CleverReachOfficial\Components\OfflineMode
 */
class OfflineModeTickHandler
{
    public static function handle(): void
    {
        /** @var AuthorizationService $authService */
        $authService = ServiceRegister::getService(AuthorizationService::class);
        /** @var OfflineModeCheckService $offlineModeService */
        $offlineModeService = ServiceRegister::getService(OfflineModeCheckService::class);

        $status = $authService->isOffline();

        if (!$status) {
            return;
        }

        try {
            $lastCheckTime = $offlineModeService->getOfflineModeCheckTime();
            $nextCheckTime = $lastCheckTime ? $lastCheckTime->add(new DateInterval('PT12H')) : new DateTime();
            $now = new DateTime();

            if ($nextCheckTime <= $now) {
                $authService->getFreshOfflineStatus();
                $offlineModeService->setOfflineModeCheckTime(new DateTime());
            }
        } catch (QueryFilterInvalidParamException $e) {
            Logger::logError($e->getMessage());
        }
    }
}