<?php


namespace Crsw\CleverReachOfficial\Controller\Admin;

use Crsw\CleverReachOfficial\Components\TaskStatusProvider\TaskStatusProvider;
use Crsw\CleverReachOfficial\Components\Utility\Bootstrap;
use Crsw\CleverReachOfficial\Components\Utility\Initializer;
use Crsw\CleverReachOfficial\Core\BusinessLogic\SecondarySynchronization\Tasks\Composite\SecondarySyncTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\SyncSettings\Contracts\SyncSettingsService;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueItem;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueService;
use Crsw\CleverReachOfficial\Exceptions\InvalidSyncSettingsException;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Customer\BuyerService;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Customer\ContactService;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Customer\SubscriberService;
use Crsw\CleverReachOfficial\Service\BusinessLogic\SyncSettings\BuyerSyncSettings;
use Crsw\CleverReachOfficial\Service\BusinessLogic\SyncSettings\ContactSyncSettings;
use Crsw\CleverReachOfficial\Service\BusinessLogic\SyncSettings\SubscriberSyncSettings;
use Exception;
use Shopware\Core\Framework\Api\Response\JsonApiResponse;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class SyncSettingsController
 *
 * @package Crsw\CleverReachOfficial\Controller\Admin
 */
class SyncSettingsController extends AbstractController
{
    /**
     * @var QueueService
     */
    private $queueService;
    /**
     * @var SyncSettingsService
     */
    private $syncSettingsService;
    /**
     * @var SubscriberService
     */
    private $subscriberService;
    /**
     * @var BuyerService
     */
    private $buyerService;
    /**
     * @var ContactService
     */
    private $contactService;

    /**
     * SyncSettingsController constructor.
     *
     * @param Initializer $initializer
     * @param QueueService $queueService
     * @param SyncSettingsService $syncSettingsService
     * @param SubscriberService $subscriberService
     * @param BuyerService $buyerService
     * @param ContactService $contactService
     */
    public function __construct(
        Initializer $initializer,
        QueueService $queueService,
        SyncSettingsService $syncSettingsService,
        SubscriberService $subscriberService,
        BuyerService $buyerService,
        ContactService $contactService
    ) {
        Bootstrap::init();
        $initializer->registerServices();
        $this->queueService = $queueService;
        $this->syncSettingsService = $syncSettingsService;
        $this->subscriberService = $subscriberService;
        $this->buyerService = $buyerService;
        $this->contactService = $contactService;
    }

    /**
     * Save sync settings.
     *
     * @RouteScope(scopes={"api"})
     * @Route(path="api/v{version}/cleverreach/syncsettings/save",
     *      name="api.cleverreach.syncsettings.save", methods={"GET", "POST"})
     * @Route(path="api/cleverreach/syncsettings/save",
     *      name="api.cleverreach.syncsettings.save.new", methods={"GET", "POST"})
     *
     * @param Request $request
     *
     * @return JsonApiResponse
     */
    public function save(Request $request): JsonApiResponse
    {
        if ($this->isSecondarySyncTaskRunning()) {
            return new JsonApiResponse(['success' => false]);
        }

        try {
            $this->saveServices($request);
            $success = true;
        } catch (InvalidSyncSettingsException $e) {
            $success = false;
        }

        return new JsonApiResponse(['success' => $success]);
    }

