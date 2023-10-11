<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\SupportConsole;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Contracts\AuthorizationService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Group\Contracts\GroupService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\SupportConsole\Contracts\SupportService as BaseService;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\ConfigEntity;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\ConfigurationManager;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\QueryFilter\Operators;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\QueryFilter\QueryFilter;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\RepositoryRegistry;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Interfaces\TaskRunnerWakeup;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Process;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueItem;

/**
 * Class SupportService
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\SupportConsole
 */
abstract class SupportService implements BaseService
{
    const QUEUE_ITEM_LIMIT = 25;
    /**
     * @var Configuration
     */
    protected $configService;
    /**
     * @var AuthorizationService
     */
    protected $authService;

    /**
     * @inheritDoc
     * @return array
     */
    public function get()
    {
        /** @var TaskRunnerWakeup $wakeUpService */
        $wakeUpService = ServiceRegister::getService(TaskRunnerWakeup::CLASS_NAME);
        try {
            $wakeUpService->wakeup();
            $wakeUpStatus = 'successful';
        } catch (\Exception $e) {
            $wakeUpStatus = "Failed because {$e->getMessage()}";
        }

        try {
            $processes = $this->getProcesses();
        } catch (\Exception $e) {
            $processes = "Unavailable because: {$e->getMessage()}";
        }

        try {
            $queuedItems = $this->getItems(QueueItem::QUEUED);
        } catch (\Exception $e) {
            $queuedItems = "Unavailable because: {$e->getMessage()}";
        }

        try {
            $queuedItemsCnt = $this->getQueueItemCount(QueueItem::QUEUED);
        } catch (\Exception $e) {
            $queuedItemsCnt = "Unavailable because: {$e->getMessage()}";
        }

        try {
            $inProgressItems = $this->getItems(QueueItem::IN_PROGRESS);
        } catch (\Exception $e) {
            $inProgressItems = "Unavailable because: {$e->getMessage()}";
        }

        try {
            $inProgressItemsCnt = $this->getQueueItemCount(QueueItem::IN_PROGRESS);
        } catch (\Exception $e) {
            $inProgressItemsCnt = "Unavailable because: {$e->getMessage()}";
        }

        try {
            $failedItems = $this->getItems(QueueItem::FAILED);
        } catch (\Exception $e) {
            $failedItems = "Unavailable because: {$e->getMessage()}";
        }

        try {
            $failedItemsCnt = $this->getQueueItemCount(QueueItem::FAILED);
        } catch (\Exception $e) {
            $failedItemsCnt = "Unavailable because: {$e->getMessage()}";
        }

        $result = array(
            'CONTEXT' => ConfigurationManager::getInstance()->getContext(),
            'SYSTEM_VERSION' => $this->getSystemVersion(),
            'INTEGRATION_VERSION' => $this->getIntegrationVersion(),
            'INTEGRATION_NAME' => $this->getConfigService()->getIntegrationName(),
            'INTEGRATION_CLIENT_ID' => $this->getConfigService()->getClientId(),
            'RECEIVER_GROUP_ID' => $this->getGroupService()->getId(),
            'MAX_STARTED_TASK_LIMIT' => $this->getConfigService()->getMaxStartedTasksLimit(),
            'MAX_TASK_EXECUTION_RETRIES' => $this->getConfigService()->getMaxTaskExecutionRetries(),
            'MAX_TASK_INACTIVITY_PERIOD' => $this->getConfigService()->getMaxTaskInactivityPeriod(),
            'MAX_ALIVE_TIME' => $this->getConfigService()->getTaskRunnerMaxAliveTime(),
            'ASYNC_STARTER_BATCH_SIZE' => $this->getConfigService()->getAsyncStarterBatchSize(),
            'ASYNC_REQUEST_TIMEOUT' => $this->getConfigService()->getAsyncRequestTimeout(),
            'TASK_RUNNER_STATUS' => $this->getConfigService()->getTaskRunnerStatus(),
            'TASK_RUNNER_WAKEUP_DELAY' => $this->getConfigService()->getTaskRunnerWakeupDelay(),
            'TASK_RUNNER_HALTED' => $this->getConfigService()->isTaskRunnerHalted(),
            'DEFAULT_QUEUE_NAME' => $this->getConfigService()->getDefaultQueueName(),
            'MIN_LOG_LEVEL_USER' => $this->getConfigService()->getMinLogLevelUser(),
            'MIN_LOG_LEVEL_GLOBAL' => $this->getConfigService()->getMinLogLevelGlobal(),
            'QUEUED_COUNT' => $queuedItemsCnt,
            'QUEUED_ITEMS' => $queuedItems,
            'IN_PROGRESS_COUNT' => $inProgressItemsCnt,
            'IN_PROGRESS_ITEMS' => $inProgressItems,
            'FAILED_COUNT' => $failedItemsCnt,
            'FAILED_ITEMS' => $failedItems,
            'PUBLIC_AVAILABLE_URLS' => $this->getPublicAvailableUrls(),
            'WAKEUP_STATUS' => $wakeUpStatus,
            'TASK_EXECUTION_PROCESSES' => $processes,
        );

        try {
            $result['CLEVERREACH_USER_INFO'] = $this->getAuthorizationService()->getUserInfo()->toArray();
        } catch (\Exception $e) {
            $result['CLEVERREACH_USER_INFO'] = "Unavailable because: {$e->getMessage()}";
        }

        try {
            $result['CLEVERREACH_ACCESS_TOKEN'] = $this->getAuthorizationService()->getAuthInfo()->getAccessToken();
        } catch (\Exception $e) {
            $result['CLEVERREACH_ACCESS_TOKEN'] = "Unavailable because: {$e->getMessage()}";
        }

        return $result;
    }

