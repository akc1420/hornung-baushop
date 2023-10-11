<?php


namespace Crsw\CleverReachOfficial\Controller\Admin;

use Crsw\CleverReachOfficial\Components\Utility\Bootstrap;
use Crsw\CleverReachOfficial\Components\Utility\Initializer;
use Crsw\CleverReachOfficial\Core\BusinessLogic\SupportConsole\Contracts\SupportService;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueItem;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Api\Response\JsonApiResponse;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class SupportController
 *
 * @package Crsw\CleverReachOfficial\Controller\Admin
 */
class SupportController extends AbstractController
{
    /**
     * @var SupportService
     */
    private $supportService;

    /**
     * SupportController constructor.
     *
     * @param Initializer $initializer
     * @param SupportService $supportService
     */
    public function __construct(Initializer $initializer, SupportService $supportService)
    {
        Bootstrap::init();
        $initializer->registerServices();
        $this->supportService = $supportService;
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(path="api/v{version}/cleverreach/support/display", name="api.cleverreach.support.display", methods={"GET"})
     * @Route(path="api/cleverreach/support/display", name="api.cleverreach.support.display.new", methods={"GET"})
     *
     * @return JsonApiResponse
     */
    public function display(): JsonApiResponse
    {
        $data = $this->supportService->get();
        $data['TASK_STATS'] = [
            'queued' => $this->getTaskStatistics(QueueItem::QUEUED),
            'completed' => $this->getTaskStatistics(QueueItem::COMPLETED),
            'failed' => $this->getTaskStatistics(QueueItem::FAILED),
            'in_progress' => $this->getTaskStatistics(QueueItem::IN_PROGRESS),
        ];
        return new JsonApiResponse($data);
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(path="api/v{version}/cleverreach/support/modify",
     *      name="api.cleverreach.support.modify", methods={"GET", "POST"})
     * @Route(path="api/cleverreach/support/modify",
     *      name="api.cleverreach.support.modify.new", methods={"GET", "POST"})
     *
     * @param Request $request
     *
     * @return JsonApiResponse
     */
    public function modify(Request $request): JsonApiResponse
    {
        $payload = json_decode($request->getContent(), true);

        return new JsonApiResponse($this->supportService->update($payload));
    }

    /**
     * @param $status
     *
     * @return array|mixed[]
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Exception
     */
    private function getTaskStatistics($status)
    {
        /** @var Connection $connection */
        $connection = $this->get(Connection::class);
        $sql = "SELECT COUNT(*) as count, index_2 as taskType 
                FROM cleverreach_entity 
                WHERE type='QueueItem' AND index_1 =:status
                GROUP BY index_2";

        return $connection->executeQuery($sql, ['status' => $status])->fetchAll();
    }
}