    /**
     * Forces secondary sync task.
     *
     * @RouteScope(scopes={"api"})
     * @Route(path="api/v{version}/cleverreach/syncsettings/force",
     *      name="api.cleverreach.syncsettings.force", methods={"GET", "POST"})
     * @Route(path="api/cleverreach/syncsettings/force",
     *      name="api.cleverreach.syncsettings.force.new", methods={"GET", "POST"})
     *
     * @return JsonApiResponse
     */
    public function forceSync(): JsonApiResponse
    {
        try {
            if ($this->isSecondarySyncTaskRunning()) {
                return new JsonApiResponse(['success' => false]);
            }

            $this->queueService->enqueue($this->getConfigService()->getDefaultQueueName(), new SecondarySyncTask());

            return new JsonApiResponse(['success' => true]);
        } catch (QueueStorageUnavailableException $e) {
            Logger::logError($e->getMessage());
            return new JsonApiResponse(['success' => false]);
        }
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(path="api/v{version}/cleverreach/syncsettings/getSyncStatus",
     *     name="api.cleverreach.syncsettings.getSyncStatus", methods={"GET"})
     * @Route(path="api/cleverreach/syncsettings/getSyncStatus",
     *     name="api.cleverreach.syncsettings.getSyncStatus.new", methods={"GET"})
     *
     * @return JsonApiResponse
     */
    public function getSyncStatus(): JsonApiResponse
    {
        $queueItem = $this->queueService->findLatestByType('SecondarySyncTask');

        if (!$queueItem) {
            return new JsonApiResponse(['status' => 'not created']);
        }

        try {
            return new JsonApiResponse($this->getTaskStatusProvider()->getTaskData($queueItem));
        } catch (Exception $e) {
            Logger::logError($e->getMessage());
            return new JsonApiResponse(['status' => 'Unable to get task status.']);
        }
    }

    /**
     * Gets number of subscribers, buyers and contacts.
     *
     * @RouteScope(scopes={"api"})
     * @Route(path="api/v{version}/cleverreach/syncsettings/getNumberOfReceivers",
     *     name="api.cleverreach.syncsettings.getNumberOfReceivers", methods={"GET"})
     * @Route(path="api/cleverreach/syncsettings/getNumberOfReceivers",
     *     name="api.cleverreach.syncsettings.getNumberOfReceivers.new", methods={"GET"})
     *
     * @return JsonApiResponse
     */
    public function getNumberOfReceivers(): JsonApiResponse
    {
        $data['subscribers'] = $this->subscriberService->count();
        $data['buyers'] = $this->buyerService->count();
        $data['contacts'] = $this->contactService->count();

        return new JsonApiResponse($data);
    }

    /**
     * Get enabled services.
     *
     * @RouteScope(scopes={"api"})
     * @Route(path="api/v{version}/cleverreach/syncsettings/getservices",
     *      name="api.cleverreach.syncsettings.getservices", methods={"GET"})
     * @Route(path="api/cleverreach/syncsettings/getservices",
     *      name="api.cleverreach.syncsettings.getservices.new", methods={"GET"})
     *
     * @return JsonApiResponse
     */
    public function getServices(): JsonApiResponse
    {
        return new JsonApiResponse($this->getEnabledReceiverGroups());
    }

    /**
     * @return bool
     */
    private function isSecondarySyncTaskRunning(): bool
    {
        $secondarySyncTask = $this->queueService->findLatestByType('SecondarySyncTask');

        return $secondarySyncTask && in_array(
            $secondarySyncTask->getStatus(),
            [QueueItem::CREATED, QueueItem::QUEUED, QueueItem::IN_PROGRESS],
            true
        );
    }

    /**
     * @param Request $request
     *
     * @throws InvalidSyncSettingsException
     */
    private function saveServices(Request $request): void
    {
        $settings = $request->get('syncSettings');
        $syncSettings = explode(', ', $settings);

        $this->validate($syncSettings);

        $enabledServices = [
            ServiceRegister::getService(SubscriberSyncSettings::class),
        ];

        if (in_array('buyers', $syncSettings, true)) {
            $enabledServices[] = ServiceRegister::getService(BuyerSyncSettings::class);
        }

        if (in_array('contacts', $syncSettings, true)) {
            $enabledServices[] = ServiceRegister::getService(ContactSyncSettings::class);
        }

        $this->syncSettingsService->setEnabledServices($enabledServices);
    }

    /**
     * @param array $syncSettings
     *
     * @throws InvalidSyncSettingsException
     */
    private function validate(array $syncSettings): void
    {
        if (!in_array('subscribers', $syncSettings, true)) {
            throw new InvalidSyncSettingsException('Subscribers must be selected.');
        }

        if (in_array('contacts', $syncSettings, true) &&
            !in_array('buyers', $syncSettings, true)) {
            throw new InvalidSyncSettingsException('Contacts cannot be selected without buyers.');
        }
    }

    /**
     * @return bool[]
     */
    private function getEnabledReceiverGroups(): array
    {
        $buyer = false;
        $contact = false;
        $enabledServices = $this->syncSettingsService->getEnabledServices();

        foreach ($enabledServices as $service) {
            if ($service->getUuid() === 'buyer-service') {
                $buyer = true;
            }

            if ($service->getUuid() === 'contact-service') {
                $contact = true;
            }
        }

        return [
            'subscribers' => true,
            'buyers' => $buyer,
            'contacts' => $contact
        ];
    }

    /**
     * @return Configuration
     */
    private function getConfigService(): Configuration
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(Configuration::class);
    }

    /**
     * @return TaskStatusProvider
     */
    private function getTaskStatusProvider(): TaskStatusProvider
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(TaskStatusProvider::class);
    }
}
