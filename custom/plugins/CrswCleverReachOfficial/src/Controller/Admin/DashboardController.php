<?php


namespace Crsw\CleverReachOfficial\Controller\Admin;

use Crsw\CleverReachOfficial\Components\Utility\Bootstrap;
use Crsw\CleverReachOfficial\Components\Utility\Initializer;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Contracts\AuthorizationService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveUserInfoException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Dashboard\Contracts\DashboardService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Contracts\FormService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Group\Contracts\GroupService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\InitialSynchronization\Tasks\Composite\InitialSyncTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\PaymentPlan\Contracts\PaymentPlanService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\ReceiverSyncTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\SubscribeReceiverTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\UnsubscribeReceiverTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Segment\Contracts\SegmentService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Stats\Contracts\SnapshotService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Stats\Http\Proxy;
use Crsw\CleverReachOfficial\Core\Infrastructure\Exceptions\BaseException;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Interfaces\RepositoryInterface;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\QueryFilter\Operators;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\QueryFilter\QueryFilter;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\RepositoryRegistry;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueItem;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueService;
use Shopware\Core\Framework\Api\Response\JsonApiResponse;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class DashboardController
 *
 * @package Crsw\CleverReachOfficial\Controller\Admin
 */
class DashboardController extends AbstractController
{
    /**
     * @var DashboardService
     */
    private $dashboardService;
    /**
     * @var GroupService
     */
    private $groupService;
    /**
     * @var SegmentService
     */
    private $segmentsService;
    /**
     * @var FormService
     */
    private $formService;
    /**
     * @var AuthorizationService
     */
    private $authService;
    /**
     * @var PaymentPlanService
     */
    private $paymentPlanService;
    /**
     * @var SnapshotService
     */
    private $snapshotService;
    /**
     * @var Proxy
     */
    private $proxy;
    /**
     * @var QueueService
     */
    private $queueService;

