<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Contracts\AuthorizationService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Events\AuthorizationEventBus;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Events\ConnectionLostEvent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Http\AuthProxy;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Http\OauthStatusProxy;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Http\RefreshProxy;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Http\TokenProxy;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Http\UserProxy;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Listeners\ConnectionLostEventListener;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Contracts\AbandonedCartEntityService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Contracts\AbandonedCartRecordService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Contracts\AbandonedCartSettingsService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Events\AbandonedCartConvertedEvent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Events\AbandonedCartConvertedEventListener;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Events\AbandonedCartEvent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Events\AbandonedCartEventListener;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Events\AbandonedCartEventsBus;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Events\AbandonedCartUpdatedEvent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Events\AbandonedCartUpdatedEventListener;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Pipeline\AbandonedCartCreatePipeline;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Pipeline\AbandonedCartTriggerPipeline;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Pipeline\AlreadyProcessedFilter;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Pipeline\CartCreatedFilter;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Pipeline\CartFilter;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Pipeline\ReceiverFilter;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Pipeline\RecordFilter;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Dashboard\Contracts\DashboardService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Contracts\FormCacheService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\WebHooks\Handler;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Group\Http\Proxy;
use Crsw\CleverReachOfficial\Core\BusinessLogic\InitialSynchronization\Events\InitialSyncCompletedEvent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\InitialSynchronization\Listeners\InitialSyncCompletedListener;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Interfaces\AutomationRecordService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Interfaces\CartAutomationService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Listeners\AutomationRecordStatusListener;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Tasks\Trigger\Filter\AutomationFilter;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Tasks\Trigger\Filter\CartDataFilter;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Tasks\Trigger\Filter\FilterChain;
use Crsw\CleverReachOfficial\Core\BusinessLogic\PaymentPlan\Contracts\PaymentPlanService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Contracts\SyncConfigService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Stats\Contracts\SnapshotService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Stats\Contracts\StatsService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Survey\Contracts\SurveyService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\SyncSettings\Events\EnabledServicesSetEvent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\SyncSettings\Events\SyncSettingsEventBus;
use Crsw\CleverReachOfficial\Core\BusinessLogic\SyncSettings\Listeners\EnabledSyncServicesChangeRecorder;
use Crsw\CleverReachOfficial\Core\BusinessLogic\SyncSettings\Listeners\SyncSettingsUpdater;
use Crsw\CleverReachOfficial\Core\BusinessLogic\TaskExecution\Events\QueueItemAbortedEvent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\TaskExecution\Events\QueueItemEnqueuedEvent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\TaskExecution\Events\QueueItemFailedEvent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\TaskExecution\Events\QueueItemFinishedEvent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\TaskExecution\Events\QueueItemStateTransitionEventBus;
use Crsw\CleverReachOfficial\Core\BusinessLogic\TaskExecution\Events\TaskCompletedEventBus;
use Crsw\CleverReachOfficial\Core\BusinessLogic\TaskExecution\QueueService;
use Crsw\CleverReachOfficial\Core\Infrastructure\BootstrapComponent as InfrastructureBootstrap;
use Crsw\CleverReachOfficial\Core\Infrastructure\Http\CurlHttpClient;
use Crsw\CleverReachOfficial\Core\Infrastructure\Http\HttpClient;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueService as BaseQueueService;

/**
 * Class BootstrapComponent
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic
 */
class BootstrapComponent extends InfrastructureBootstrap
{
    public static function init()
    {
        parent::init();

        static::initProxies();
        static::initWebHookHandlers();
        static::initPipelines();
    }

