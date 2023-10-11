<?php

namespace Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution;

use Exception;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException;
use Crsw\CleverReachOfficial\Core\Infrastructure\Http\HttpClient;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Interfaces\RepositoryInterface;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\QueryFilter\QueryFilter;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\RepositoryRegistry;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\Singleton;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\ProcessStarterSaveException;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Interfaces\AsyncProcessService;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Interfaces\Runnable;
use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\GuidProvider;

/**
 * Class AsyncProcessStarter.
 *
 * @package Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution
 */
class AsyncProcessStarterService extends Singleton implements AsyncProcessService
{
    /**
     * Singleton instance of this class.
     *
     * @var static
     */
    protected static $instance;
    /**
     * Configuration instance.
     *
     * @var Configuration
     */
    private $configuration;
    /**
     * Process entity repository.
     *
     * @var RepositoryInterface
     */
    private $processRepository;
    /**
     * GUID provider instance.
     *
     * @var GuidProvider
     */
    private $guidProvider;
    /**
     * HTTP client.
     *
     * @var HttpClient
     */
    private $httpClient;

    /**
     * AsyncProcessStarterService constructor.
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function __construct()
    {
        parent::__construct();

        $this->httpClient = ServiceRegister::getService(HttpClient::CLASS_NAME);
        $this->guidProvider = ServiceRegister::getService(GuidProvider::CLASS_NAME);
        $this->configuration = ServiceRegister::getService(Configuration::CLASS_NAME);
        $this->processRepository = RepositoryRegistry::getRepository(Process::CLASS_NAME);
    }

    /**
     * Starts given runner asynchronously (in new process/web request or similar).
     *
     * @param Runnable $runner Runner that should be started async.
     *
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\ProcessStarterSaveException
     */
    public function start(Runnable $runner)
    {
        $guid = trim($this->guidProvider->generateGuid());

        $this->saveGuidAndRunner($guid, $runner);
        $this->startRunnerAsynchronously($guid);
    }

    /**
     * Runs a process with provided identifier.
     *
     * @param string $guid Identifier of process.
     */
    public function runProcess($guid)
    {
        try {
            $filter = new QueryFilter();
            $filter->where('guid', '=', $guid);

            /** @var Process $process */
            $process = $this->processRepository->selectOne($filter);
            if ($process !== null) {
                $process->getRunner()->run();
                $this->processRepository->delete($process);
            }
        } catch (Exception $e) {
            Logger::logError($e->getMessage(), 'Core', array('guid' => $guid));
        }
    }

    /**
     * Saves runner and guid to storage.
     *
     * @param string $guid Unique process identifier.
     * @param Runnable $runner Runner instance.
     *
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\ProcessStarterSaveException
     */
    private function saveGuidAndRunner($guid, Runnable $runner)
    {
        try {
            $process = new Process();
            $process->setGuid($guid);
            $process->setRunner($runner);

            $this->processRepository->save($process);
        } catch (Exception $e) {
            Logger::logError($e->getMessage());
            throw new ProcessStarterSaveException($e->getMessage(), 0, $e);
        }
    }

    /**
     * Starts runnable asynchronously.
     *
     * @param string $guid Unique process identifier.
     *
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    private function startRunnerAsynchronously($guid)
    {
        try {
            $this->httpClient->requestAsync(
                $this->configuration->getAsyncProcessCallHttpMethod(),
                $this->configuration->getAsyncProcessUrl($guid)
            );
        } catch (Exception $e) {
            Logger::logError($e->getMessage(), 'Integration');
            throw new HttpRequestException($e->getMessage(), 0, $e);
        }
    }
}
