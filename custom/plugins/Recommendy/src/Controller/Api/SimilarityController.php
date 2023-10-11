<?php declare(strict_types=1);

namespace Recommendy\Controller\Api;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Recommendy\Core\Content\Similarity\SimilarityDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
class SimilarityController extends AbstractController
{
    /**
     * @param EntityRepository $similarityRepository
     * @param Connection $connection
     * @param LoggerInterface $logger
     */
    public function __construct
    (
        EntityRepository $similarityRepository,
        Connection                $connection,
        LoggerInterface           $logger
    )
    {
        parent::__construct($similarityRepository, $connection, $logger, SimilarityDefinition::ENTITY_NAME);
    }

    /**
     * @Route("/api/RecommendySimilarity", name="api.RecommendySimilarity", methods={"GET"})
     * @Route("/api/recommendy-similarity", name="api.recommendy-similarity", methods={"GET"})
     * @Route("/api/recommendy_similarity", name="api.recommendy_similarity", methods={"GET"})
     *
     * @return JsonResponse
     */
    public function indexAction(): JsonResponse
    {
        return new JsonResponse(['success' => true]);
    }

    /**
     * @Route("/api/RecommendySimilarity", name="api.RecommendySimilarity", methods={"POST"})
     * @Route("/api/recommendy-similarity", name="api.recommendy-similarity", methods={"POST"})
     * @Route("/api/recommendy_similarity", name="api.recommendy_similarity", methods={"POST"})
     *
     * @param Request $request
     * @param Context $context
     * @return JsonResponse
     */
    public function postAction(Request $request, Context $context): JsonResponse
    {
        return parent::postAction($request, $context);
    }
}
