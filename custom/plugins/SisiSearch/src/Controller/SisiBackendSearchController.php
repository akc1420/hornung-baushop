<?php

namespace Sisi\Search\Controller;

use Doctrine\DBAL\Connection;
use Exception;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Sisi\Search\Service\ClientService;
use Sisi\Search\Service\ContextService;
use Sisi\Search\Service\CriteriaService;
use Sisi\Search\Service\ExtSearchService;
use Sisi\Search\Service\SearchExtraQueriesService;
use Sisi\Search\Service\VariantenService;
use Sisi\Search\ServicesInterfaces\InterfaceFrontendService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Sisi\Search\Service\QueryService;
use Sisi\Search\Service\SearchHelpService;
use Sisi\Search\Service\SisiGetFieldsService;

/**
 * @RouteScope(scopes={"api"})
 *
 *  @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SisiBackendSearchController extends AbstractController
{
    /**
     *
     *   @var ContainerInterface
     */
    protected $container;

    /**
     *
     *  @var SystemConfigService
     */
    protected $config;

    /**
     *  @var Connection
     */

    protected $connection;

    /**
     * @var SisiGetFieldsService
     */
    protected $sisiGetFieldService;

    /**
     *
     * @var InterfaceFrontendService
     */
    private $frontendService;

    /**
     * @var  AbstractSalesChannelContextFactory
     */
    protected $channelfactory;

    /**
     * @param ContainerInterface $container
     * @param SystemConfigService $config
     * @param Connection $connection
     * @param SisiGetFieldsService $sisiGetFieldsService
     */
    public function __construct(
        ContainerInterface $container,
        SystemConfigService $config,
        Connection $connection,
        SisiGetFieldsService $sisiGetFieldsService,
        InterfaceFrontendService $frontendService,
        AbstractSalesChannelContextFactory $channelfactory
    ) {
        $this->container = $container;
        $this->config = $config;
        $this->connection = $connection;
        $this->sisiGetFieldService = $sisiGetFieldsService;
        $this->frontendService = $frontendService;
        $this->channelfactory = $channelfactory;
    }

    /**
     * @Route("api/_action/sisi/backend/search", name="api.action.sisibackendsearch", methods={"POST"} )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sisibackendsearch(Request $request)
    {
        $heandlerExtra = new SearchExtraQueriesService();
        $heandler = new SearchExtraQueriesService();
        $heandlervariants = new VariantenService();
        $hanlerExSearchService = new ExtSearchService();
        $criteriaHandler = new CriteriaService();
        $contextService = new ContextService();
        $result = (array)json_decode($request->getContent());
        $result = (array)$result['config'];
        $config = $this->config->get("SisiSearch.config");
        $heandlerClient = new ClientService();
        $criteriaChannel = new Criteria();
        $size = 10;
        $from = $result['page'] * $size;
        $terms = [];
        $fields = [];
        $shop = explode("_", $result['shop']);
        $salechannelID = $shop[0];
        $languageId = $shop[1];
        $client = $heandlerClient->createClient($config);
        $heandlerQueryService = new QueryService();
        $helpService = new SearchHelpService();
        $index = $helpService->findLast($this->connection, $salechannelID, $languageId);
        $match = $heandlerQueryService->getTheKindOfMatch($config);
        $fieldsconfig = $this->sisiGetFieldService->getFields();
        $terms['product'] = $result['term'];
        $helpService->getFields($terms, $fieldsconfig, $fields, $config, $match);
        if ($index != false && $index != null) {
            $search = $hanlerExSearchService->stripUrl($terms['product'], $config);
            $params = $heandlerQueryService->getQuery($index, $fields, $config, $from, $size);
            $heandlerExtra->addSuggest($params, $config, $search);
            $heandlervariants->changeQueryForvariantssearch($params, $search, $fieldsconfig, $config, false);
            $params['body']['query'] = $heandler->removeCategorienFromTheQuery($params['body']['query'], $config);
            $criteriaHandler->getMergeCriteriaForSalesChannel($criteriaChannel, "shopID=" . $shop[0]);
            $token = $contextService->getRandomHex();
            $saleschannelContext = $this->channelfactory->create(
                $token,
                $salechannelID,
                [
                    SalesChannelContextService::LANGUAGE_ID => strtolower(
                        $languageId
                    )
                ]
            );
            try {
                $result = $this->frontendService->search($client, $params, $saleschannelContext, $this->container);
            } catch (Exception $e) {
                $exception = $e->getMessage();
                $params['hits']['total']['value'] = $exception;
            }
            if (is_array($result) && array_key_exists('hits', $result)) {
                if (array_key_exists('hits', $result['hits'])) {
                    return new JsonResponse($result);
                }
            }
        }
        if (!isset($params['hits']['total']['value'])) {
            $params['hits']['total']['value'] = 0;
        }
        $params['hits']['hits'] = false;
        return new JsonResponse($params);
    }

    /**
     * @Route("api/_action/sisi/backend/shop", name="api.action.sisibackendshop", methods={"POST"} )
     *
     * @return JsonResponse
     */
    public function sisibackendshop()
    {
        $channels = $this->sisiGetFieldService->channels();
        $return['shop'] = [];
        foreach ($channels as $channel) {
            foreach ($channel->getLanguages() as $language) {
                $return['channel'][] = ['text' => $channel->getName() . '_' . $language->getName(), 'value' => $channel->getId() . '_' . $language->getId()];
            }
        }
        return new JsonResponse($return);
    }
}
