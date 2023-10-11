<?php


namespace Crsw\CleverReachOfficial\Controller\Admin;

use Crsw\CleverReachOfficial\Components\Entities\StateTransitionRecord;
use Crsw\CleverReachOfficial\Components\Exceptions\InvalidTaskNameException;
use Crsw\CleverReachOfficial\Components\Tasks\OrdersOlderThanOneYearSyncTask;
use Crsw\CleverReachOfficial\Components\Utility\Bootstrap;
use Crsw\CleverReachOfficial\Components\Utility\Initializer;
use Crsw\CleverReachOfficial\Core\BusinessLogic\InitialSynchronization\Tasks\Composite\InitialSyncTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\SecondarySynchronization\Tasks\Composite\SecondarySyncTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\TaskExecution\QueueService;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\QueryFilter\Operators;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\QueryFilter\QueryFilter;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueItem;
use Crsw\CleverReachOfficial\Service\BusinessLogic\StateTransition\StateTransitionRecordService;
use Exception;
use JsonException;
use Shopware\Core\Framework\Api\Response\JsonApiResponse;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class RetrySyncController
 *
 * @package Crsw\CleverReachOfficial\Controller\Admin
 */
class RetrySyncController extends AbstractController
{
    /**
     * @var QueueService
     */
    private $queueService;

    /**
     * RetrySyncController constructor.
     *
     * @param Initializer $initializer
     * @param QueueService $queueService
     */
    public function __construct(
        Initializer $initializer,
        QueueService $queueService
    ) {
        Bootstrap::init();
        $initializer->registerServices();
        $this->queueService = $queueService;
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(path="api/v{version}/cleverreach/retry", name="api.cleverreach.retry", methods={"GET", "POST"})
     * @Route(path="api/cleverreach/retry", name="api.cleverreach.retry.new", methods={"GET", "POST"})
     *
     * @param Request $request
     *
     * @return JsonApiResponse
     */
    public function retrySync(Request $request): JsonApiResponse
    {
        $task = null;
        $taskType = '';

        $taskName = $request->get('taskName');

        try {
            switch ($taskName) {
                case 'SecondarySyncTask':
                    $task = new SecondarySyncTask();
                    $taskType = SecondarySyncTask::getClassName();
                    break;
                case 'InitialSyncTask':
                    $task = new InitialSyncTask();
                    $taskType = InitialSyncTask::getClassName();
                    break;
                case 'OrdersOlderThanOneYearSyncTask':
                    $task = new OrdersOlderThanOneYearSyncTask();
                    $taskType = OrdersOlderThanOneYearSyncTask::getClassName();
            }

            if (!$task || !$taskType) {
                throw new InvalidTaskNameException('Invalid task name.');
            }

            $this->queueService->enqueue($this->getConfigService()->getDefaultQueueName(), $task);
            $this->resolveFailedRecords($taskType);

            return new JsonApiResponse(['success' => true]);
        } catch (Exception $e) {
            Logger::logError($e->getMessage());
            return new JsonApiResponse(['success' => false]);
        }
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
     * @param $taskType
     *
     * @throws QueryFilterInvalidParamException
     * @throws JsonException
     */
    private function resolveFailedRecords($taskType): void
    {
        $filter = new QueryFilter();
        $filter->where('taskType', Operators::EQUALS, $taskType)
            ->where('status', Operators::EQUALS, QueueItem::FAILED)
            ->where('resolved', Operators::EQUALS, false);

        /** @var StateTransitionRecord[] $stateTransitionRecords */
        $stateTransitionRecords = $this->getStateTransitionRecordService()->findBy($filter);

        foreach ($stateTransitionRecords as $record) {
            $this->getStateTransitionRecordService()->resolve($record);
        }
    }

    /**
     * @return StateTransitionRecordService
     */
    private function getStateTransitionRecordService(): StateTransitionRecordService
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(StateTransitionRecordService::class);
    }
}
