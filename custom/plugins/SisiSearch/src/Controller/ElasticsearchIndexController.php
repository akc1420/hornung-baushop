<?php

namespace Sisi\Search\Controller;

use Doctrine\DBAL\Driver\Exception;
use Sisi\Search\Service\V2\IndexService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(defaults={"_routeScope"={"api"}})
 */
class ElasticsearchIndexController extends AbstractController
{
    private IndexService $indexService;

    /**
     * @param IndexService $indexService
     */
    public function __construct(
        IndexService $indexService
    ) {
        $this->indexService = $indexService;
    }


    /**
     * @Route("api/_action/sisi/elasticsearch/deleteIndex", name="api.action.deleteIndex", methods={"POST"} )
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function deleteIndex(Request $request): JsonResponse
    {
        $indexName = $request->get('indexName');
        if ($indexName) {
            $result = $this->indexService->deleteIndex($indexName);
        } else {
            $result['status'] = "No index selected";
        }
        return new JsonResponse($result);
    }

    /**
     * @Route("api/_action/sisi/elasticsearch/getIndexes", name="api.action.getIndexes", methods={"POST"} )
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function getIndexes(Request $request): JsonResponse
    {
        $salesChannelId = $request->get('salesChannelId');
        $indices = $this->indexService->getIndexes($salesChannelId);
        $statistics = $this->indexService->getStatistics($salesChannelId);
        $list = [];
        foreach ($indices as $index) {
            $row = $index;
            $indexStatistics = $statistics['indices'];
            if (array_key_exists($index['index'], $indexStatistics)) {
                $indexStat = $indexStatistics[$index['index']];
                $row['indexSize'] = $indexStat['total']['store']['size_in_bytes'];
                $row['docs'] = $indexStat['primaries']['docs']['count'];
            }
            $list[] = $row;
        }

        return new JsonResponse($list);
    }

    /**
     * * @Route("api/_action/sisi/elasticsearch/getStatus", name="api.action.getStatus", methods={"POST"} )
     * @param Request $request
     * @return JsonResponse
     */
    public function getStatus(Request $request): JsonResponse
    {
        $salesChannelId = $request->get('salesChannelId');
        $cluster = $this->indexService->getCluster($salesChannelId);

        return new JsonResponse($cluster);
    }
}
