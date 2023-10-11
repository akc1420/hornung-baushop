<?php

declare(strict_types=1);

namespace Sisi\Search\Controller;

use Doctrine\DBAL\Connection;
use mysql_xdevapi\Exception;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Sisi\Search\Service\BackendStatisticsService;
use Sisi\Search\Service\ClientService;
use Sisi\Search\Service\ContextService;
use Sisi\Search\Service\QuerylogService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sisi\Search\Service\BackendIndexService;

/**
 * @RouteScope(scopes={"api"})
 */
class DashController extends AbstractController
{
    /**
     *
     * @var ContainerInterface
     */
    protected $container;


    /**
     *
     * @var SystemConfigService
     */
    protected $config;


    /**
     * @var Connection
     */
    protected $connection;


    public function __construct(ContainerInterface $container, SystemConfigService $config, Connection $connection)
    {
        $this->container = $container;
        $this->config = $config;
        $this->connection = $connection;
    }

    /**
     *
     *
     * @Route("api/_action/sisi/sisisearch/history", name="api.action.sisisearch.history", methods={"POST"})
     */
    public function getQueryhistory(Request $request): JsonResponse
    {
        $config = $this->config->get("SisiSearch.config");
        $heandlerClient = new ClientService();
        $heandlerBackendStatistics = new BackendStatisticsService();
        $heandlerQuerlog = new QuerylogService();
        $client = $heandlerClient->createClient($config);
        $result = (array)json_decode($request->getContent());
        $result = (array)$result['config'];
        $resultArray = explode("_", $result['channel']);
        try {
            $saleschannelItem = $heandlerQuerlog->findAll($this->connection, $resultArray[0]);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        $saleschannelItem['languageId'] = $resultArray[1];
        $elasticvalue[] = $heandlerBackendStatistics->getHistory($config, $client, $saleschannelItem);
        return new JsonResponse($elasticvalue);
    }
}
