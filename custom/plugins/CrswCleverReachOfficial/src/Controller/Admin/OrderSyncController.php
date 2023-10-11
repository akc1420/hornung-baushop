<?php


namespace Crsw\CleverReachOfficial\Controller\Admin;

use Crsw\CleverReachOfficial\Components\Tasks\OrdersOlderThanOneYearSyncTask;
use Crsw\CleverReachOfficial\Components\TaskStatusProvider\TaskStatusProvider;
use Crsw\CleverReachOfficial\Components\Utility\Bootstrap;
use Crsw\CleverReachOfficial\Components\Utility\Initializer;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Order\Contracts\OrderService;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueItem;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueService;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Configuration\ConfigService;
use Exception;
use Shopware\Core\Framework\Api\Response\JsonApiResponse;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class OrderSyncController
 *
 * @package Crsw\CleverReachOfficial\Controller\Admin
 */
class OrderSyncController extends AbstractController
{
    /**
     * @var QueueService
     */
    private $queueService;
    /**
     * @var OrderService
     */
    private $orderService;

    /**
     * OrderSyncController constructor.
     *
     * @param Initializer $initializer
     * @param QueueService $queueService
     * @param OrderService $orderService
     */
    public function __construct(Initializer $initializer, QueueService $queueService, OrderService $orderService)
    {
        Bootstrap::init();
        $initializer->registerServices();
        $this->queueService = $queueService;
        $this->orderService = $orderService;
    }

    /**
     * Enqueues OrdersOlderThanOneYearSyncTask.
     *
     * @RouteScope(scopes={"api"})
     * @Route(path="api/v{version}/cleverreach/orderSync", name="api.cleverreach.orderSync", methods={"GET", "POST"})
     * @Route(path="api/cleverreach/orderSync", name="api.cleverreach.orderSync.new", methods={"GET", "POST"})
     *
     * @return JsonApiResponse
     */
    public function includeOrders(): JsonApiResponse
    {
        if (!$this->orderService->canSynchronizeOrderItems()) {
            return new JsonApiResponse(['success' => false]);
        }

        try {
            if ($this->areAllOrdersSynced()) {
                return new JsonApiResponse(['success' => false]);
            }

            $this->getConfigService()->setAllOrdersSynced();
            $this->queueService->enqueue(
                $this->getConfigService()->getDefaultQueueName(),
                new OrdersOlderThanOneYearSyncTask()
            );

            return new JsonApiResponse(['success' => true]);
        } catch (Exception $e) {
            Logger::logError($e->getMessage());
            return new JsonApiResponse(['success' => false]);
        }
    }

    /**
     * Gets order data.
     *
     * @RouteScope(scopes={"api"})
     * @Route(path="api/v{version}/cleverreach/orderSync/get",
     *     name="api.cleverreach.orderSync.get", methods={"GET", "POST"})
     * @Route(path="api/cleverreach/orderSync/get",
     *     name="api.cleverreach.orderSync.get.new", methods={"GET", "POST"})
     *
     * @return JsonApiResponse
     */
    public function getOrdersData(): JsonApiResponse
    {
        try {
            $allOrdersSynced = $this->areAllOrdersSynced();

            $buyerEnabled = $this->orderService->canSynchronizeOrderItems();
            $data['enableOrderSync'] = !$allOrdersSynced && $buyerEnabled;

            if ($allOrdersSynced) {
                $queueItem = $this->queueService->findLatestByType('OrdersOlderThanOneYearSyncTask');

                if ($queueItem && $queueItem->getStatus() === QueueItem::FAILED) {
                    $data['error'] = true;
                }

                $data['taskStatus'] = $queueItem->getStatus();
                $data['lastSyncTime'] = $queueItem ? date('d-m-Y H:i', $queueItem->getFinishTimestamp()) : '';
            }

            return new JsonApiResponse($data);
        } catch (QueryFilterInvalidParamException $e) {
            Logger::logError($e->getMessage());
            return new JsonApiResponse(['errorMessage' => 'Error occurred while getting order data.']);
        }
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(path="api/v{version}/cleverreach/orderSync/progress",
     *     name="api.cleverreach.orderSync.progress", methods={"GET", "POST"})
     * @Route(path="api/cleverreach/orderSync/progress",
     *     name="api.cleverreach.orderSync.progress.new", methods={"GET", "POST"})
     *
     * @return JsonApiResponse
     */
    public function getOrderProgress(): JsonApiResponse
    {
        $queueItem = $this->queueService->findLatestByType('OrdersOlderThanOneYearSyncTask');

        if (!$queueItem) {
            return new JsonApiResponse(['success' => false]);
        }

        if ($queueItem->getStatus() === QueueItem::FAILED) {
            try {
                return new JsonApiResponse($this->getTaskStatusProvider()->getTaskData($queueItem));
            } catch (Exception $e) {
                Logger::logError($e->getMessage());
                return new JsonApiResponse(['status' => 'Unable to get task status.']);
            }
        }

        $progress = $queueItem->getProgressBasePoints() / 100;

        return new JsonApiResponse(['progressValue' => $progress]);
    }

    /**
     * @return bool
     * @throws QueryFilterInvalidParamException
     */
    private function areAllOrdersSynced(): bool
    {
        return $this->getConfigService()->getAllOrdersSynced()
            && $this->queueService->findLatestByType('OrdersOlderThanOneYearSyncTask')
            && $this->queueService->findLatestByType('OrdersOlderThanOneYearSyncTask')->getStatus() === QueueItem::COMPLETED;
    }

    /**
     * @return ConfigService
     */
    private function getConfigService(): ConfigService
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