    /**
     * Initializes services and utilities.
     */
    public static function initServices()
    {
        parent::initServices();

        ServiceRegister::registerService(
            BaseQueueService::CLASS_NAME,
            function () {
                return new QueueService();
            }
        );

        ServiceRegister::registerService(
            SyncConfigService::CLASS_NAME,
            function () {
                return Receiver\SyncConfigService::getInstance();
            }
        );

        ServiceRegister::registerService(
            HttpClient::CLASS_NAME,
            function () {
                return new CurlHttpClient();
            }
        );

        ServiceRegister::registerService(
            DashboardService::CLASS_NAME,
            function () {
                return new Dashboard\DashboardService();
            }
        );

        ServiceRegister::registerService(
            PaymentPlanService::CLASS_NAME,
            function () {
                return new PaymentPlan\PaymentPlanService();
            }
        );

        ServiceRegister::registerService(
            FormCacheService::CLASS_NAME,
            function () {
                return new Form\FormCacheService();
            }
        );

        ServiceRegister::registerService(
            SurveyService::CLASS_NAME,
            function () {
                return new Survey\SurveyService();
            }
        );

        ServiceRegister::registerService(
            StatsService::CLASS_NAME,
            function () {
                return new Stats\StatsService();
            }
        );
        ServiceRegister::registerService(
            SnapshotService::CLASS_NAME,
            function () {
                return new Stats\SnapshotService();
            }
        );

        ServiceRegister::registerService(
            AbandonedCartSettingsService::CLASS_NAME,
            function () {
                return new Automation\AbandonedCart\AbandonedCartSettingsService();
            }
        );

        ServiceRegister::registerService(
            AbandonedCartEntityService::CLASS_NAME,
            function () {
                return new Automation\AbandonedCart\AbandonedCartEntityService();
            }
        );

        ServiceRegister::registerService(
            AbandonedCartRecordService::CLASS_NAME,
            function () {
                return new Automation\AbandonedCart\AbandonedCartRecordService();
            }
        );

        ServiceRegister::registerService(
            CartAutomationService::CLASS_NAME,
            function () {
                return new Multistore\AbandonedCart\Services\CartAutomationService();
            }
        );

        ServiceRegister::registerService(
            AutomationRecordService::CLASS_NAME,
            function () {
                return new Multistore\AbandonedCart\Services\AutomationRecordService();
            }
        );
    }

