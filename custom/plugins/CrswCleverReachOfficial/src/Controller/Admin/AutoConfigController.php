<?php


namespace Crsw\CleverReachOfficial\Controller\Admin;


use Crsw\CleverReachOfficial\Components\Utility\Bootstrap;
use Crsw\CleverReachOfficial\Components\Utility\Initializer;
use Crsw\CleverReachOfficial\Core\Infrastructure\Exceptions\BaseException;
use Crsw\CleverReachOfficial\Core\Infrastructure\Http\AutoConfiguration;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Shopware\Core\Framework\Api\Response\JsonApiResponse;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class AutoConfigController
 *
 * @package Crsw\CleverReachOfficial\Controller\Admin
 */
class AutoConfigController extends AbstractController
{
    /**
     * AutoConfigController constructor.
     *
     * @param Initializer $initializer
     */
    public function __construct(Initializer $initializer)
    {
        Bootstrap::init();
        $initializer->registerServices();
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(path="api/v{version}/cleverreach/autoconfig", name="api.cleverreach.autoconfig", methods={"GET", "POST"})
     * @Route(path="/api/cleverreach/autoconfig", name="api.cleverreach.autoconfig.new", methods={"GET", "POST"})
     *
     * @return JsonApiResponse
     */
    public function startAutoConfig(): JsonApiResponse
    {
        try {
            $data = ['success' => $this->getAutoConfigService()->start()];
        } catch (BaseException $e) {
            $data = ['success' => false];
        }

        return new JsonApiResponse($data);
    }

    /**
     * @return AutoConfiguration
     */
    private function getAutoConfigService(): AutoConfiguration
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(AutoConfiguration::class);
    }
}