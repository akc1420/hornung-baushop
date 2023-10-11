<?php

namespace Crsw\CleverReachOfficial\Controller\Storefront;

use Crsw\CleverReachOfficial\Components\Utility\Bootstrap;
use Crsw\CleverReachOfficial\Components\Utility\Initializer;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Interfaces\AsyncProcessService;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class AsyncProcessController
 *
 * @package Crsw\CleverReachOfficial\Controller\Storefront
 */
class AsyncProcessController extends AbstractController
{
    /**
     * AsyncProcessController constructor.
     *
     * @param Initializer $initializer
     */
    public function __construct(
        Initializer $initializer
    ) {
        Bootstrap::init();
        $initializer->registerServices();
    }

    /**
     * Async process starter endpoint
     *
     * @RouteScope(scopes={"api"})
     * @Route(path="/api/v{version}/cleverreach/async/{guid}", name="api.cleverreach.async",
     *      defaults={"csrf_protected"=false, "auth_required"=false}, methods={"GET", "POST"})
     * @Route(path="/api/cleverreach/async/{guid}", name="api.cleverreach.async.new",
     *      defaults={"csrf_protected"=false, "auth_required"=false}, methods={"GET", "POST"})
     *
     * @param string $guid
     *
     * @return JsonResponse
     */
    public function run(string $guid): JsonResponse
    {
        $this->getAsyncProcessService()->runProcess($guid);

        return new JsonResponse(['success' => true]);
    }

    /**
     * @return AsyncProcessService
     */
    private function getAsyncProcessService(): AsyncProcessService
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(AsyncProcessService::class);
    }
}
