<?php

namespace Crsw\CleverReachOfficial\Controller\Admin;

use Crsw\CleverReachOfficial\Components\Utility\Bootstrap;
use Crsw\CleverReachOfficial\Components\Utility\Initializer;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Tasks\Composite\ConnectTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\TaskExecution\QueueService;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueItem;
use Shopware\Core\Framework\Api\Response\JsonApiResponse;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class CheckConnectionStatusController
 *
 * @package Shopware\Controller\Admin
 */
class CheckConnectionStatusController extends AbstractController
{
    /**
     * @var QueueService
     */
    private $queueService;

    /**
     * CheckConnectionStatusController constructor.
     *
     * @param QueueService $queueService
     * @param Initializer $initializer
     */
    public function __construct(QueueService $queueService, Initializer $initializer)
    {
        Bootstrap::init();
        $initializer->registerServices();
        $this->queueService = $queueService;
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(path="api/v{version}/cleverreach/status", name="api.cleverreach.status",
     *     defaults={"auth_required"=false}, methods={"GET", "POST"})
     * @Route(path="api/cleverreach/status", name="api.cleverreach.status.new",
     *     defaults={"auth_required"=false}, methods={"GET", "POST"})
     *
     * @return JsonApiResponse
     */
    public function checkConnectionStatus(): JsonApiResponse
    {
        $status = 'finished';
        $queueItem = $this->queueService->findLatestByType(ConnectTask::getClassName());

        if ($queueItem !== null) {
            $queueStatus = $queueItem->getStatus();
            if ($queueStatus !== QueueItem::FAILED && $queueStatus !== QueueItem::COMPLETED) {
                $status = QueueItem::IN_PROGRESS;
            }
        }

        return new JsonApiResponse(['status' => $status]);
    }
}
