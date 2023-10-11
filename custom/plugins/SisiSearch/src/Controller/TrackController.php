<?php

declare(strict_types=1);

namespace Sisi\Search\Controller;

use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Sisi\Search\ServicesInterfaces\InterfaceQuerylogSearchService;
use Sisi\Search\Service\QuerylogService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sisi\Search\Service\BackendIndexService;
use Shopware\Storefront\Framework\Cache\Annotation\HttpCache;
use Sisi\Search\Service\ClientService;
use Elasticsearch\Client;
use  Sisi\Search\Service\ContextService;

/**
 * @RouteScope(scopes={"storefront"})
 */
class TrackController extends AbstractController
{


    /**
     * @var SystemConfigService
     */
    private $systemConfigService;


    /**
     * @var InterfaceQuerylogSearchService
     */
    protected $querylogSearchService;


    /**
     * @var Client
     */
    private $client;


    /**
     * @var ContextService
     */
    protected $contexService;


    /**
     *
     * @param SystemConfigService $systemConfigService
     * @param  InterfaceQuerylogSearchService $querylogSearchService
     *
     */
    public function __construct($systemConfigService, $querylogSearchService)
    {
        $heandlerClient = new ClientService();
        $this->contexService = new ContextService();
        $this->systemConfigService = $systemConfigService;
        $systemConfig = $this->systemConfigService->get("SisiSearch.config");
        $this->client = $heandlerClient->createClient($systemConfig);
        $this->querylogSearchService = $querylogSearchService;
    }

    /**
     *
     * @Route("/sisiontrack", name="frontend.track.ontrack", defaults={"XmlHttpRequest"=true}, methods={"GET"})
     * @param Request $request
     * @return JsonResponse
     */
    public function onTrack(SalesChannelContext $context, Request $request): JsonResponse
    {
        $searchTerm = $request->query->get('searchTerm');
        $productName = $request->query->get('produktname');
        $heanderquerylog = new QuerylogService();
        $saleschannel = $context->getSalesChannel();
        $languageId = $saleschannel->getLanguageId();
        $language = $request->query->get('language');
        if (!empty($language)) {
            $languageId = $language;
        }
        $number = $request->query->get('number');
        $urlLink = $request->query->get('urlLink');
        $parameters['shop'] = $saleschannel->getName();
        $parameters['channelId'] = $saleschannel->getId();
        $parameters['lanuageName'] = $languageId;
        $esIndex = $heanderquerylog->createIndexName($parameters);
        $customer = $context->getCustomer();
        $customerId = "";
        $groupdId = "";
        if ($customer !== null) {
            $customerId = $customer->getId();
            $groupdId = $customer->getGroupId();
        }
        $fields = [
            "product_name" => $productName,
            "product_url" =>  $urlLink,
            "term" => $searchTerm,
            "number" => $number,
            "language_id" => $languageId,
            "customerId" => $customerId,
            "customerGroupId" => $groupdId,
            "time" => time()
        ];
        $result = $this->querylogSearchService->insert($fields, $esIndex, $this->client);
        return  new JsonResponse([0 => $result]);
    }
}
