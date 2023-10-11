<?php

namespace Crsw\CleverReachOfficial\Service\Infrastructure;

use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\LogData;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Configuration\ConfigService;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger as MonologLogger;
use Shopware\Core\Kernel;

/**
 * Class LoggerService
 *
 * @package Crsw\CleverReachOfficial\Service\Infrastructure
 */
class LoggerService implements ShopLoggerAdapter
{
    /**
     * @var Kernel
     */
    private $kernel;

    /**
     * LoggerService constructor.
     *
     * @param Kernel $logger
     */
    public function __construct(Kernel $logger)
    {
        $this->kernel = $logger;
    }

    /**
     * Log message in system
     *
     * @param LogData $data
     */
    public function logMessage(LogData $data): void
    {
        $logLevel = $data->getLogLevel();
        $configService = ConfigService::getInstance();

        if ($logLevel > $configService->getMinLogLevel()) {
            return;
        }

        $message = "[Date: {$data->getTimestamp()}] Message: {$data->getMessage()}";

        $logger = $this->getSystemLogger();

        switch ($logLevel) {
            case Logger::ERROR:
                $logger->error($message);
                break;
            case Logger::WARNING:
                $logger->warning($message);
                break;
            case Logger::DEBUG:
                $logger->debug($message);
                break;
            default:
                $logger->info($message);
        }
    }

    /**
     * Returns system logger with predefined log directory and log file
     *
     * @return MonologLogger
     */
    private function getSystemLogger(): MonologLogger
    {
        $logger = new MonologLogger('cleverreach');
        $logFile = $this->kernel->getLogDir() . '/cleverreach/cleverreach.log';
        $logger->pushHandler(new RotatingFileHandler($logFile));

        return $logger;
    }
}
