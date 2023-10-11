<?php

namespace Crsw\CleverReachOfficial\Service\BusinessLogic\OfflineMode;

use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\ConfigurationManager;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use DateTime;

/**
 * Class OfflineModeCheckService
 *
 * @package Crsw\CleverReachOfficial\Service\BusinessLogic\OfflineMode
 */
class OfflineModeCheckService
{
    /**
     * Sets offlineModeCheckTime config value.
     *
     * @param DateTime $checkTime
     *
     * @throws QueryFilterInvalidParamException
     */
    public function setOfflineModeCheckTime(DateTime $checkTime): void
    {
        ConfigurationManager::getInstance()->saveConfigValue('offlineModeCheckTime', $checkTime);
    }

    /**
     * Gets offlineModeCheckTime config value.
     *
     * @return DateTime | null
     *
     * @throws QueryFilterInvalidParamException
     */
    public function getOfflineModeCheckTime(): ?DateTime
    {
        $checkTime = strtotime(ConfigurationManager::getInstance()->getConfigValue('offlineModeCheckTime')['date']);

        if (!$checkTime) {
            return null;
        }

        return (new DateTime())->setTimestamp($checkTime);
    }
}
