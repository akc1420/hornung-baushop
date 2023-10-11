<?php


namespace Crsw\CleverReachOfficial\Components\Utility;

use Crsw\CleverReachOfficial\Components\AuthorizationHandler\UserAuthorizedEvent;
use Crsw\CleverReachOfficial\Components\AuthorizationHandler\UserAuthorizedListener;
use Crsw\CleverReachOfficial\Components\Entities\StateTransitionRecord;
use Crsw\CleverReachOfficial\Components\FormListeners\TransformFormContent;
use Crsw\CleverReachOfficial\Components\OfflineMode\OfflineModeTickHandler;
use Crsw\CleverReachOfficial\Components\StateTransition\QueueItemFailedListener;
use Crsw\CleverReachOfficial\Components\StateTransition\QueueItemFinishedListener;
use Crsw\CleverReachOfficial\Components\TaskStatusProvider\TaskStatusProvider;
use Crsw\CleverReachOfficial\Components\Webhooks\Listeners\ReceiverCreatedListener;
use Crsw\CleverReachOfficial\Components\Webhooks\Listeners\ReceiverSubscribedListener;
use Crsw\CleverReachOfficial\Components\Webhooks\Listeners\ReceiverUnsubscribedListener;
use Crsw\CleverReachOfficial\Components\Webhooks\Listeners\ReceiverUpdatedListener;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Contracts\AuthorizationService
    as BaseAuthorizationService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Contracts\RegistrationService as BaseRegistrationService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\DTO\AuthInfo;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\DTO\UserInfo;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Events\AuthorizationEventBus;
use Crsw\CleverReachOfficial\Core\BusinessLogic\BootstrapComponent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\DoubleOptIn\Http\Proxy as DoubleOptInProxy;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Http\Proxy as ReceiverProxy;
use Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\Contracts\DynamicContentService as BaseDynamicContentService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Field\Contracts\FieldService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Contracts\FormService as BaseFormService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Entities\Form;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Events\BeforeFormCacheCreatedEvent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Events\BeforeFormCacheUpdatedEvent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Events\FormEventBus;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\FormEventsService as BaseFormEventsService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Group\Contracts\GroupService as BaseGroupService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Language\Contracts\TranslationService as BaseTranslationService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Mailing\Contracts\DefaultMailingService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Entities\AutomationRecord;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Entities\CartAutomation;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Interfaces\Required\AutomationWebhooksService
    as BaseAutomationWebhooksService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Interfaces\Required\CartAutomationTriggerService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Tasks\Trigger\Filter\FilterChain;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Events\ReceiverCreatedEvent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Events\ReceiverEventBus;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Events\ReceiverSubscribedEvent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Events\ReceiverUnsubscribedEvent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Events\ReceiverUpdatedEvent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Merger\MergerRegistry;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\ReceiverEventsService as BaseReceiverEventsService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Scheduler\Models\Schedule;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Scheduler\ScheduleTickHandler;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Segment\Contracts\SegmentService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Stats\Entity\Stats;
