<?php declare(strict_types=1);

namespace Recommendy\Controller\Api;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Recommendy\Core\Content\BundleMatrix\BundleMatrixDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
class BundleMatrixController extends AbstractController
{
    /**
     * @param EntityRepository $bundleMatrixRepository
     * @param Connection $connection
     * @param LoggerInterface $logger
     */
    public function __construct
    (
        EntityRepository $bundleMatrixRepository,
        Connection                $connection,
        LoggerInterface           $logger
    )
    {
        parent::__construct($bundleMatrixRepository, $connection, $logger, BundleMatrixDefinition::ENTITY_NAME);
    }

    /**
     * @Route("/api/RecommendyBundleMatrix", name="api.RecommendyBundleMatrix", methods={"GET"})
     * @Route("/api/recommendy-bundle-matrix", name="api.recommendy-bundle-matrix", methods={"GET"})
     * @Route("/api/recommendy_bundle_matrix", name="api.recommendy_bundle_matrix", methods={"GET"})
     *
     * @return JsonResponse
     */
    public function indexAction(): JsonResponse
    {
        return new JsonResponse(['success' => true]);
    }

    /**
     * @Route("/api/RecommendyBundleMatrix", name="api.RecommendyBundleMatrix", methods={"POST"})
     * @Route("/api/recommendy-bundle-matrix", name="api.recommendy-bundle-matrix", methods={"POST"})
     * @Route("/api/recommendy_bundle_matrix", name="api.recommendy_bundle_matrix", methods={"POST"})
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
