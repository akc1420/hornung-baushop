<?php

namespace Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution;

use InvalidArgumentException;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\Serializer\Interfaces\Serializable;
use Crsw\CleverReachOfficial\Core\Infrastructure\Serializer\Serializer;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\TaskEvents\AliveAnnouncedTaskEvent;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\TaskEvents\TaskProgressEvent;
use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Events\EventEmitter;
use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\TimeProvider;
use RuntimeException;

/**
 * Class Task
 * @package Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution
 */
abstract class Task extends EventEmitter implements Serializable
{
    /**
     * Max inactivity period for a task in seconds
     */
    const MAX_INACTIVITY_PERIOD = 300;
    /**
     * Minimal number of seconds that must pass between two alive signals
     */
    const ALIVE_SIGNAL_FREQUENCY = 2;
    /**
     * Time of last invoked alive signal.
     *
     * @var \DateTime
     */
    private $lastAliveSignalTime;
    /**
     * An instance of Configuration service.
     *
     * @var Configuration
     */
    private $configService;
    /**
     * Task execution id.
     *
     * @var string
     */
    private $executionId;

    /**
     * Runs task logic.
     *
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\AbortTaskExecutionException
     */
    abstract public function execute();

    /**
     * @inheritdoc
     */
    public function serialize()
    {
        return Serializer::serialize(array());
    }

    /**
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        // This method was intentionally left blank because
        // this task doesn't have any properties which needs to encapsulate.
    }

    /**
     * @inheritDoc
     */
    public static function fromArray(array $array)
    {
        return new static();
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return array();
    }

    /**
     * Reports task progress by emitting @param float|int $progressPercent
     *   Float representation of progress percentage, value between 0 and 100 that will immediately
     *   be converted to base points. One base point is equal to 0.01%. For example 23.58% is
     *   equal to 2358 base points
     *
     * @throws \InvalidArgumentException In case when progress percent is outside of 0 - 100 boundaries or not an float
     * @see TaskProgressEvent and defers next @see AliveAnnouncedTaskEvent.
     *
     */
    public function reportProgress($progressPercent)
    {
        if (!is_int($progressPercent) && !is_float($progressPercent)) {
            throw new InvalidArgumentException('Progress percentage must be value integer or float value');
        }

        /** @var TimeProvider $timeProvider */
        $timeProvider = ServiceRegister::getService(TimeProvider::CLASS_NAME);
        $this->lastAliveSignalTime = $timeProvider->getCurrentLocalTime();

        $this->fire(new TaskProgressEvent($this->percentToBasePoints($progressPercent)));
    }

    /**
     * Reports that task is alive by emitting @see AliveAnnouncedTaskEvent.
     */
    public function reportAlive()
    {
        /** @var TimeProvider $timeProvider */
        $timeProvider = ServiceRegister::getService(TimeProvider::CLASS_NAME);
        $currentTime = $timeProvider->getCurrentLocalTime();
        if ($this->lastAliveSignalTime === null
            || $this->lastAliveSignalTime->getTimestamp() + self::ALIVE_SIGNAL_FREQUENCY < $currentTime->getTimestamp()
        ) {
            $this->fire(new AliveAnnouncedTaskEvent());
            $this->lastAliveSignalTime = $timeProvider->getCurrentLocalTime();
        }
    }

    /**
     * Gets max inactivity period for a task.
     *
     * @return int Max inactivity period for a task in seconds.
     *
     */
    public function getMaxInactivityPeriod()
    {
        $configurationValue = $this->getConfigService()->getMaxTaskInactivityPeriod();

        return $configurationValue !== null ? $configurationValue : static::MAX_INACTIVITY_PERIOD;
    }

    /**
     * Gets name of the class.
     * Alias method for static method {@see self::getClassName()}
     *
     * @return string FQN of the task.
     */
    public function getType()
    {
        return static::getClassName();
    }

    /**
     * Gets name of the class.
     *
     * @return string FQN of the task.
     */
    public static function getClassName()
    {
        $namespaceParts = explode('\\', get_called_class());
        $name = end($namespaceParts);

        if ($name === 'Task') {
            throw new RuntimeException('Constant CLASS_NAME not defined in class ' . get_called_class());
        }

        return $name;
    }

    /**
     * Determines whether task can be reconfigured.
     *
     * @return bool TRUE if task can be reconfigured; otherwise, FALSE.
     */
    public function canBeReconfigured()
    {
        return false;
    }

    /**
     * Reconfigures the task.
     */
    public function reconfigure()
    {
    }

    /**
     * Gets execution Id.
     *
     * @return string Execution Id.
     */
    public function getExecutionId()
    {
        return $this->executionId;
    }

    /**
     * Sets Execution id.
     *
     * @param string $executionId Execution id.
     */
    public function setExecutionId($executionId)
    {
        $this->executionId = $executionId;
    }

    /**
     * Calculates base points for progress tracking from percent value.
     *
     * @param float $percentValue Value in float representation.
     *
     * @return int Base points representation of percentage.
     */
    private function percentToBasePoints($percentValue)
    {
        return (int)round($percentValue * 100, 2);
    }

    /**
     * Gets Configuration service.
     *
     * @return Configuration Service instance.
     */
    protected function getConfigService()
    {
        if ($this->configService === null) {
            $this->configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        }

        return $this->configService;
    }
}