use Crsw\CleverReachOfficial\Core\BusinessLogic\SupportConsole\Contracts\SupportService as BaseSupportService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Survey\DTO\Survey;
use Crsw\CleverReachOfficial\Core\BusinessLogic\SyncSettings\Contracts\SyncSettingsService as BaseSyncSettingsService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\SyncSettings\DTO\SyncSettings;
use Crsw\CleverReachOfficial\Core\BusinessLogic\SyncSettings\Entities\EnabledServicesChangeLog;
use Crsw\CleverReachOfficial\Core\BusinessLogic\SyncSettings\Events\EnabledServicesSetEvent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\SyncSettings\Events\SyncSettingsEventBus;
use Crsw\CleverReachOfficial\Core\BusinessLogic\SyncSettings\Listeners\InitialSyncTaskEnqueuer;
use Crsw\CleverReachOfficial\Core\BusinessLogic\SyncSettings\Listeners\SecondarySyncTaskEnqueuer;
use Crsw\CleverReachOfficial\Core\BusinessLogic\TaskExecution\Events\QueueItemFailedEvent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\TaskExecution\Events\QueueItemFinishedEvent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\TaskExecution\Events\QueueItemStateTransitionEventBus;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\ConfigEntity;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\Http\AutoConfiguration;
use Crsw\CleverReachOfficial\Core\Infrastructure\Http\HttpClient;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryClassException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\RepositoryRegistry;
use Crsw\CleverReachOfficial\Core\Infrastructure\Serializer\Concrete\JsonSerializer;
use Crsw\CleverReachOfficial\Core\Infrastructure\Serializer\Serializer;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Process;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueItem;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\TaskEvents\TickEvent;
use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Events\EventBus;
use Crsw\CleverReachOfficial\Entity\Automation\Repositories\AutomationRepository;
use Crsw\CleverReachOfficial\Entity\Base\Repositories\BaseRepository;
use Crsw\CleverReachOfficial\Entity\Form\Repositories\FormRepository;
use Crsw\CleverReachOfficial\Entity\Queue\Repositories\QueueItemRepository;
use Crsw\CleverReachOfficial\Entity\Schedule\Repositories\ScheduleRepository;
use Crsw\CleverReachOfficial\Entity\Stats\Repositories\StatsRepository;
use Crsw\CleverReachOfficial\Mergers\BuyerMerger;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Authorization\AuthorizationService;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Automation\AutomationWebhooksService;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Automation\Entities\RecoveryRecord;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Automation\Filters\SalesChannelFilter;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Automation\Filters\SubscriberFilter;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Automation\RecoveryRecordService;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Automation\TriggerService;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Configuration\ConfigService;
use Crsw\CleverReachOfficial\Service\BusinessLogic\DoubleOptIn\DoubleOptInRecordService;
use Crsw\CleverReachOfficial\Service\BusinessLogic\DoubleOptIn\Entities\DoubleOptInRecord;
use Crsw\CleverReachOfficial\Service\BusinessLogic\DynamicContent\DynamicContentService;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Events\FormEventsService;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Events\ReceiverEventsService;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Form\FormService;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Group\GroupService;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Mailing\MailingService;
use Crsw\CleverReachOfficial\Service\BusinessLogic\OfflineMode\OfflineModeCheckService;
use Crsw\CleverReachOfficial\Service\BusinessLogic\ReceiverFields\ReceiverFieldService;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Registration\RegistrationService;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Segments\SegmentsService;
use Crsw\CleverReachOfficial\Service\BusinessLogic\StateTransition\StateTransitionRecordService;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Support\SupportService;
use Crsw\CleverReachOfficial\Service\BusinessLogic\SyncSettings\BuyerSyncSettings;
use Crsw\CleverReachOfficial\Service\BusinessLogic\SyncSettings\ContactSyncSettings;
use Crsw\CleverReachOfficial\Service\BusinessLogic\SyncSettings\SubscriberSyncSettings;
use Crsw\CleverReachOfficial\Service\BusinessLogic\SyncSettings\SyncSettingsService;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Translation\TranslationService;
use Crsw\CleverReachOfficial\Service\Infrastructure\Http1Client;

/**
 * Class Bootstrap
 *
 * @package Crsw\CleverReachOfficial\Components\Utility
 */
class Bootstrap extends BootstrapComponent
{
    /**
     * Initializes services, repositories and proxies.
     */
    public static function register(): void
    {
        try {
            self::initServices();
            self::initRepositories();
            self::initProxies();
        } catch (RepositoryClassException $e) {
        }
    }

