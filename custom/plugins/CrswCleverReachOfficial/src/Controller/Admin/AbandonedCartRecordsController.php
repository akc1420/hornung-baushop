<?php

namespace Crsw\CleverReachOfficial\Controller\Admin;

use Crsw\CleverReachOfficial\Components\AbandonedCart\AbandonedCartRecordsHandler;
use Crsw\CleverReachOfficial\Components\AbandonedCart\DTO\AbandonedCartRecordsRequestPayload;
use Crsw\CleverReachOfficial\Components\Utility\Bootstrap;
use Crsw\CleverReachOfficial\Components\Utility\Initializer;
use Shopware\Core\Framework\Api\Response\JsonApiResponse;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;


/**
 * Class AbandonedCartRecordsController
 *
 * @package Crsw\CleverReachOfficial\Controller\Admin
 */
class AbandonedCartRecordsController extends AbstractController
{

    /**
     * @var AbandonedCartRecordsHandler
     */
    private $abandonedCartRecordsHandler;

    /**
     * AbandonedCartRecordsController constructor.
     *
     * @param Initializer $initializer
     * @param AbandonedCartRecordsHandler $abandonedCartRecordsHandler
     */
    public function __construct(Initializer $initializer, AbandonedCartRecordsHandler $abandonedCartRecordsHandler)
    {
        Bootstrap::init();
        $initializer->registerServices();
        $this->abandonedCartRecordsHandler = $abandonedCartRecordsHandler;
    }


    /**
     * Returns abandoned cart records by provided criteria (filters, limit, page, sort)
     *
     * @RouteScope(scopes={"api"})
     * @Route(path="api/v{version}/cleverreach/abandonedcart/records", name="api.cleverreach.abandonedcart.records", methods={"POST"})
     * @Route(path="/api/cleverreach/abandonedcart/records", name="api.cleverreach.abandonedcart.records.new", methods={"POST", "GET"}, defaults={"auth_required"=false})
     *
     * @param Request $request
     *
     * @return JsonApiResponse
     */
    public function getRecords(Request $request): JsonApiResponse
    {
        $recordsPayloadDTO = AbandonedCartRecordsRequestPayload::fromArray(json_decode($request->getContent(), true));
        $records = $this->abandonedCartRecordsHandler->getRecords($recordsPayloadDTO);

        $filters = array_filter($recordsPayloadDTO->getFilters());

        $count = (empty($filters) && empty($recordsPayloadDTO->getTerm())) ?
            $this->abandonedCartRecordsHandler->countRecords() : count($records);

        return new JsonApiResponse(['records' => $records, 'count' => $count]);
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(path="api/v{version}/cleverreach/abandonedcart/trigger", name="api.cleverreach.abandonedcart.trigger", methods={"POST"})
     * @Route(path="/api/cleverreach/abandonedcart/trigger", name="api.cleverreach.abandonedcart.trigger.new", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonApiResponse
     */
    public function trigger(Request $request)
    {
        try {
            $success = true;
            $this->abandonedCartRecordsHandler->trigger($request->get('recordId'));
        } catch (\Exception $exception) {
            $success = false;
        }

        return new JsonApiResponse(['success' => $success]);
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(path="api/v{version}/cleverreach/abandonedcart/remove/{recordId}", name="api.cleverreach.abandonedcart.remove", methods={"DELETE"})
     * @Route(path="/api/cleverreach/abandonedcart/remove/{recordId}", name="api.cleverreach.abandonedcart.remove.new", methods={"DELETE"})
     *
     * @param string $recordId
     *
     * @return JsonApiResponse
     */
    public function remove(string $recordId)
    {
        try {
            $success = true;
            $this->abandonedCartRecordsHandler->delete($recordId);
        } catch (\Exception $exception) {
            $success = false;
        }

        return new JsonApiResponse(['success' => $success]);
    }
}