    /**
     * DashboardController constructor.
     *
     * @param Initializer $initializer
     * @param DashboardService $dashboardService
     * @param GroupService $groupService
     * @param SegmentService $segmentsService
     * @param FormService $formService
     * @param AuthorizationService $authService
     * @param PaymentPlanService $paymentPlanService
     * @param SnapshotService $snapshotService
     * @param Proxy $proxy
     * @param QueueService $queueService
     */
    public function __construct(
        Initializer $initializer,
        DashboardService $dashboardService,
        GroupService $groupService,
        SegmentService $segmentsService,
        FormService $formService,
        AuthorizationService $authService,
        PaymentPlanService $paymentPlanService,
        SnapshotService $snapshotService,
        Proxy $proxy,
        QueueService $queueService
    ) {
        Bootstrap::init();
        $initializer->registerServices();
        $this->dashboardService = $dashboardService;
        $this->groupService = $groupService;
        $this->segmentsService = $segmentsService;
        $this->formService = $formService;
        $this->authService = $authService;
        $this->paymentPlanService = $paymentPlanService;
        $this->snapshotService = $snapshotService;
        $this->proxy = $proxy;
        $this->queueService = $queueService;
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(path="api/v{version}/cleverreach/dashboard/getSyncStatistics",
     *      name="api.cleverreach.dashboard.getSyncStatistics", methods={"GET"})
     * @Route(path="api/cleverreach/dashboard/getSyncStatistics",
     *      name="api.cleverreach.dashboard.getSyncStatistics.new", methods={"GET"})
     *
     * @return JsonApiResponse
     */
    public function getSyncStatistics(): JsonApiResponse
    {
        $initialSyncTask = $this->queueService->findLatestByType('InitialSyncTask');

        if (!$initialSyncTask || $initialSyncTask->getStatus() !== QueueItem::COMPLETED) {
            return new JsonApiResponse(['displayStatistics' => false]);
        }

        $data['displayStatistics'] = !$this->dashboardService->isSyncStatisticsDisplayed();

        if (!$data['displayStatistics']) {
            return new JsonApiResponse($data);
        }

        $this->dashboardService->setSyncStatisticsDisplayed(true);

        $data['syncedRecipients'] = $this->dashboardService->getSyncedReceiversCount();
        $data['createdList'] = $this->groupService->getName();
        $data['segments'] = $this->segmentsService->getSegmentNames();
        $data['form'] = $this->formService->getDefaultFormName();

        return new JsonApiResponse($data);
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(path="api/v{version}/cleverreach/dashboard/getAccountData",
     *     name="api.cleverreach.dashboard.getAccountData", methods={"GET"})
     * @Route(path="api/cleverreach/dashboard/getAccountData",
     *     name="api.cleverreach.dashboard.getAccountData.new", methods={"GET"})
     *
     * @return JsonApiResponse
     */
    public function getAccountData(): JsonApiResponse
    {
        $this->authService->getFreshOfflineStatus();
        try {
            $userInfo = $this->authService->getUserInfo();
            $data = [
                'email' => $userInfo->getEmail(),
                'accountId' => $userInfo->getId()
            ];

            return new JsonApiResponse($data);
        } catch (FailedToRetrieveUserInfoException $e) {
            Logger::logError($e->getMessage());
            return new JsonApiResponse();
        }
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(path="api/v{version}/cleverreach/dashboard/getSyncStatus",
     *      name="api.cleverreach.dashboard.getSyncStatus", methods={"GET"})
     * @Route(path="api/cleverreach/dashboard/getSyncStatus",
     *      name="api.cleverreach.dashboard.getSyncStatus.new", methods={"GET"})
     *
     * @return JsonApiResponse
     */
    public function getSyncStatus(): JsonApiResponse
    {
        try {
            $user = $this->authService->getUserInfo();
            $statistics = $this->getReceiverStatistics();

            $data = [
                'totalRecipients' => $statistics['totalRecipients'],
                'lastSync' => $this->getLastSyncDate(),
                'currentPlan' => $this->paymentPlanService->getPlanInfo($user->getId())->__toString(),
                'newRecipients' => $statistics['newRecipients'],
                'unsubscribed' => $statistics['unsubscribed']
            ];

            return new JsonApiResponse($data);
        } catch (FailedToRetrieveUserInfoException $e) {
            Logger::logError($e->getMessage());
            return new JsonApiResponse();
        }
    }

    /**
     * @return array
     */
    private function getReceiverStatistics(): array
    {
        $snapshots = $this->snapshotService->getSnapshots();
        $latestSnapshot = end($snapshots);

        try {
            $stats = $this->proxy->getStats($this->groupService->getId());
            $totalReceiverCount = $stats->getTotalReceiverCount();
        } catch (BaseException $e) {
            $totalReceiverCount = 0;
        }

        return [
            'totalRecipients' => $totalReceiverCount,
            'newRecipients' => $latestSnapshot ? $latestSnapshot->getSubscribed() : 0,
            'unsubscribed' => $latestSnapshot ? $latestSnapshot->getUnsubscribed() : 0
        ];
    }

    /**
     * @return false|string
     */
    private function getLastSyncDate()
    {
        $filter = new QueryFilter();

        try {
            $filter->where('taskType', Operators::IN, [
                SubscribeReceiverTask::getClassName(),
                UnsubscribeReceiverTask::getClassName(),
                ReceiverSyncTask::getClassName(),
                InitialSyncTask::getClassName()
            ]);

            $filter->where('status', Operators::EQUALS, 'completed');

            $filter->orderBy('queueTime', 'DESC');

            $task = $this->getQueueItemRepository()->selectOne($filter);

            return $task ? date('d-m-Y H:i', $task->getFinishTimestamp()) : '';
        } catch (BaseException $e) {
            Logger::logError($e->getMessage());
            return '';
        }
    }

    /**
     * @return RepositoryInterface
     *
     * @throws RepositoryNotRegisteredException
     */
    private function getQueueItemRepository(): RepositoryInterface
    {
        return RepositoryRegistry::getRepository(QueueItem::getClassName());
    }
}