    /**
     * Initializes services and utilities.
     */
    public static function initServices(): void
    {
        parent::initServices();

        ServiceRegister::registerService(
            BaseAuthorizationService::class,
            static function () {
                return new AuthorizationService();
            }
        );

        ServiceRegister::registerService(
            Configuration::class,
            static function () {
                return ConfigService::getInstance();
            }
        );

        ServiceRegister::registerService(
            BaseRegistrationService::class,
            static function () {
                return new RegistrationService();
            }
        );

        ServiceRegister::registerService(
            Serializer::class,
            static function () {
                return new JsonSerializer();
            }
        );

        ServiceRegister::registerService(
            AutoConfiguration::class,
            static function () {
                /** @noinspection PhpParamsInspection */
                return new AutoConfiguration(
                    ServiceRegister::getService(Configuration::class),
                    ServiceRegister::getService(HttpClient::class)
                );
            }
        );

        ServiceRegister::registerService(
            BaseGroupService::class,
            static function () {
                return new GroupService();
            }
        );

        ServiceRegister::registerService(
            FieldService::class,
            static function () {
                return new ReceiverFieldService();
            }
        );

        ServiceRegister::registerService(
            BaseTranslationService::class,
            static function () {
                return new TranslationService();
            }
        );

        ServiceRegister::registerService(
            SegmentService::class,
            static function () {
                return new SegmentsService();
            }
        );

        ServiceRegister::registerService(
            BaseDynamicContentService::class,
            static function () {
                return new DynamicContentService();
            }
        );

        ServiceRegister::registerService(
            BaseReceiverEventsService::class,
            static function () {
                return new ReceiverEventsService();
            }
        );

        MergerRegistry::register(
            BuyerMerger::class,
            static function () {
                return BuyerMerger::getInstance();
            }
        );

        ServiceRegister::registerService(
            BaseSyncSettingsService::class,
            static function () {
                return new SyncSettingsService();
            }
        );

        ServiceRegister::registerService(
            SubscriberSyncSettings::class,
            static function () {
                return new SubscriberSyncSettings();
            }
        );

        ServiceRegister::registerService(
            BuyerSyncSettings::class,
            static function () {
                return new BuyerSyncSettings();
            }
        );

        ServiceRegister::registerService(
            ContactSyncSettings::class,
            static function () {
                return new ContactSyncSettings();
            }
        );

        ServiceRegister::registerService(
            BaseFormService::class,
            static function () {
                return new FormService();
            }
        );

        ServiceRegister::registerService(
            DefaultMailingService::class,
            static function () {
                return new MailingService();
            }
        );

        ServiceRegister::registerService(
            BaseFormEventsService::class,
            static function () {
                return new FormEventsService();
            }
        );

        ServiceRegister::registerService(
            BaseSupportService::class,
            static function () {
                return new SupportService();
            }
        );

        ServiceRegister::registerService(
            TaskStatusProvider::class,
            static function () {
                return new TaskStatusProvider();
            }
        );

        ServiceRegister::registerService(
            StateTransitionRecordService::class,
            static function () {
                return new StateTransitionRecordService();
            }
        );

        ServiceRegister::registerService(
            OfflineModeCheckService::class,
            static function () {
                return new OfflineModeCheckService();
            }
        );

        ServiceRegister::registerService(
            BaseAutomationWebhooksService::class,
            static function () {
                return new AutomationWebhooksService();
            }
        );

        ServiceRegister::registerService(
            CartAutomationTriggerService::class,
            static function () {
                return new TriggerService();
            }
        );

        ServiceRegister::registerService(
            RecoveryRecordService::class,
            static function () {
                return new RecoveryRecordService();
            }
        );

        ServiceRegister::registerService(
            DoubleOptInRecordService::class,
            static function () {
                return new DoubleOptInRecordService();
            }
        );

    }

