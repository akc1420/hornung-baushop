<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Configuration;

use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\Configuration as BaseConfiguration;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\TimeProvider;

/**
 * Class Configuration
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Configuration
 */
abstract class Configuration extends BaseConfiguration
{
    /**
     * Default sync batch size.
     */
    const DEFAULT_SYNC_BATCH_SIZE = 250;
    /**
     * Denotes number of seconds in a day.
     */
    const SECONDS_IN_A_DAY = 86400;
    /**
     * Denotes token lifetime in days.
     */
    const TOKEN_LIFE_TIME_IN_DAYS = 7;
    /**
     * Singleton instance of this class.
     *
     * @var static
     */
    protected static $instance;

    /**
     * Retrieves sync batch size.
     *
     * @return int
     */
    public function getSynchronizationBatchSize()
    {
        return (int)$this->getConfigValue('syncBatchSize', static::DEFAULT_SYNC_BATCH_SIZE);
    }

    /**
     * Sets synchronization batch size.
     *
     * @param $batchSize
     */
    public function setSynchronizationBatchSize($batchSize)
    {
        $this->saveConfigValue('syncBatchSize', $batchSize);
    }

    /**
     * Retrieves scheduler time threshold.
     *
     * @return int
     */
    public function getSchedulerTimeThreshold()
    {
        return $this->getConfigValue('schedulerThreshold', 30);
    }

    /**
     * Sets scheduler time threshold.
     *
     * @param int $threshold
     */
    public function setSchedulerTimeThreshold($threshold)
    {
        $this->saveConfigValue('schedulerThreshold', (int)$threshold);
    }

    /**
     * Retrieves schedule queue name.
     *
     * @return string
     */
    public function getSchedulerQueueName()
    {
        return $this->getConfigValue('schedulerQueueName', 'Schedule');
    }

    /**
     * Saves scheduler queue name.
     *
     * @param string $name
     */
    public function setSchedulerQueueName($name)
    {
        $this->saveConfigValue('schedulerQueueName', $name);
    }

    /**
     * Provides the time when the token expires for the provided access token.
     *
     * @param string $token Access token.
     *
     * @return int UNIX timestamp. Time when the token expires.
     */
    public function getTokenLifeTime($token)
    {
        $currentTime = $this->getTimeProvider()->getDateTime(time())->getTimestamp();

        return $currentTime + self::SECONDS_IN_A_DAY * self::TOKEN_LIFE_TIME_IN_DAYS;
    }

    /**
     * @inheritDoc
     *
     * @return int
     */
    public function getMinLogLevel()
    {
        return max($this->getMinLogLevelUser(), $this->getMinLogLevelGlobal(), Logger::WARNING);
    }

    /**
     * Returns user specific log level
     *
     * @return int
     */
    public function getMinLogLevelUser()
    {
        return (int)$this->getConfigValue('minLogLevel', static::MIN_LOG_LEVEL);
    }

    /**
     * Set user specific log level
     *
     * @param int $minLogLevel
     */
    public function setMinLogLevelUser($minLogLevel)
    {
        $this->saveConfigValue('minLogLevel', $minLogLevel);
    }

    /**
     * Returns global log level
     *
     * @return int
     */
    public function getMinLogLevelGlobal()
    {
        return (int)$this->getConfigValue('minLogLevelGlobal', Logger::ERROR);
    }

    /**
     * Set global log level
     *
     * @param int $minLogLevel
     */
    public function setMinLogLevelGlobal($minLogLevel)
    {
        $this->saveConfigValue('minLogLevelGlobal', $minLogLevel);
    }

    /**
     * Returns default queue name
     *
     * @return string default queue name
     */
    abstract public function getDefaultQueueName();

    /**
     * Retrieves client id of the integration.
     *
     * @return string
     */
    abstract public function getClientId();

    /**
     * Retrieves client secret of the integration.
     *
     * @return string
     */
    abstract public function getClientSecret();

    /**
     * Returns base url of the integrated system.
     *
     * @return string Url.
     */
    abstract public function getSystemUrl();

    /**
     * Provides time provider.
     *
     * @return TimeProvider | object Time provider instance.
     */
    protected function getTimeProvider()
    {
        return ServiceRegister::getService(TimeProvider::CLASS_NAME);
    }
}
