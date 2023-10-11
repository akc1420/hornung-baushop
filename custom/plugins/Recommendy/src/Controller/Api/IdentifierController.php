<?php declare(strict_types=1);

namespace Recommendy\Controller\Api;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Recommendy\Core\Content\Identifier\IdentifierDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
class IdentifierController extends AbstractController
{
    /**
     * @param EntityRepository $identifierRepository
     * @param Connection $connection
     * @param LoggerInterface $logger
     */
    public function __construct
    (
        EntityRepository $identifierRepository,
        Connection                $connection,
        LoggerInterface           $logger
    )
    {
        parent::__construct($identifierRepository, $connection, $logger, IdentifierDefinition::ENTITY_NAME);
    }
    /**
     * @Route("/api/RecommendyIdentifier", name="api.RecommendyIdentifier", methods={"GET"})
     * @Route("/api/recommendy-identifier", name="api.recommendy-identifier", methods={"GET"})
     * @Route("/api/recommendy_identifier", name="api.recommendy_identifier", methods={"GET"})
     *
     * @return JsonResponse
     */
    public function indexAction(): JsonResponse
    {
        return new JsonResponse(['success' => true]);
    }

    /**
     * @Route("/api/RecommendyIdentifier", name="api.RecommendyIdentifier", methods={"POST"})
     * @Route("/api/recommendy-identifier", name="api.recommendy-identifier", methods={"POST"})
     * @Route("/api/recommendy_identifier", name="api.recommendy_identifier", methods={"POST"})
     *
     * @param Request $request
     * @param Context $context
     * @return JsonResponse
     */
    public function postAction(Request $request, Context $context): JsonResponse
    {
        return parent::postAction($request, $context);
    }

    /**
     * @param array $dataInsert
     * @return array
     */
    protected function generateUpsertData(array $dataInsert): array
    {
        $upsertData = [];
        foreach ($dataInsert as $data) {
            array_push($upsertData, [
                'id' => md5($data['P'] . $data['S']),
                'primaryProductId' => $data['P'],
                'identifier' => $data['S']
            ]);
        }
        return $upsertData;
    }
}