    /**
     * Initializes repositories.
     *
     * @throws RepositoryClassException
     */
    public static function initRepositories(): void
    {
        parent::initRepositories();

        RepositoryRegistry::registerRepository(ConfigEntity::class, BaseRepository::getClassName());
        RepositoryRegistry::registerRepository(QueueItem::class, QueueItemRepository::getClassName());
        RepositoryRegistry::registerRepository(Process::class, BaseRepository::getClassName());
        RepositoryRegistry::registerRepository(AuthInfo::class, BaseRepository::getClassName());
        RepositoryRegistry::registerRepository(UserInfo::class, BaseRepository::getClassName());
        RepositoryRegistry::registerRepository(SyncSettings::class, BaseRepository::getClassName());
        RepositoryRegistry::registerRepository(Form::class, FormRepository::getClassName());
        RepositoryRegistry::registerRepository(Survey::class, BaseRepository::getClassName());
        RepositoryRegistry::registerRepository(
            EnabledServicesChangeLog::getClassName(),
            BaseRepository::getClassName()
        );
        RepositoryRegistry::registerRepository(Schedule::getClassName(), ScheduleRepository::getClassName());
        RepositoryRegistry::registerRepository(Stats::getClassName(), StatsRepository::getClassName());
        RepositoryRegistry::registerRepository(StateTransitionRecord::class, BaseRepository::getClassName());
        RepositoryRegistry::registerRepository(CartAutomation::class, AutomationRepository::getClassName());
        RepositoryRegistry::registerRepository(AutomationRecord::class, AutomationRepository::getClassName());
        RepositoryRegistry::registerRepository(RecoveryRecord::class, AutomationRepository::getClassName());
        RepositoryRegistry::registerRepository(DoubleOptInRecord::class, AutomationRepository::getClassName());
    }

	/**
	 * Initializes proxies.
	 */
	public static function initProxies(): void
	{
		parent::initProxies();

		ServiceRegister::registerService(
			DoubleOptInProxy::class,
			static function () {
				/** @noinspection PhpParamsInspection */
				return new DoubleOptInProxy(
					ServiceRegister::getService(HttpClient::class),
					ServiceRegister::getService(BaseAuthorizationService::class)
				);
			}
		);

		ServiceRegister::registerService(
			ReceiverProxy::class,
			function () {
				/** @noinspection PhpParamsInspection */
				return new ReceiverProxy(
					new Http1Client(),
					ServiceRegister::getService(AuthorizationService::CLASS_NAME)
				);
			}
		);
	}

    /**
     * Initializes event listeners.
     */
    public static function initEvents(): void
    {
        parent::initEvents();

        SyncSettingsEventBus::getInstance()->when(
            EnabledServicesSetEvent::class,
            SecondarySyncTaskEnqueuer::class . '::handle'
        );

        SyncSettingsEventBus::getInstance()->when(
            EnabledServicesSetEvent::class,
            InitialSyncTaskEnqueuer::class . '::handle'
        );

        ReceiverEventBus::getInstance()->when(
            ReceiverCreatedEvent::class,
            ReceiverCreatedListener::class . '::handle'
        );

        ReceiverEventBus::getInstance()->when(
            ReceiverUpdatedEvent::class,
            ReceiverUpdatedListener::class . '::handle'
        );

        ReceiverEventBus::getInstance()->when(
            ReceiverSubscribedEvent::class,
            ReceiverSubscribedListener::class . '::handle'
        );

        ReceiverEventBus::getInstance()->when(
            ReceiverUnsubscribedEvent::class,
            ReceiverUnsubscribedListener::class . '::handle'
        );

        AuthorizationEventBus::getInstance()->when(
            UserAuthorizedEvent::class,
            UserAuthorizedListener::class . '::handle'
        );

        EventBus::getInstance()->when(
            TickEvent::class,
            [new ScheduleTickHandler(), 'handle']
        );

        QueueItemStateTransitionEventBus::getInstance()->when(
            QueueItemFinishedEvent::class,
            QueueItemFinishedListener::class . '::handle'
        );

        QueueItemStateTransitionEventBus::getInstance()->when(
            QueueItemFailedEvent::class,
            QueueItemFailedListener::class . '::handle'
        );

        EventBus::getInstance()->when(
            TickEvent::class,
            OfflineModeTickHandler::class . '::handle'
        );

        FormEventBus::getInstance()->when(
            BeforeFormCacheUpdatedEvent::class,
            TransformFormContent::class . '::handle'
        );

        FormEventBus::getInstance()->when(
            BeforeFormCacheCreatedEvent::class,
            TransformFormContent::class . '::handle'
        );
    }

    /**
     * Initializes pipelines with filters.
     */
    protected static function initPipelines(): void
    {
        parent::initPipelines();

        FilterChain::append(new SalesChannelFilter());
        FilterChain::append(new SubscriberFilter());
    }
}