    /**
     * @inheritDoc
     *
     * @param array $payload
     *
     * @return array
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function update(array $payload)
    {
        if (array_key_exists('MAX_STARTED_TASK_LIMIT', $payload)) {
            $this->getConfigService()->setMaxStartedTasksLimit((int)$payload['MAX_STARTED_TASK_LIMIT']);
        }

        if (array_key_exists('MIN_LOG_LEVEL_USER', $payload)) {
            $this->getConfigService()->setMinLogLevelUser((int)$payload['MIN_LOG_LEVEL_USER']);
        }

        if (array_key_exists('MIN_LOG_LEVEL_GLOBAL', $payload)) {
            $this->getConfigService()->setMinLogLevelGlobal((int)$payload['MIN_LOG_LEVEL_GLOBAL']);
        }

        if (array_key_exists('ASYNC_STARTER_BATCH_SIZE', $payload)) {
            $this->getConfigService()->setAsyncStarterBatchSize((int)$payload['ASYNC_STARTER_BATCH_SIZE']);
        }

        if (array_key_exists('ASYNC_REQUEST_TIMEOUT', $payload)) {
            $this->getConfigService()->setAsyncRequestTimeout((int)$payload['ASYNC_REQUEST_TIMEOUT']);
        }

        if (array_key_exists('MAX_TASK_INACTIVITY_PERIOD', $payload)) {
            $this->getConfigService()->setMaxTaskInactivityPeriod((int)$payload['MAX_TASK_INACTIVITY_PERIOD']);
        }

        if (array_key_exists('TASK_RUNNER_WAKEUP_DELAY', $payload)) {
            $this->getConfigService()->setTaskRunnerWakeupDelay((int)$payload['TASK_RUNNER_WAKEUP_DELAY']);
        }

        if (array_key_exists('MAX_ALIVE_TIME', $payload)) {
            $this->getConfigService()->setTaskRunnerMaxAliveTime((int)$payload['MAX_ALIVE_TIME']);
        }

        if (array_key_exists('MAX_TASK_EXECUTION_RETRIES', $payload)) {
            $this->getConfigService()->setMaxTaskExecutionRetries((int)$payload['MAX_TASK_EXECUTION_RETRIES']);
        }

        if (array_key_exists('OFFLINE_MODE', $payload)) {
            $this->getAuthorizationService()->setIsOffline(true);
        }

        if (array_key_exists('TASK_RUNNER_HALTED', $payload)) {
            $this->getConfigService()->setTaskRunnerHalted($payload['TASK_RUNNER_HALTED']);
        }

        if (array_key_exists('HARD_RESET', $payload)) {
            $this->hardReset();
        }

        return array('status' => 'success');
    }


    /**
     * Returns task items for current context in provided status
     *
     * @param string $status
     *
     * @return array
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function getItems($status)
    {
        $filter = $this->getBaseQueueItemQuery($status);
        $filter->setLimit(self::QUEUE_ITEM_LIMIT);

        $itemEntities = RepositoryRegistry::getQueueItemRepository()->select($filter);

        $items = array();
        foreach ($itemEntities as $itemEntity) {
            $items[] = $itemEntity->toArray();
        }

        return $items;
    }

    /**
     * Provides queue item count.
     *
     * @param string $status
     *
     * @return int
     *
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function getQueueItemCount($status)
    {
        $filter = $this->getBaseQueueItemQuery($status);

        return RepositoryRegistry::getQueueItemRepository()->count($filter);
    }

    /**
     * Provides base queue item query.
     *
     * @param string $status
     *
     * @return \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\QueryFilter\QueryFilter
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    protected function getBaseQueueItemQuery($status)
    {
        $filter = new QueryFilter();
        $filter->where('context', Operators::EQUALS, ConfigurationManager::getInstance()->getContext())
            ->where('status', Operators::EQUALS, $status);

        return $filter;
    }

    /**
     * Returns public urls
     *
     * @return array
     */
    protected function getPublicAvailableUrls()
    {
        return array(
            'OAUTH_CALLBACK_URL' => $this->getAuthorizationService()->getRedirectURL(),
            'WEBHOOK_URL' => $this->getWebhookUrl(),
            'DYNAMIC_CONTENT_URLS' => $this->getDynamicContentUrls(),
            'ASYNC_URL' => $this->getConfigService()->getAsyncProcessUrl('/'),
        );
    }