    /**
     * Initializes proxies.
     */
    public static function initProxies()
    {
        ServiceRegister::registerService(
            Proxy::CLASS_NAME,
            function () {
                /** @var HttpClient $httpClient */
                $httpClient = ServiceRegister::getService(HttpClient::CLASS_NAME);
                /** @var AuthorizationService $authService */
                $authService = ServiceRegister::getService(AuthorizationService::CLASS_NAME);

                return new Proxy($httpClient, $authService);
            }
        );

        ServiceRegister::registerService(
            Field\Http\Proxy::CLASS_NAME,
            function () {
                /** @var HttpClient $httpClient */
                $httpClient = ServiceRegister::getService(HttpClient::CLASS_NAME);
                /** @var AuthorizationService $authService */
                $authService = ServiceRegister::getService(AuthorizationService::CLASS_NAME);

                return new Field\Http\Proxy($httpClient, $authService);
            }
        );

        ServiceRegister::registerService(
            Segment\Http\Proxy::CLASS_NAME,
            function () {
                /** @var HttpClient $httpClient */
                $httpClient = ServiceRegister::getService(HttpClient::CLASS_NAME);
                /** @var AuthorizationService $authService */
                $authService = ServiceRegister::getService(AuthorizationService::CLASS_NAME);

                return new Segment\Http\Proxy($httpClient, $authService);
            }
        );

        ServiceRegister::registerService(
            Receiver\Http\Proxy::CLASS_NAME,
            function () {
                /** @var HttpClient $httpClient */
                $httpClient = ServiceRegister::getService(HttpClient::CLASS_NAME);
                /** @var AuthorizationService $authService */
                $authService = ServiceRegister::getService(AuthorizationService::CLASS_NAME);

                return new Receiver\Http\Proxy($httpClient, $authService);
            }
        );

        ServiceRegister::registerService(
            UserProxy::CLASS_NAME,
            function () {
                /** @var HttpClient $httpClient */
                $httpClient = ServiceRegister::getService(HttpClient::CLASS_NAME);
                /** @var AuthorizationService $authService */
                $authService = ServiceRegister::getService(AuthorizationService::CLASS_NAME);

                return new UserProxy($httpClient, $authService);
            }
        );

        ServiceRegister::registerService(
            AuthProxy::CLASS_NAME,
            function () {
                /** @var HttpClient $httpClient */
                $httpClient = ServiceRegister::getService(HttpClient::CLASS_NAME);

                return new AuthProxy($httpClient);
            }
        );

        ServiceRegister::registerService(
            RefreshProxy::CLASS_NAME,
            function () {
                /** @var HttpClient $httpClient */
                $httpClient = ServiceRegister::getService(HttpClient::CLASS_NAME);

                return new RefreshProxy($httpClient);
            }
        );

        ServiceRegister::registerService(
            OauthStatusProxy::CLASS_NAME,
            function () {
                /** @var HttpClient $httpClient */
                $httpClient = ServiceRegister::getService(HttpClient::CLASS_NAME);
                /** @var AuthorizationService $authService */
                $authService = ServiceRegister::getService(AuthorizationService::CLASS_NAME);

                return new OauthStatusProxy($httpClient, $authService);
            }
        );

        ServiceRegister::registerService(
            SyncSettings\Http\Proxy::CLASS_NAME,
            function () {
                /** @var HttpClient $httpClient */
                $httpClient = ServiceRegister::getService(HttpClient::CLASS_NAME);
                /** @var AuthorizationService $authService */
                $authService = ServiceRegister::getService(AuthorizationService::CLASS_NAME);

                return new SyncSettings\Http\Proxy($httpClient, $authService);
            }
        );

        ServiceRegister::registerService(
            PaymentPlan\Http\Proxy::CLASS_NAME,
            function () {
                /** @var HttpClient $httpClient */
                $httpClient = ServiceRegister::getService(HttpClient::CLASS_NAME);
                /** @var AuthorizationService $authService */
                $authService = ServiceRegister::getService(AuthorizationService::CLASS_NAME);

                return new PaymentPlan\Http\Proxy($httpClient, $authService);
            }
        );

        ServiceRegister::registerService(
            API\Http\Proxy::CLASS_NAME,
            function () {
                /** @var HttpClient $httpClient */
                $httpClient = ServiceRegister::getService(HttpClient::CLASS_NAME);

                return new API\Http\Proxy($httpClient);
            }
        );

        ServiceRegister::registerService(
            Form\Http\Proxy::CLASS_NAME,
            function () {
                /** @var HttpClient $httpClient */
                $httpClient = ServiceRegister::getService(HttpClient::CLASS_NAME);
                /** @var AuthorizationService $authService */
                $authService = ServiceRegister::getService(AuthorizationService::CLASS_NAME);

                return new Form\Http\Proxy($httpClient, $authService);
            }
        );

        ServiceRegister::registerService(
            WebHookEvent\Http\Proxy::CLASS_NAME,
            function () {
                /** @var HttpClient $httpClient */
                $httpClient = ServiceRegister::getService(HttpClient::CLASS_NAME);
                /** @var AuthorizationService $authService */
                $authService = ServiceRegister::getService(AuthorizationService::CLASS_NAME);

                return new WebHookEvent\Http\Proxy($httpClient, $authService);
            }
        );

        ServiceRegister::registerService(
            Survey\Http\Proxy::CLASS_NAME,
            function () {
                /** @var HttpClient $httpClient */
                $httpClient = ServiceRegister::getService(HttpClient::CLASS_NAME);
                /** @var AuthorizationService $authService */
                $authService = ServiceRegister::getService(AuthorizationService::CLASS_NAME);

                return new Survey\Http\Proxy($httpClient, $authService);
            }
        );

        ServiceRegister::registerService(
            DynamicContent\Http\Proxy::CLASS_NAME,
            function () {
                /** @var HttpClient $httpClient */
                $httpClient = ServiceRegister::getService(HttpClient::CLASS_NAME);
                /** @var AuthorizationService $authService */
                $authService = ServiceRegister::getService(AuthorizationService::CLASS_NAME);

                return new DynamicContent\Http\Proxy($httpClient, $authService);
            }
        );

        ServiceRegister::registerService(
            Mailing\Http\Proxy::CLASS_NAME,
            function () {
                /** @var HttpClient $httpClient */
                $httpClient = ServiceRegister::getService(HttpClient::CLASS_NAME);
                /** @var AuthorizationService $authService */
                $authService = ServiceRegister::getService(AuthorizationService::CLASS_NAME);

                return new Mailing\Http\Proxy($httpClient, $authService);
            }
        );

        ServiceRegister::registerService(
            Report\Http\Proxy::CLASS_NAME,
            function () {
                /** @var HttpClient $httpClient */
                $httpClient = ServiceRegister::getService(HttpClient::CLASS_NAME);
                /** @var AuthorizationService $authService */
                $authService = ServiceRegister::getService(AuthorizationService::CLASS_NAME);

                return new Report\Http\Proxy($httpClient, $authService);
            }
        );

        ServiceRegister::registerService(
            TokenProxy::CLASS_NAME,
            function () {
                /** @var HttpClient $httpClient */
                $httpClient = ServiceRegister::getService(HttpClient::CLASS_NAME);
                /** @var AuthorizationService $authService */
                $authService = ServiceRegister::getService(AuthorizationService::CLASS_NAME);

                return new TokenProxy($httpClient, $authService);
            }
        );

        ServiceRegister::registerService(
            Stats\Http\Proxy::CLASS_NAME,
            function () {
                /** @var HttpClient $httpClient */
                $httpClient = ServiceRegister::getService(HttpClient::CLASS_NAME);
                /** @var AuthorizationService $authService */
                $authService = ServiceRegister::getService(AuthorizationService::CLASS_NAME);

                return new Stats\Http\Proxy($httpClient, $authService);
            }
        );

        ServiceRegister::registerService(
            Automation\AbandonedCart\Http\Proxy::CLASS_NAME,
            function () {
                /** @var HttpClient $httpClient */
                $httpClient = ServiceRegister::getService(HttpClient::CLASS_NAME);
                /** @var AuthorizationService $authService */
                $authService = ServiceRegister::getService(AuthorizationService::CLASS_NAME);

                return new Automation\AbandonedCart\Http\Proxy($httpClient, $authService);
            }
        );

        ServiceRegister::registerService(
            Multistore\Http\Proxy::CLASS_NAME,
            function () {
                /** @var HttpClient $httpClient */
                $httpClient = ServiceRegister::getService(HttpClient::CLASS_NAME);
                /** @var AuthorizationService $authService */
                $authService = ServiceRegister::getService(AuthorizationService::CLASS_NAME);

                return new Multistore\Http\Proxy($httpClient, $authService);
            }
        );

        ServiceRegister::registerService(
            Multistore\AbandonedCart\Http\Proxy::CLASS_NAME,
            function () {
                /** @var HttpClient $httpClient */
                $httpClient = ServiceRegister::getService(HttpClient::CLASS_NAME);
                /** @var AuthorizationService $authService */
                $authService = ServiceRegister::getService(AuthorizationService::CLASS_NAME);

                return new Multistore\AbandonedCart\Http\Proxy($httpClient, $authService);
            }
        );
    }

