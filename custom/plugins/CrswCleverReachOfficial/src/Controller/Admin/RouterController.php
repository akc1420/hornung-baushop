<?php

namespace Crsw\CleverReachOfficial\Controller\Admin;

use Crsw\CleverReachOfficial\Components\Utility\Bootstrap;
use Crsw\CleverReachOfficial\Components\Utility\Initializer;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\AuthorizationService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\InitialSynchronization\Tasks\Composite\InitialSyncTask;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\ConfigurationManager;
use Crsw\CleverReachOfficial\Core\Infrastructure\Exceptions\BaseException;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Interfaces\TaskRunnerWakeup;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueService;
use Shopware\Core\Framework\Api\Response\JsonApiResponse;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class RouterController
 *
 * @package Crsw\CleverReachOfficial\Controller\Admin
 */
class RouterController extends AbstractController
{
    public const WELCOME_STATE_CODE = 'welcome';
    public const DASHBOARD_STATE_CODE = 'dashboard';
    public const REFRESH_STATE_CODE = 'refresh';
    public const AUTO_CONFIG_STATE_CODE = 'autoConfig';
    public const SYNC_SETTINGS = 'syncSettings';

    /**
     * @var AuthorizationService
     */
    private $authService;
    /**
     * @var QueueService
     */
    private $queueService;

    /**
     * RouterController constructor.
     *
     * @param Initializer $initializer
     * @param AuthorizationService $authService
     * @param QueueService $queueService
     */
    public function __construct(
        Initializer $initializer,
        AuthorizationService $authService,
        QueueService $queueService
    ) {
        Bootstrap::init();
        $initializer->registerServices();
        $this->authService = $authService;
        $this->queueService = $queueService;
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(path="api/v{version}/cleverreach/router", name="api.cleverreach.router", methods={"GET", "POST"})
     * @Route(path="/api/cleverreach/router", name="api.cleverreach.router.new", methods={"GET", "POST"})
     *
     * @return JsonApiResponse
     */
    public function getRouteName(): JsonApiResponse
    {
        try {
            if (!ConfigurationManager::getInstance()->getConfigValue('pluginOpened')) {
                ConfigurationManager::getInstance()->saveConfigValue('pluginOpened', true);
                $this->getTaskRunnerWakeup()->wakeup();
            }
        } catch (QueryFilterInvalidParamException $e) {
            Logger::logError($e->getMessage());
        }

        return new JsonApiResponse(['routeName' => $this->getCurrentStateCode()]);
    }

    /**
     * @return string
     */
    private function getCurrentStateCode(): string
    {
        $permittedActions = [];

        if (!$this->isAutoConfigured()) {
            $permittedActions[] = self::AUTO_CONFIG_STATE_CODE;
        }

        if (empty($permittedActions) && !$this->isAuthorized()) {
            $permittedActions[] = self::WELCOME_STATE_CODE;
        }

        if (empty($permittedActions) && !$this->areCredentialsValid()) {
            $permittedActions[] = self::REFRESH_STATE_CODE;
        }

        if (empty($permittedActions) && !$this->isInitialSyncEnqueued()) {
            $permittedActions[] = self::SYNC_SETTINGS;
        }

        if (empty($permittedActions)) {
            $permittedActions[] = self::DASHBOARD_STATE_CODE;
        }

        return $permittedActions[0];
    }

    /**
     * @return bool
     */
    private function isAutoConfigured(): bool
    {
        $result = false;
        $state = $this->getConfigService()->getAutoConfigurationState();

        if ($state === 'succeeded') {
            $result = true;
        }

        if ($state === 'failed') {
            $result = false;
        }

        return $result;
    }

    /**
     * Check if user is authorized with CleverReach.
     *
     * @return bool
     */
    private function isAuthorized(): bool
    {
        $result = true;

        try {
            $this->authService->getAuthInfo();
        } catch (BaseException $e) {
            $result = false;
        }

        return $result;
    }

    /**
     * Checks if oauth credentials are valid.
     *
     * @return bool
     */
    private function areCredentialsValid(): bool
    {
        try {
            $isOffline = $this->authService->getFreshOfflineStatus();
        } catch (QueryFilterInvalidParamException $e) {
            $isOffline = true;
        }

        if ($isOffline) {
            return false;
        }

        $result = true;

        try {
            $this->authService->getAuthInfo();
        } catch (BaseException $e) {
            $result = false;
        }

        return $result;
    }

    /**
     * Checks if initial sync task has already been enqueued.
     *
     * @return bool
     */
    private function isInitialSyncEnqueued(): bool
    {
        $initialSyncTask = $this->queueService->findLatestByType(InitialSyncTask::getClassName());

        return $initialSyncTask !== null;
    }

    /**
     * @return Configuration
     */
    private function getConfigService(): Configuration
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(Configuration::CLASS_NAME);
    }

    /**
     * @return TaskRunnerWakeup
     */
    private function getTaskRunnerWakeup(): TaskRunnerWakeup
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(TaskRunnerWakeup::CLASS_NAME);
    }
}
