<?php

namespace Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution;

use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Interfaces\TaskRunnerManager as BaseService;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Interfaces\TaskRunnerWakeup;

class TaskRunnerManager implements BaseService
{
    /**
     * @var \Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\Configuration
     */
    protected $configuration;
    /**
     * @var \Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Interfaces\TaskRunnerWakeup
     */
    protected $tasRunnerWakeupService;

    /**
     * Halts task runner.
     */
    public function halt()
    {
        $this->getConfiguration()->setTaskRunnerHalted(true);
    }

    /**
     * Resumes task execution.
     */
    public function resume()
    {
        $this->getConfiguration()->setTaskRunnerHalted(false);
        $this->getTaskRunnerWakeupService()->wakeup();
    }

    /**
     * Retrieves configuration.
     *
     * @return \Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\Configuration Configuration instance.
     */
    protected function getConfiguration()
    {
        if ($this->configuration === null) {
            $this->configuration = ServiceRegister::getService(Configuration::CLASS_NAME);
        }

        return $this->configuration;
    }

    /**
     * Retrieves task runner wakeup service.
     *
     * @return \Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Interfaces\TaskRunnerWakeup Task runner wakeup instance.
     */
    protected function getTaskRunnerWakeupService()
    {
        if ($this->tasRunnerWakeupService === null) {
            $this->tasRunnerWakeupService = ServiceRegister::getService(TaskRunnerWakeup::CLASS_NAME);
        }

        return $this->tasRunnerWakeupService;
    }
}