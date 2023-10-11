<?php


namespace Crsw\CleverReachOfficial\Controller\Admin;

use Crsw\CleverReachOfficial\Components\Utility\Bootstrap;
use Crsw\CleverReachOfficial\Components\Utility\Initializer;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Uninstall\UninstallService;
use Shopware\Core\Framework\Api\Response\JsonApiResponse;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class DisconnectController
 *
 * @package Crsw\CleverReachOfficial\Controller\Admin
 */
class DisconnectController extends AbstractController
{
    /**
     * @var UninstallService
     */
    private $uninstallService;

    /**
     * DisconnectController constructor.
     *
     * @param Initializer $initializer
     * @param UninstallService $uninstallService
     */
    public function __construct(Initializer $initializer, UninstallService $uninstallService)
    {
        Bootstrap::init();
        $initializer->registerServices();
        $this->uninstallService = $uninstallService;
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(path="api/v{version}/cleverreach/disconnect", name="api.cleverreach.disconnect", methods={"GET", "POST"})
     * @Route(path="api/cleverreach/disconnect", name="api.cleverreach.disconnect.new", methods={"GET", "POST"})
     *
     * @return JsonApiResponse
     */
    public function disconnect(): JsonApiResponse
    {
        $this->uninstallService->removeData();

        return new JsonApiResponse(['success' => true]);
    }
}