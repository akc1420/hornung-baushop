<?php

namespace Crsw\CleverReachOfficial\Core\Infrastructure;

use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\ConfigurationManager;
use Crsw\CleverReachOfficial\Core\Infrastructure\Http\CurlHttpClient;
use Crsw\CleverReachOfficial\Core\Infrastructure\Http\HttpClient;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\AsyncProcessStarterService;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Interfaces\AsyncProcessService;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Interfaces\TaskRunnerManager;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Interfaces\TaskRunnerStatusStorage;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Interfaces\TaskRunnerWakeup;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueService;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\RunnerStatusStorage;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\TaskRunner;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\TaskRunnerWakeupService;
use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Events\EventBus;
use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\GuidProvider;
use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\TimeProvider;

/**
 * Class BootstrapComponent.
 *
 * @package Crsw\CleverReachOfficial\Core\Infrastructure
 */
class BootstrapComponent
{
    /**
     * Initializes infrastructure components.
     */
    public static function init()
    {
        static::initServices();
        static::initRepositories();
        static::initEvents();
    }

    /**
     * Initializes services and utilities.
     */
    protected static function initServices()
    {
        ServiceRegister::registerService(
            ConfigurationManager::CLASS_NAME,
            function () {
                return ConfigurationManager::getInstance();
            }
        );
        ServiceRegister::registerService(
            TimeProvider::CLASS_NAME,
            function () {
                return TimeProvider::getInstance();
            }
        );
        ServiceRegister::registerService(
            GuidProvider::CLASS_NAME,
            function () {
                return GuidProvider::getInstance();
            }
        );
        ServiceRegister::registerService(
            EventBus::CLASS_NAME,
            function () {
                return EventBus::getInstance();
            }
        );
        ServiceRegister::registerService(
            AsyncProcessService::CLASS_NAME,
            function () {
                return AsyncProcessStarterService::getInstance();
            }
        );
        ServiceRegister::registerService(
            QueueService::CLASS_NAME,
            function () {
                return new QueueService();
            }
        );
        ServiceRegister::registerService(
            TaskRunnerWakeup::CLASS_NAME,
            function () {
                return new TaskRunnerWakeupService();
            }
        );
        ServiceRegister::registerService(
            TaskRunner::CLASS_NAME,
            function () {
                return new TaskRunner();
            }
        );
        ServiceRegister::registerService(
            TaskRunnerStatusStorage::CLASS_NAME,
            function () {
                return new RunnerStatusStorage();
            }
        );
        ServiceRegister::registerService(
            TaskRunnerManager::CLASS_NAME,
            function () {
                return new TaskExecution\TaskRunnerManager();
            }
        );
        ServiceRegister::registerService(
            HttpClient::CLASS_NAME,
            function () {
                return new CurlHttpClient();
            }
        );
    }

    /**
     * Initializes repositories.
     */
    protected static function initRepositories()
    {
    }

    /**
     * Initializes events.
     */
    protected static function initEvents()
    {
    }
}