    /**
     * Initializes event listeners.
     */
    protected static function initEvents()
    {
        parent::initEvents();

        SyncSettingsEventBus::getInstance()->when(
            EnabledServicesSetEvent::CLASS_NAME,
            EnabledSyncServicesChangeRecorder::CLASS_NAME . '::handle'
        );

        SyncSettingsEventBus::getInstance()->when(
            EnabledServicesSetEvent::CLASS_NAME,
            SyncSettingsUpdater::CLASS_NAME . '::handle'
        );

        TaskCompletedEventBus::getInstance()->when(
            InitialSyncCompletedEvent::CLASS_NAME,
            InitialSyncCompletedListener::CLASS_NAME . '::handle'
        );

        AuthorizationEventBus::getInstance()->when(
            ConnectionLostEvent::CLASS_NAME,
            ConnectionLostEventListener::CLASS_NAME . '::handle'
        );

        AbandonedCartEventsBus::getInstance()->when(
            AbandonedCartConvertedEvent::CLASS_NAME,
            array(new AbandonedCartConvertedEventListener(), 'handle')
        );

        AbandonedCartEventsBus::getInstance()->when(
            AbandonedCartEvent::CLASS_NAME,
            array(new AbandonedCartEventListener(), 'handle')
        );

        AbandonedCartEventsBus::getInstance()->when(
            AbandonedCartUpdatedEvent::CLASS_NAME,
            array(new AbandonedCartUpdatedEventListener(), 'handle')
        );

        QueueItemStateTransitionEventBus::getInstance()->when(
            QueueItemEnqueuedEvent::CLASS_NAME,
            array(new AutomationRecordStatusListener(), 'onEnqueue')
        );

        QueueItemStateTransitionEventBus::getInstance()->when(
            QueueItemFinishedEvent::CLASS_NAME,
            array(new AutomationRecordStatusListener(), 'onComplete')
        );

        QueueItemStateTransitionEventBus::getInstance()->when(
            QueueItemAbortedEvent::CLASS_NAME,
            array(new AutomationRecordStatusListener(), 'onAbort')
        );

        QueueItemStateTransitionEventBus::getInstance()->when(
            QueueItemFailedEvent::CLASS_NAME,
            array(new AutomationRecordStatusListener(), 'onFail')
        );
    }

    /**
     * Initializes web hook handlers.
     */
    protected static function initWebHookHandlers()
    {
        ServiceRegister::registerService(
            Handler::CLASS_NAME,
            function () {
                return new Handler();
            }
        );
    }

    /**
     * Initializes pipelines with filters.
     */
    protected static function initPipelines()
    {
        AbandonedCartCreatePipeline::append(new CartCreatedFilter());
        AbandonedCartCreatePipeline::append(new AlreadyProcessedFilter());
        AbandonedCartCreatePipeline::append(new CartFilter());
        AbandonedCartCreatePipeline::append(new RecordFilter());
        AbandonedCartTriggerPipeline::append(new CartCreatedFilter());
        AbandonedCartTriggerPipeline::append(new AlreadyProcessedFilter());
        AbandonedCartTriggerPipeline::append(new ReceiverFilter());

        FilterChain::append(new AutomationFilter());
        FilterChain::append(new CartDataFilter());
        FilterChain::append(new Multistore\AbandonedCart\Tasks\Trigger\Filter\ReceiverFilter());
    }
}