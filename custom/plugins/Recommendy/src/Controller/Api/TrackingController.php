<?php declare(strict_types=1);

namespace Recommendy\Controller\Api;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Recommendy\Core\Content\Tracking\TrackingDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
class TrackingController extends AbstractController
{
    /**
     * @param EntityRepository $trackingRepository
     * @param Connection $connection
     * @param LoggerInterface $logger
     */
    public function __construct
    (
        EntityRepository $trackingRepository,
        Connection                $connection,
        LoggerInterface           $logger
    )
    {
        parent::__construct($trackingRepository, $connection, $logger, TrackingDefinition::ENTITY_NAME);
    }

    /**
     * @Route("/api/RecommendyTracking", name="api.RecommendyTracking", methods={"GET"})
     * @Route("/api/recommendy-tracking", name="api.recommendy-tracking", methods={"GET"})
     * @Route("/api/recommendy_tracking", name="api.recommendy_tracking", methods={"GET"})
     *
     * @return JsonResponse
     */
    public function indexAction(): JsonResponse
    {
        return new JsonResponse(['success' => true]);
    }

    /**
     * @Route("/api/RecommendyTracking", name="api.RecommendyTracking", methods={"POST"})
     * @Route("/api/recommendy-tracking", name="api.recommendy-tracking", methods={"POST"})
     * @Route("/api/recommendy_tracking", name="api.recommendy_tracking", methods={"POST"})
     *
     * @param Request $request
     * @param Context $context
     * @return JsonResponse
     */
    public function postAction(Request $request, Context $context): JsonResponse
    {
        $action = $request->get('RecommendyAction');

        if ($action === 'RecommendyDelete') {
            return $this->delete($request, $context);
        }

        if ($action === 'RecommendyGet') {
            return $this->fetchAll($request, $context);
        }

        return $this->notFound($action);
    }
}
