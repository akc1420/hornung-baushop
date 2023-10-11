<?php declare(strict_types=1);
/**
 * gb media
 * All Rights Reserved.
 *
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * The content of this file is proprietary and confidential.
 *
 * @category       Shopware
 * @package        Shopware_Plugins
 * @subpackage     GbmedForm
 * @copyright      Copyright (c) 2020, gb media
 * @license        proprietary
 * @author         Giuseppe Bottino
 * @link           http://www.gb-media.biz
 */

namespace Gbmed\Form\Services;

use Monolog\Logger as MonologLogger;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Uuid\Uuid;

class Logger
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var EntityRepository
     */
    private $logEntryRepository;

    /**
     * Logger constructor.
     * @param LoggerInterface $logger
     * @param EntityRepository $logEntryRepository
     */
    public function __construct(
        LoggerInterface $logger,
        EntityRepository $logEntryRepository
    ) {
        $this->logger = $logger;
        $this->logEntryRepository = $logEntryRepository;
    }

    /**
     * write logger info and logEntry
     *
     * @param string $message
     * @param array $context
     */
    public function info(string $message, array $context): void
    {
        $this->logger->info($message, $context);
        $this->logEntry(
            $message,
            MonologLogger::INFO,
            $context
        );
    }

    /**
     * write logger warning and logEntry
     *
     * @param string $message
     * @param array $context
     */
    public function warning(string $message, array $context): void
    {
        $this->logger->warning($message, $context);
        $this->logEntry(
            $message,
            MonologLogger::WARNING,
            $context
        );
    }

    /**
     * write logger error and logEntry
     *
     * @param string $message
     * @param array $context
     */
    public function error(string $message, array $context): void
    {
        $this->logger->error($message, $context);
        $this->logEntry(
            $message,
            MonologLogger::ERROR,
            $context
        );
    }

    /**
     * write logger debug and logEntry
     *
     * @param string $message
     * @param array $context
     */
    public function debug(string $message, array $context): void
    {
        $this->logger->debug($message, $context);
        $this->logEntry(
            $message,
            MonologLogger::DEBUG,
            $context
        );
    }

    /**
     * create logEntry
     *
     * @param string $message
     * @param int $level
     * @param array $context
     */
    public function logEntry(string $message, int $level, array $context): void
    {
        $data = [
            'id' => Uuid::randomHex(),
            'message' => $message,
            'level' => $level,
            'channel' => $this->logger->getName(),
            'context' => $this->getContext($context)
        ];

        try {
            $this->logEntryRepository->create([$data], Context::createDefaultContext());
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), $data);
        }
    }

    /**
     * return logEntry context
     *
     * @param array $context
     * @return array
     */
    private function getContext(array $context): array
    {
        return array_merge([
            'plugin' => $this->logger->getName()
        ], $context);
    }
}