    /**
     * Returns integrated system version
     *
     * @return string
     */
    abstract protected function getSystemVersion();

    /**
     * Returns extension version
     *
     * @return string
     */
    abstract protected function getIntegrationVersion();

    /**
     * Returns dynamic content urls
     *
     * @return array
     */
    abstract protected function getDynamicContentUrls();

    /**
     * Returns webhook url
     *
     * @return string
     */
    abstract protected function getWebhookUrl();

    /**
     * Removes all tenant specific data
     */
    abstract protected function hardReset();

    /**
     * @return Configuration
     */
    protected function getConfigService()
    {
        if ($this->configService === null) {
            $this->configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        }

        return $this->configService;
    }

    /**
     * Retrieves group service.
     *
     * @return GroupService | object
     */
    protected function getGroupService()
    {
        return ServiceRegister::getService(GroupService::CLASS_NAME);
    }

    /**
     * @return \Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\ConfigEntity
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function getMinLogLevelEntity()
    {
        $filter = new QueryFilter();
        /** @noinspection PhpUnhandledExceptionInspection */
        $filter->where('name', '=', 'minLogLevel');
        $filter->where('context', Operators::NULL);

        /** @var ConfigEntity $config */
        $config = $this->getConfigRepository()->selectOne($filter);

        return $config;
    }

    /**
     * @return \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Interfaces\RepositoryInterface
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function getConfigRepository()
    {
        return RepositoryRegistry::getRepository(ConfigEntity::getClassName());
    }

    /**
     * @return AuthorizationService
     */
    protected function getAuthorizationService()
    {
        if ($this->authService === null) {
            $this->authService = ServiceRegister::getService(AuthorizationService::CLASS_NAME);
        }

        return $this->authService;
    }

    /**
     * Retrieves processes.
     *
     * @return Process[] List of up to 10 processes.
     *
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    private function getProcesses()
    {
        $query = new QueryFilter();
        $query->setLimit(10);
        $repository = RepositoryRegistry::getRepository(Process::getClassName());
        $processes = $repository->select($query);
        $result = array();

        foreach ($processes as $process) {
            $result[] = $process->toArray();
        }

        return $result;
    }
}
