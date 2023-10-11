<?php


namespace Crsw\CleverReachOfficial\Controller\Admin;

use Crsw\CleverReachOfficial\Components\TaskStatusProvider\TaskStatusProvider;
use Crsw\CleverReachOfficial\Components\Utility\Bootstrap;
use Crsw\CleverReachOfficial\Components\Utility\Initializer;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueItem;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueService;
use Exception;
use Shopware\Core\Framework\Api\Response\JsonApiResponse;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class InitialSyncController
 *
 * @package Crsw\CleverReachOfficial\Controller\Admin
 */
class InitialSyncController extends AbstractController
{
    /**
     * @var QueueService
     */
    private $queueService;
    /**
     * @var TaskStatusProvider
     */
    private $taskStatusProvider;

    /**
     * InitialSyncController constructor.
     *
     * @param Initializer $initializer
     * @param QueueService $queueService
     * @param TaskStatusProvider $taskStatusProvider
     */
    public function __construct(
        Initializer $initializer,
        QueueService $queueService,
        TaskStatusProvider $taskStatusProvider
    ) {
        Bootstrap::init();
        $initializer->registerServices();
        $this->queueService = $queueService;
        $this->taskStatusProvider = $taskStatusProvider;
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(path="api/v{version}/cleverreach/initialSync", name="api.cleverreach.initialSync", methods={"GET"})
     * @Route(path="api/cleverreach/initialSync", name="api.cleverreach.initialSync.new", methods={"GET"})
     *
     * @return JsonApiResponse
     */
    public function getInitialSyncStatus(): JsonApiResponse
    {
        try {
            /** @var QueueItem $task */
            $task = $this->queueService->findLatestByType('InitialSyncTask');

            if (!$task || $task->getStatus() === QueueItem::COMPLETED) {
                return new JsonApiResponse(['initialSync' => false]);
            }

            if ($task->getStatus() === QueueItem::FAILED) {
                $taskStatus = $this->taskStatusProvider->getTaskData($task);

                return new JsonApiResponse([
                    'initialSync' => false,
                    'initialSyncError' => true,
                    'errorDescription' => $taskStatus['errorMessage']
                ]);
            }

            return new JsonApiResponse([
                'initialSync' => true,
                'initialSyncError' => false,
                'progressValue' => $this->getProgressValue($task),
                'runningSubTask' => $this->getRunningSubtask($task)
            ]);
        } catch (Exception $e) {
            Logger::logError($e->getMessage());
            return new JsonApiResponse();
        }
    }

    /**
     * @param QueueItem $task
     *
     * @return float
     */
    private function getProgressValue(QueueItem $task): float
    {
        return round($task->getProgressBasePoints() / 100);
    }

    /**
     * @param QueueItem $task
     *
     * @return string
     *
     * @throws QueueItemDeserializationException
     */
    private function getRunningSubtask(QueueItem $task)
    {
        $taskInProgress = [];
        $taskProgress = $task->getTask()->getProgressByTask();

        foreach ($taskProgress as $key => $item) {
            if ($item > 0 && $item < 100) {
                $parts = explode('\\', $key);
                $taskInProgress = end($parts);
            }
        }

        return $taskInProgress;
    }
}