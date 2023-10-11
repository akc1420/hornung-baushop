<?php


namespace Crsw\CleverReachOfficial\Controller\Storefront;

use Crsw\CleverReachOfficial\Components\DynamicContent\DynamicContentHandler;
use Crsw\CleverReachOfficial\Components\Utility\Bootstrap;
use Crsw\CleverReachOfficial\Components\Utility\Initializer;
use Crsw\CleverReachOfficial\Core\Infrastructure\Exceptions\BaseException;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Shopware\Core\Framework\Api\Response\JsonApiResponse;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ProductSearchController
 *
 * @package Crsw\CleverReachOfficial\Controller\Storefront
 */
class ProductSearchController extends AbstractController
{
    /**
     * @var DynamicContentHandler
     */
    private $dynamicContentHandler;

    /**
     * ProductSearchController constructor.
     *
     * @param DynamicContentHandler $dynamicContentHandler
     * @param Initializer $initializer
     */
    public function __construct(DynamicContentHandler $dynamicContentHandler, Initializer $initializer)
    {
        Bootstrap::init();
        $initializer->registerServices();
        $this->dynamicContentHandler = $dynamicContentHandler;
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(path="/api/v{version}/cleverreach/search", name="api.cleverreach.search",
     *      defaults={"csrf_protected"=false, "auth_required"=false}, methods={"GET", "POST"})
     * @Route(path="/api/cleverreach/search", name="api.cleverreach.search.new",
     *      defaults={"csrf_protected"=false, "auth_required"=false}, methods={"GET", "POST"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function search(Request $request): Response
    {
        try {
            $data = $this->dynamicContentHandler->handleRequest($request);
        } catch (BaseException $e) {
            $data = [];
            Logger::logError($e->getMessage(), 'Integration');
        }

        return new JsonApiResponse($data, 200);
    }
}